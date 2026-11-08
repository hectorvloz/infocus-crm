<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Repositories\FileStore;
use App\Support\WhatsApp\MetaWhatsApp;
use App\Support\WhatsApp\WhatsAppChannel;
use App\Support\WhatsApp\WhatsAppEvents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * Configuración > IA > WhatsApp: proveedor (Twilio, Meta o TextMeBot), sus
 * credenciales (los secretos se guardan cifrados), número del dueño, usuario con
 * el que responde el asistente y mensaje de prueba.
 */
class WhatsAppSettingsController extends Controller
{
    private const MASK = '••••••••';

    /** Campos secretos: vacío o máscara = conservar el actual. */
    private const SECRETS = ['whatsapp_twilio_token', 'whatsapp_meta_token', 'whatsapp_meta_app_secret', 'whatsapp_textmebot_apikey'];

    public function update(Request $request)
    {
        $data = $request->validate([
            'whatsapp_enabled' => 'nullable|boolean',
            'whatsapp_provider' => 'required|in:' . implode(',', array_keys(WhatsAppChannel::PROVIDERS)),
            'whatsapp_owner' => 'nullable|string|max:30',
            'whatsapp_user_id' => 'nullable|string|max:40',
            // Twilio
            'whatsapp_twilio_sid' => ['nullable', 'string', 'max:64', 'regex:/^AC[a-zA-Z0-9]{32}$/'],
            'whatsapp_twilio_token' => 'nullable|string|max:200',
            'whatsapp_from' => 'nullable|string|max:30',
            'whatsapp_template_sid' => ['nullable', 'string', 'max:64', 'regex:/^HX[a-zA-Z0-9]{32}$/'],
            // Meta
            'whatsapp_meta_phone_id' => ['nullable', 'string', 'max:40', 'regex:/^\d+$/'],
            'whatsapp_meta_token' => 'nullable|string|max:1000',
            'whatsapp_meta_app_secret' => 'nullable|string|max:200',
            'whatsapp_meta_version' => ['nullable', 'string', 'max:10', 'regex:/^v\d{1,3}\.\d$/'],
            'whatsapp_meta_template_name' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9_]+$/'],
            'whatsapp_meta_template_lang' => ['nullable', 'string', 'max:10', 'regex:/^[a-z]{2}(_[A-Z]{2})?$/'],
            // TextMeBot
            'whatsapp_textmebot_apikey' => 'nullable|string|max:200',
            'whatsapp_alerts' => 'nullable|array',
            'whatsapp_alerts.*' => 'nullable|boolean',
            'whatsapp_client_invoice' => 'nullable|in:' . implode(',', array_keys(\App\Support\WhatsApp\ClientInvoiceWhatsApp::MODES)),
        ], [
            'whatsapp_twilio_sid.regex' => 'El Account SID de Twilio empieza por AC y tiene 34 caracteres.',
            'whatsapp_template_sid.regex' => 'El Content SID de la plantilla empieza por HX y tiene 34 caracteres.',
            'whatsapp_meta_phone_id.regex' => 'El Phone Number ID de Meta son solo números.',
            'whatsapp_meta_version.regex' => 'La versión de la Graph API tiene la forma v23.0.',
            'whatsapp_meta_template_name.regex' => 'El nombre de la plantilla de Meta va en minúsculas, números y guion bajo.',
            'whatsapp_meta_template_lang.regex' => 'El idioma de la plantilla tiene la forma es o es_CO.',
        ]);

        if (!empty($data['whatsapp_user_id']) && !User::whereKey($data['whatsapp_user_id'])->whereIn('role', ['admin', 'super_admin'])->exists()) {
            return back()->withErrors(['whatsapp_user_id' => 'Elige un usuario administrador.'])->withInput();
        }

        $store = new FileStore('settings.json');
        $current = $store->find('settings') ?: [];

        $payload = [
            'whatsapp_enabled' => $request->boolean('whatsapp_enabled'),
            'whatsapp_provider' => $data['whatsapp_provider'],
            'whatsapp_owner' => WhatsAppChannel::normalizeNumber((string) ($data['whatsapp_owner'] ?? '')),
            'whatsapp_user_id' => (string) ($data['whatsapp_user_id'] ?? ''),
            'whatsapp_twilio_sid' => trim((string) ($data['whatsapp_twilio_sid'] ?? '')),
            'whatsapp_from' => WhatsAppChannel::normalizeNumber((string) ($data['whatsapp_from'] ?? '')),
            'whatsapp_template_sid' => trim((string) ($data['whatsapp_template_sid'] ?? '')),
            'whatsapp_meta_phone_id' => trim((string) ($data['whatsapp_meta_phone_id'] ?? '')),
            'whatsapp_meta_version' => trim((string) ($data['whatsapp_meta_version'] ?? '')) ?: MetaWhatsApp::DEFAULT_VERSION,
            'whatsapp_meta_template_name' => trim((string) ($data['whatsapp_meta_template_name'] ?? '')),
            'whatsapp_meta_template_lang' => trim((string) ($data['whatsapp_meta_template_lang'] ?? '')) ?: 'es',
            // Se generan una vez: el de Meta se pega en Meta, la clave de TextMeBot va en la URL del webhook.
            'whatsapp_meta_verify_token' => (string) ($current['whatsapp_meta_verify_token'] ?? '') ?: Str::random(32),
            'whatsapp_textmebot_webhook_key' => (string) ($current['whatsapp_textmebot_webhook_key'] ?? '') ?: Str::random(40),
            'whatsapp_alerts' => array_key_exists('whatsapp_alerts', $data)
                ? collect(array_keys(\App\Support\WhatsApp\WhatsAppAlerts::TYPES))->mapWithKeys(fn ($type) => [$type => (bool) ($data['whatsapp_alerts'][$type] ?? false)])->all()
                : ($current['whatsapp_alerts'] ?? []),
            'whatsapp_client_invoice' => (string) ($data['whatsapp_client_invoice'] ?? ($current['whatsapp_client_invoice'] ?? 'off')),
        ];
        foreach (self::SECRETS as $field) {
            $value = trim((string) ($data[$field] ?? ''));
            $payload[$field] = $value !== '' && $value !== self::MASK
                ? 'ENC:' . Crypt::encryptString($value)
                : (string) ($current[$field] ?? '');
        }

        $current ? $store->update('settings', $payload) : $store->create(['id' => 'settings'] + $payload);

        return redirect()->route('settings.ai', ['tab' => 'whatsapp'])->with('success', 'WhatsApp actualizado.');
    }

    /** "Soy yo": reconoce como del dueño el id interno de WhatsApp (…@lid) de un mensaje ignorado. */
    public function trust(string $eventId)
    {
        $event = collect((new FileStore(WhatsAppEvents::FILE))->all())->firstWhere('id', $eventId);
        $id = WhatsAppChannel::normalizeId((string) ($event['from'] ?? ''));
        if (!$event || !WhatsAppChannel::isInternalId($id)) {
            return redirect()->route('settings.ai', ['tab' => 'whatsapp'])->with('error', 'Ese mensaje no trae un identificador de WhatsApp para reconocer.');
        }

        $store = new FileStore('settings.json');
        $ids = array_values(array_unique([...((array) (($store->find('settings') ?: [])['whatsapp_owner_ids'] ?? [])), $id]));
        $store->update('settings', ['whatsapp_owner_ids' => array_slice($ids, -5)]);
        WhatsAppEvents::record('infocus', 'reconocido como tuyo', $id, 'Desde ahora los mensajes con este identificador se responden.');

        return redirect()->route('settings.ai', ['tab' => 'whatsapp'])->with('success', 'Listo: ' . $id . ' quedó reconocido como tu WhatsApp. Escríbele de nuevo.');
    }

    public function untrust(Request $request)
    {
        $id = WhatsAppChannel::normalizeId((string) $request->input('id', ''));
        $store = new FileStore('settings.json');
        $ids = array_values(array_filter((array) (($store->find('settings') ?: [])['whatsapp_owner_ids'] ?? []), fn ($v) => WhatsAppChannel::normalizeId((string) $v) !== $id));
        $store->update('settings', ['whatsapp_owner_ids' => $ids]);

        return redirect()->route('settings.ai', ['tab' => 'whatsapp'])->with('success', 'Identificador quitado.');
    }

    public function test()
    {
        $channel = WhatsAppChannel::current();
        $settings = $channel->settings();
        $error = $channel->send($settings['owner'], 'Prueba de Infocus: WhatsApp quedó conectado por ' . WhatsAppChannel::PROVIDERS[$channel->provider()] . '. Escríbeme lo que necesites del CRM.', $settings);

        return redirect()->route('settings.ai', ['tab' => 'whatsapp'])
            ->with($error ? 'error' : 'success', $error ? 'No se pudo enviar: ' . $error : 'Mensaje de prueba enviado a ' . $settings['owner'] . '.');
    }
}
