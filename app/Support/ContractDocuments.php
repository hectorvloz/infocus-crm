<?php

namespace App\Support;

use App\Repositories\FileStore;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * PDF, enlace privado de firma y envío por correo de los contratos.
 */
class ContractDocuments
{
    private FileStore $store;

    public function __construct()
    {
        $this->store = new FileStore('contratos.json');
    }

    public static function settings(): array
    {
        return (new FileStore('settings.json'))->find('settings') ?: [];
    }

    public static function client(array $contract): array
    {
        return (new FileStore('clientes.json'))->find((string) ($contract['cliente_id'] ?? '')) ?: [];
    }

    public static function clientEmail(array $contract): string
    {
        $client = self::client($contract);

        return trim((string) ($client['contacto_email'] ?? $client['email'] ?? ''));
    }

    /** Número corto y legible para el documento, ej. CT-3F9A2C. */
    public static function number(array $contract): string
    {
        return 'CT-' . Str::upper(substr(str_replace('-', '', (string) ($contract['id'] ?? '')), 0, 6));
    }

    public static function fileName(array $contract): string
    {
        $slug = Str::slug(Str::limit((string) ($contract['titulo'] ?? 'contrato'), 60, ''));

        return 'contrato-' . self::number($contract) . ($slug !== '' ? '-' . $slug : '') . '.pdf';
    }

    /** Crea el token del enlace privado si no existe y devuelve el contrato actualizado. */
    public function ensureToken(array $contract): array
    {
        if (!empty($contract['public_token'])) {
            return $contract;
        }

        return $this->store->update($contract['id'], ['public_token' => Str::random(48)]) ?? $contract;
    }

    public static function publicUrl(array $contract): string
    {
        return route('contratos.public.show', ['id' => $contract['id'], 'token' => $contract['public_token']]);
    }

    public static function money(array $contract): string
    {
        if (!isset($contract['monto']) || $contract['monto'] === '' || (float) $contract['monto'] <= 0) {
            return '';
        }
        $currency = (string) ($contract['moneda'] ?? 'COP');
        $amount = (float) $contract['monto'];

        return $currency . ' ' . ($currency === 'COP' ? number_format($amount, 0, ',', '.') : number_format($amount, 2, ',', '.'));
    }

    public static function signature(array $contract, string $role): ?array
    {
        return collect($contract['firmas'] ?? [])->where('rol', $role)->last();
    }

    public static function formatDate(?string $value): string
    {
        if (!$value) {
            return '';
        }

        return Carbon::parse($value)->setTimezone('America/Bogota')->locale('es')->translatedFormat('j \d\e F \d\e Y, g:i a');
    }

    public function pdf(array $contract)
    {
        foreach ([storage_path('fonts'), storage_path('app/dompdf-temp')] as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
        }
        $settings = self::settings();
        $logoPath = $settings['invoice_logo'] ?? ($settings['logo_large'] ?? ($settings['logo'] ?? null));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('contratos.pdf', [
            'contrato' => $contract,
            'cliente' => self::client($contract),
            'settings' => $settings,
            'logo' => $logoPath ? app_public_asset_data_uri($logoPath) : null,
            'numero' => self::number($contract),
            'valor' => self::money($contract),
            'firmaEmpresa' => self::signature($contract, 'Empresa'),
            'firmaCliente' => self::signature($contract, 'Cliente'),
        ])->setPaper('letter');

        $pdf->setOptions([
            'defaultFont' => 'DejaVu Sans',
            'isRemoteEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'fontDir' => storage_path('fonts'),
            'fontCache' => storage_path('fonts'),
            'tempDir' => storage_path('app/dompdf-temp'),
            'chroot' => base_path(),
        ]);

        return $pdf;
    }

    /** Envía el contrato al cliente con el PDF adjunto y el enlace para firmarlo. Devuelve el correo usado. */
    public function sendToClient(array $contract): string
    {
        $to = self::clientEmail($contract);
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('El cliente no tiene un correo de contacto válido. Agrégalo en su ficha.');
        }
        $contract = $this->ensureToken($contract);
        $client = self::client($contract);
        $settings = self::settings();
        $company = trim((string) ($settings['company_name'] ?? '')) ?: 'Indigo';
        $name = trim((string) ($client['contacto_nombre'] ?? '')) ?: (string) ($contract['cliente_nombre'] ?? '');
        $firstName = e(Str::of($name)->trim()->explode(' ')->first() ?: 'hola');
        $url = self::publicUrl($contract);
        $title = e((string) $contract['titulo']);

        $body = "<p>Hola <strong>{$firstName}</strong>,</p>"
            . "<p>Te compartimos el contrato <strong>{$title}</strong> para tu revisión. Va adjunto en PDF y también puedes leerlo y firmarlo en línea desde este enlace:</p>"
            . '<p><a href="' . e($url) . '" style="display:inline-block;padding:14px 26px;border-radius:9999px;background:#0f172a;color:#ffffff;font-size:15px;font-weight:800;text-decoration:none;">Revisar y firmar el contrato</a></p>'
            . '<p>La firma es electrónica y tiene validez conforme a la Ley 527 de 1999. Si tienes preguntas o quieres ajustar algo, responde este correo antes de firmar.</p>'
            . '<p>Gracias por confiar en nosotros,<br><strong>' . e($company) . '</strong></p>';

        TemplateMail::send($to, 'Contrato para tu revisión: ' . $contract['titulo'], $body, [
            'source' => 'contratos',
            'sent_by_name' => (string) (auth()->user()->name ?? $company),
        ], [
            'data' => $this->pdf($contract)->output(),
            'name' => self::fileName($contract),
            'mime' => 'application/pdf',
        ]);

        $this->store->update($contract['id'], [
            'estado' => ($contract['estado'] ?? '') === 'Firmado' ? 'Firmado' : 'Enviado',
            'enviado_a' => $to,
            'enviado_at' => now()->toIso8601String(),
        ]);

        return $to;
    }

    /** Copia firmada para el cliente y aviso al equipo. Un fallo de correo no anula la firma. */
    public function notifySigned(array $contract): void
    {
        $settings = self::settings();
        $company = trim((string) ($settings['company_name'] ?? '')) ?: 'Indigo';
        $signature = self::signature($contract, 'Cliente') ?? [];
        $attachment = ['data' => $this->pdf($contract)->output(), 'name' => self::fileName($contract), 'mime' => 'application/pdf'];
        $title = e((string) $contract['titulo']);

        $to = self::clientEmail($contract);
        if ($to !== '') {
            TemplateMail::send($to, 'Contrato firmado: ' . $contract['titulo'], "<p>Hola,</p><p>Recibimos tu firma del contrato <strong>{$title}</strong>. Te dejamos adjunta la copia firmada para tus archivos.</p><p>Gracias,<br><strong>" . e($company) . '</strong></p>', ['source' => 'contratos'], $attachment);
        }

        $team = array_values(array_filter(array_map('trim', explode(',', (string) (config('services.leads_webhook.notify') ?: ($settings['email_from'] ?? ''))))));
        if ($team) {
            TemplateMail::send($team, 'Firmado: ' . $contract['titulo'] . ' · ' . ($contract['cliente_nombre'] ?? ''), '<p>El cliente firmó el contrato <strong>' . $title . '</strong>.</p><p><strong>Firmante:</strong> ' . e((string) ($signature['nombre'] ?? '')) . ' · ' . e((string) ($signature['documento'] ?? '')) . '<br><strong>Fecha:</strong> ' . e(self::formatDate($signature['fecha'] ?? null)) . '<br><strong>IP:</strong> ' . e((string) ($signature['ip'] ?? '')) . '</p>', ['source' => 'contratos'], $attachment);
        }
    }
}
