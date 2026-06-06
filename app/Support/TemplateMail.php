<?php

namespace App\Support;

use App\Mail\GenericMail;
use App\Repositories\FileStore;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TemplateMail
{
    public static function settings(): array
    {
        return (new FileStore('settings.json'))->find('settings') ?: [];
    }

    public static function tokenReplace(string $text, array $vars): string
    {
        if ($text === '' || empty($vars)) {
            return $text;
        }

        $normalized = [];
        foreach ($vars as $key => $value) {
            $k = strtolower(trim((string) $key, "{} \t\n\r\0\x0B"));
            if ($k !== '') {
                $normalized[$k] = (string) $value;
            }
        }

        if (empty($normalized)) {
            return $text;
        }

        $out = preg_replace_callback('/\{\s*([a-zA-Z0-9_]+)\s*\}/u', function ($m) use ($normalized) {
            $k = strtolower((string) ($m[1] ?? ''));
            return array_key_exists($k, $normalized) ? $normalized[$k] : $m[0];
        }, $text);

        if (!is_string($out) || $out === '') {
            return is_string($out) ? $out : $text;
        }

        // Soporta placeholders escapados por editores HTML: &#123;cliente&#125; o &#x7B;cliente&#x7D;
        $out = preg_replace_callback('/(?:&#123;|&#x7B;)\s*([a-zA-Z0-9_]+)\s*(?:&#125;|&#x7D;)/iu', function ($m) use ($normalized) {
            $k = strtolower((string) ($m[1] ?? ''));
            return array_key_exists($k, $normalized) ? $normalized[$k] : $m[0];
        }, $out);

        return is_string($out) ? $out : $text;
    }

    public static function buildButtons(array $buttons): string
    {
        if (empty($buttons)) {
            return '';
        }

        $out = [];
        foreach ($buttons as $btn) {
            $label = trim((string) ($btn['label'] ?? 'Abrir'));
            $url = trim((string) ($btn['url'] ?? '#'));
            $kind = trim((string) ($btn['kind'] ?? 'secondary'));

            if ($label === '') {
                continue;
            }

            if ($kind === 'primary') {
                $style = 'display:inline-block;background:#d4f547;color:#0f172a;font-weight:700;font-size:14px;padding:11px 24px;border-radius:999px;text-decoration:none;margin:0 6px 8px 0;';
            } else {
                $style = 'display:inline-block;background:#f1f5f9;color:#0f172a;font-weight:700;font-size:14px;padding:11px 24px;border-radius:999px;text-decoration:none;margin:0 6px 8px 0;border:1px solid #e2e8f0;';
            }

            $out[] = '<a href="' . e($url) . '" style="' . $style . '">' . e($label) . '</a>';
        }

        if (empty($out)) {
            return '';
        }

        return '<div style="text-align:center;margin:18px 0 8px;">' . implode('', $out) . '</div>';
    }

    public static function render(
        array $settings,
        string $subjectKey,
        string $bodyKey,
        string $defaultSubject,
        string $defaultBody,
        array $vars = [],
        array $buttons = []
    ): array {
        $subjectTpl = (string) ($settings[$subjectKey] ?? $defaultSubject);
        $bodyTpl = (string) ($settings[$bodyKey] ?? $defaultBody);

        $buttonsHtml = self::buildButtons($buttons);
        if ($buttonsHtml !== '') {
            $vars['cta_buttons'] = $buttonsHtml;
            if (!str_contains($bodyTpl, '{cta_buttons}')) {
                $bodyTpl .= "\n\n{cta_buttons}";
            }
        }

        $subject = self::tokenReplace($subjectTpl, $vars);
        $body = self::tokenReplace($bodyTpl, $vars);

        return [$subject, nl2br($body)];
    }

    public static function send(array|string $to, string $subject, string $body, array $meta = [], ?array $attachment = null): void
    {
        try {
            $mailable = new GenericMail($subject, $body, $attachment);
            if (!empty($meta['from_address'])) {
                $mailable->from((string) $meta['from_address'], (string) ($meta['from_name'] ?? config('mail.from.name')));
            }
            $copyAddress = self::resolveCopyAddress($to, $meta);
            if ($copyAddress !== null) {
                $mailable->bcc($copyAddress);
            }

            $mailer = trim((string) ($meta['mailer'] ?? ''));
            $pending = $mailer !== '' ? Mail::mailer($mailer) : Mail::mailer(config('mail.default'));
            $pending->to($to)->send($mailable);
        } catch (Throwable $e) {
            try {
                $toList = is_array($to) ? $to : [$to];
                $toList = array_values(array_filter(array_map(
                    fn ($mail) => trim((string) $mail),
                    $toList
                ), fn ($mail) => $mail !== ''));

                (new FileStore('email_history.json'))->create([
                    'to' => $toList,
                    'subject' => trim((string) $subject) ?: 'Sin asunto',
                    'body' => strip_tags((string) $body),
                    'status' => 'fallido',
                    'error' => trim($e->getMessage()),
                    'sent_by' => (string) ($meta['sent_by'] ?? (auth()->user()->email ?? session('user.email') ?? 'sistema')),
                    'sent_by_name' => (string) ($meta['sent_by_name'] ?? (auth()->user()->name ?? session('user.name') ?? 'Sistema')),
                    'source' => (string) ($meta['source'] ?? 'mail_error'),
                    'sent_at' => now()->toDateTimeString(),
                ]);
            } catch (Throwable) {
                // no-op
            }
            throw $e;
        }
    }

    protected static function resolveCopyAddress(array|string $to, array $meta): ?string
    {
        if (($meta['copy_to_sender'] ?? true) === false) {
            return null;
        }

        $copyAddress = trim((string) ($meta['copy_to'] ?? $meta['from_address'] ?? config('mail.from.address')));
        if (!filter_var($copyAddress, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $toList = is_array($to) ? $to : [$to];
        foreach ($toList as $recipient) {
            if (strtolower(trim((string) $recipient)) === strtolower($copyAddress)) {
                return null;
            }
        }

        return $copyAddress;
    }

    public static function configureInvoiceMailer(array $settings): string
    {
        if (empty($settings['invoice_smtp_enabled'])) {
            return config('mail.default', 'smtp');
        }

        $host = trim((string) ($settings['invoice_smtp_host'] ?? ''));
        $username = trim((string) ($settings['invoice_smtp_username'] ?? ''));
        $password = self::decryptSetting((string) ($settings['invoice_smtp_password'] ?? ''));

        if ($host === '' || $username === '' || $password === '') {
            return config('mail.default', 'smtp');
        }

        $encryption = (string) ($settings['invoice_smtp_encryption'] ?? 'tls');
        Config::set('mail.mailers.invoice_smtp', [
            'transport' => 'smtp',
            'host' => $host,
            'port' => (int) ($settings['invoice_smtp_port'] ?? 587),
            'encryption' => $encryption === 'none' ? null : $encryption,
            'username' => $username,
            'password' => $password,
            'timeout' => null,
            'local_domain' => config('mail.mailers.smtp.local_domain'),
        ]);
        Mail::purge('invoice_smtp');

        return 'invoice_smtp';
    }

    public static function invoiceFrom(array $settings): array
    {
        if (!empty($settings['invoice_smtp_enabled'])) {
            $fromAddress = trim((string) ($settings['invoice_mail_from_address'] ?? $settings['invoice_smtp_username'] ?? ''));
            if (filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) {
                return [
                    'address' => $fromAddress,
                    'name' => trim((string) ($settings['invoice_mail_from_name'] ?? '')) ?: (string) ($settings['mail_from_name'] ?? config('app.name')),
                ];
            }
        }

        return [
            'address' => (string) ($settings['mail_from_address'] ?? config('mail.from.address')),
            'name' => (string) ($settings['mail_from_name'] ?? config('mail.from.name')),
        ];
    }

    protected static function decryptSetting(string $value): string
    {
        if ($value !== '' && str_starts_with($value, 'ENC:')) {
            try {
                return Crypt::decryptString(substr($value, 4));
            } catch (Throwable) {
                return '';
            }
        }

        return $value;
    }
}
