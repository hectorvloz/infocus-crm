<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Repositories\FileStore;
use App\Support\WhatsApp\TwilioWhatsApp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

/**
 * Configuración > IA > WhatsApp: credenciales de Twilio, número del dueño,
 * usuario con el que responde el asistente y mensaje de prueba.
 */
class WhatsAppSettingsController extends Controller
{
    private const MASK = '••••••••';

    public function update(Request $request)
    {
        $data = $request->validate([
            'whatsapp_enabled' => 'nullable|boolean',
            'whatsapp_twilio_sid' => ['nullable', 'string', 'max:64', 'regex:/^AC[a-zA-Z0-9]{32}$/'],
            'whatsapp_twilio_token' => 'nullable|string|max:200',
            'whatsapp_from' => 'nullable|string|max:30',
            'whatsapp_owner' => 'nullable|string|max:30',
            'whatsapp_user_id' => 'nullable|string|max:40',
        ], [
            'whatsapp_twilio_sid.regex' => 'El Account SID de Twilio empieza por AC y tiene 34 caracteres.',
        ]);

        if (!empty($data['whatsapp_user_id']) && !User::whereKey($data['whatsapp_user_id'])->whereIn('role', ['admin', 'super_admin'])->exists()) {
            return back()->withErrors(['whatsapp_user_id' => 'Elige un usuario administrador.'])->withInput();
        }

        $store = new FileStore('settings.json');
        $current = $store->find('settings') ?: [];
        $token = trim((string) ($data['whatsapp_twilio_token'] ?? ''));

        $payload = [
            'whatsapp_enabled' => $request->boolean('whatsapp_enabled'),
            'whatsapp_twilio_sid' => trim((string) ($data['whatsapp_twilio_sid'] ?? '')),
            'whatsapp_twilio_token' => $token !== '' && $token !== self::MASK
                ? 'ENC:' . Crypt::encryptString($token)
                : (string) ($current['whatsapp_twilio_token'] ?? ''),
            'whatsapp_from' => TwilioWhatsApp::normalizeNumber((string) ($data['whatsapp_from'] ?? '')),
            'whatsapp_owner' => TwilioWhatsApp::normalizeNumber((string) ($data['whatsapp_owner'] ?? '')),
            'whatsapp_user_id' => (string) ($data['whatsapp_user_id'] ?? ''),
        ];

        $current ? $store->update('settings', $payload) : $store->create(['id' => 'settings'] + $payload);

        return redirect()->route('settings.ai', ['tab' => 'whatsapp'])->with('success', 'WhatsApp actualizado.');
    }

    public function test(TwilioWhatsApp $whatsapp)
    {
        $settings = $whatsapp->settings();
        $error = $whatsapp->send($settings['owner'], 'Prueba de Infocus: WhatsApp quedó conectado. Escríbeme lo que necesites del CRM.', $settings);

        return redirect()->route('settings.ai', ['tab' => 'whatsapp'])
            ->with($error ? 'error' : 'success', $error ? 'No se pudo enviar: ' . $error : 'Mensaje de prueba enviado a ' . $settings['owner'] . '.');
    }
}
