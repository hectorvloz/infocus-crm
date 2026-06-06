<?php

namespace App\Mail;

use App\Repositories\FileStore;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;

class GenericMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subjectLine;
    public $content;
    public $attachment;
    public $brandName;
    public $logoUrl;
    public $logoPath;
    public $preheader;
    public $websiteUrl;
    public $socialLinks;
    public $mailTheme;
    public $footerNote;

    /**
     * Create a new message instance.
     */
    public function __construct($subject, $content, $attachment = null)
    {
        $settings = (new FileStore('settings.json'))->find('settings') ?: [];

        $this->subjectLine = $subject;
        $this->content = $this->formatContent($content);
        $this->attachment = $attachment;
        $this->brandName = $settings['mail_from_name'] ?? $settings['company_name'] ?? config('app.name');
        [$this->logoUrl, $this->logoPath] = $this->resolveLogoData($settings);
        $this->preheader = $this->makePreheader($content);
        $this->websiteUrl = $this->resolveWebsiteUrl($settings);
        $this->socialLinks = $this->resolveSocialLinks($settings);
        $this->mailTheme = $this->resolveMailTheme($settings);
        $this->footerNote = trim((string) ($settings['mail_footer_note'] ?? ''));
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.generic',
            with: [
                'content' => $this->content,
                'brandName' => $this->brandName,
                'logoUrl' => $this->logoUrl,
                'logoPath' => $this->logoPath,
                'subjectLine' => $this->subjectLine,
                'preheader' => $this->preheader,
                'websiteUrl' => $this->websiteUrl,
                'socialLinks' => $this->socialLinks,
                'mailTheme' => $this->mailTheme,
                'footerNote' => $this->footerNote,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        if ($this->attachment) {
            return [\Illuminate\Mail\Mailables\Attachment::fromData(
                fn() => $this->attachment['data'],
                $this->attachment['name']
            )->withMime($this->attachment['mime'])];
        }
        return [];
    }

    protected function formatContent(string $content): string
    {
        if ($this->containsHtml($content)) {
            return $content;
        }

        return nl2br(e($content));
    }

    protected function containsHtml(string $content): bool
    {
        return preg_match('/<[^>]+>/', $content) === 1;
    }

    protected function makePreheader(string $content): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($content)));

        return mb_strimwidth($text, 0, 120, '...');
    }

    protected function resolveLogoData(array $settings): array
    {
        $publicBase = $this->resolvePublicBaseUrl($settings);
        $publicHost = strtolower((string) (parse_url((string) $publicBase, PHP_URL_HOST) ?? ''));

        // Priorizar logo completo de empresa para correos (no favicon).
        $candidates = [
            $settings['logo_large'] ?? null,
            $settings['logo'] ?? null,
            $settings['logo_small'] ?? null,
            $settings['invoice_logo'] ?? null,
            '/uploads/branding/logo-infocus.svg',
            '/uploads/branding/logo.png',
        ];

        foreach ($candidates as $candidate) {
            if (!$candidate) {
                continue;
            }

            $logoRaw = trim((string) $candidate);
            if ($logoRaw === '') {
                continue;
            }

            // Solo permitir URL externa si es del mismo dominio publico de la app.
            if (preg_match('/^https?:\/\//i', $logoRaw) === 1) {
                $logoHost = strtolower((string) (parse_url($logoRaw, PHP_URL_HOST) ?? ''));
                if ($logoHost !== '' && $publicHost !== '' && $logoHost === $publicHost) {
                    return [$logoRaw, null];
                }
                continue;
            }

            $relative = ltrim($logoRaw, '/');
            $fullPath = public_path($relative);
            if (!File::exists($fullPath)) {
                continue;
            }

            $logoUrl = $publicBase
                ? rtrim($publicBase, '/') . '/' . $relative
                : url('/' . $relative);

            return [$logoUrl, $fullPath];
        }

        return [null, null];
    }

    protected function resolvePublicBaseUrl(array $settings): ?string
    {
        $candidates = [
            $settings['app_url'] ?? null,
            $settings['invoice_website'] ?? null,
            $settings['company_website'] ?? null,
            config('app.url'),
        ];

        foreach ($candidates as $raw) {
            $url = trim((string) $raw);
            if ($url === '') {
                continue;
            }
            if (!preg_match('/^https?:\/\//i', $url)) {
                $url = 'https://' . $url;
            }
            $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?? ''));
            if ($host === '' || $host === 'localhost' || str_ends_with($host, '.local')) {
                continue;
            }
            if (str_starts_with($host, '127.') || str_starts_with($host, '0.0.0.0')) {
                continue;
            }
            return rtrim($url, '/');
        }

        return null;
    }

    protected function resolveWebsiteUrl(array $settings): ?string
    {
        $raw = $settings['invoice_website'] ?? $settings['company_website'] ?? null;
        if (!$raw) return null;
        $url = str_starts_with($raw, 'http') ? $raw : 'https://' . $raw;
        return rtrim($url, '/');
    }

    protected function resolveSocialLinks(array $settings): array
    {
        if (!empty($settings['social_links']) && is_array($settings['social_links'])) {
            $normalized = [];
            foreach ($settings['social_links'] as $row) {
                $network = trim((string) ($row['network'] ?? ''));
                $value = trim((string) ($row['value'] ?? ''));
                if ($network === '' || $value === '') {
                    continue;
                }
                $normalized[$network] = $this->normalizeSocialUrl($network, $value);
            }
            if (!empty($normalized)) {
                return $normalized;
            }
        }

        $links = [];
        $map = [
            'instagram' => ['key' => 'social_instagram', 'base' => 'https://instagram.com/'],
            'facebook'  => ['key' => 'social_facebook',  'base' => 'https://facebook.com/'],
            'x'         => ['key' => 'social_x',         'base' => 'https://x.com/'],
            'linkedin'  => ['key' => 'social_linkedin',  'base' => 'https://linkedin.com/in/'],
            'tiktok'    => ['key' => 'social_tiktok',    'base' => 'https://tiktok.com/@'],
        ];
        foreach ($map as $name => $cfg) {
            $val = trim($settings[$cfg['key']] ?? '');
            if ($val) {
                $links[$name] = str_starts_with($val, 'http') ? $val : $cfg['base'] . ltrim($val, '@/');
            }
        }
        return $links;
    }

    protected function normalizeSocialUrl(string $network, string $value): string
    {
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        $baseByNetwork = [
            'instagram' => 'https://instagram.com/',
            'facebook' => 'https://facebook.com/',
            'x' => 'https://x.com/',
            'linkedin' => 'https://linkedin.com/in/',
            'tiktok' => 'https://tiktok.com/@',
            'youtube' => 'https://youtube.com/',
            'reddit' => 'https://reddit.com/u/',
            'whatsapp' => 'https://wa.me/',
            'telegram' => 'https://t.me/',
        ];

        $base = $baseByNetwork[$network] ?? '';
        return $base . ltrim($value, '@/');
    }

    protected function resolveMailTheme(array $settings): array
    {
        return [
            'headerLabel' => $settings['mail_header_label'] ?? 'Mensaje automatizado',
            'headerFrom' => $settings['mail_header_gradient_from'] ?? '#0f172a',
            'headerTo' => $settings['mail_header_gradient_to'] ?? '#1580c6',
            'headerAccent' => $settings['mail_header_accent'] ?? '#d7f171',
            'headerText' => $settings['mail_header_text_color'] ?? '#ffffff',
            'footerBg' => $settings['mail_footer_bg'] ?? '#f8fafc',
            'footerText' => $settings['mail_footer_text_color'] ?? '#64748b',
            'linkColor' => $settings['mail_link_color'] ?? '#0b6fb8',
        ];
    }
}
