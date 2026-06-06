<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $subjectLine }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f4f7fb;
            color: #1e293b;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            width: 100% !important;
        }

        table {
            border-spacing: 0;
            border-collapse: collapse;
        }

        .shell {
            width: 100%;
            background: radial-gradient(circle at top, #e8f4ff 0%, #f4f7fb 48%, #eef2f7 100%);
            padding: 32px 12px;
        }

        .card {
            width: 100%;
            max-width: 640px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #dbe4f0;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 18px 48px rgba(15, 23, 42, 0.08);
        }

        .card-wrap {
            width: 100%;
            max-width: 640px;
            margin: 0 auto;
        }

        .hero {
            background: linear-gradient(135deg, #0f172a 0%, #0f3b66 52%, #1580c6 100%);
            padding: 28px 32px;
            color: #ffffff;
        }

        .brand-row {
            display: table;
            width: 100%;
        }

        .brand-copy,
        .brand-logo {
            display: table-cell;
            vertical-align: middle;
        }

        .brand-copy {
            width: 100%;
        }

        .eyebrow {
            display: inline-block;
            font-size: 11px;
            line-height: 1;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #d7f171;
            font-weight: 700;
            margin-bottom: 14px;
        }

        .brand-name {
            margin: 0;
            font-size: 28px;
            line-height: 1.15;
            font-weight: 700;
            color: #ffffff;
        }

        .subject {
            margin: 10px 0 0;
            font-size: 15px;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.84);
        }

        .logo {
            width: auto;
            max-width: 210px;
            max-height: 78px;
            display: block;
            margin-left: 20px;
            background: transparent;
            padding: 0;
            border-radius: 0;
        }

        .body {
            padding: 34px 32px 20px;
        }

        .content {
            font-size: 16px;
            line-height: 1.45;
            color: #334155;
        }

        .content h1,
        .content h2,
        .content h3 {
            color: #0f172a;
            line-height: 1.2;
            margin: 0 0 10px;
        }

        .content p {
            margin: 0 0 6px;
        }

        .content a {
            color: var(--mail-link, #0b6fb8);
            font-weight: 600;
            word-break: break-word;
        }

        .content strong {
            color: #0f172a;
        }

        .content ul,
        .content ol {
            padding-left: 22px;
            margin: 0 0 8px;
        }

        .content img {
            max-width: 100% !important;
            height: auto !important;
        }

        .content table {
            width: 100% !important;
            max-width: 100% !important;
            table-layout: fixed;
        }

        .content th,
        .content td {
            word-break: break-word;
            overflow-wrap: anywhere;
            vertical-align: top;
        }

        .divider {
            height: 1px;
            background: linear-gradient(90deg, rgba(11, 111, 184, 0) 0%, rgba(11, 111, 184, 0.24) 20%, rgba(11, 111, 184, 0.24) 80%, rgba(11, 111, 184, 0) 100%);
            margin: 12px 0 0;
        }

        .footer {
            padding: 0;
        }

        .footer-card {
            border-radius: 0;
            padding: 14px 24px;
            font-size: 13px;
            line-height: 1.4;
            color: #ffffff;
        }

        .footer-simple {
            display: table;
            width: 100%;
        }

        .footer-left,
        .footer-right {
            display: table-cell;
            vertical-align: middle;
        }

        .footer-left {
            text-align: left;
        }

        .footer-right {
            text-align: right;
            white-space: nowrap;
        }

        .footer-social-link {
            display: inline-block;
            margin-left: 8px;
            text-decoration: none;
        }

        .footer-social-link img {
            width: 18px;
            height: 18px;
            display: inline-block;
            vertical-align: middle;
        }

        .footer-website {
            display: inline-block;
            color: #ffffff;
            font-size: 12px;
            letter-spacing: 0.16em;
            text-transform: lowercase;
            text-decoration: none;
            font-weight: 600;
        }

        .footer-note {
            margin-top: 8px;
            font-size: 11px;
            line-height: 1.5;
            opacity: 0.88;
        }

        .preheader {
            display: none;
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            mso-hide: all;
        }

        @media only screen and (max-width: 640px) {
            .shell {
                padding: 10px 0 !important;
            }

            .hero,
            .body {
                padding-left: 14px !important;
                padding-right: 14px !important;
            }

            .brand-row,
            .brand-copy,
            .brand-logo {
                display: block;
                width: 100%;
            }

            .brand-name {
                font-size: 24px;
            }

            .logo {
                margin: 16px 0 0;
                max-width: 180px;
            }

            .content {
                font-size: 15px;
            }

            .content th,
            .content td {
                font-size: 14px !important;
                line-height: 1.3 !important;
                padding-top: 8px !important;
                padding-bottom: 8px !important;
            }

            .footer-simple,
            .footer-left,
            .footer-right {
                display: block;
                width: 100%;
                text-align: left;
            }

            .footer-right {
                margin-top: 8px;
            }

            .footer-social-link {
                margin: 0 8px 0 0;
            }
        }
    </style>
</head>
<body>
    @php
        $theme = $mailTheme ?? [];
        $headerLabel = $theme['headerLabel'] ?? 'Mensaje automatizado';
        $headerFrom = $theme['headerFrom'] ?? '#0f172a';
        $headerTo = $theme['headerTo'] ?? '#1580c6';
        $headerAccent = $theme['headerAccent'] ?? '#d7f171';
        $headerText = $theme['headerText'] ?? '#ffffff';
        $footerBg = $theme['footerBg'] ?? '#f8fafc';
        $footerText = $theme['footerText'] ?? '#64748b';
        $linkColor = $theme['linkColor'] ?? '#0b6fb8';
        $footerCopy = !empty($footerNote)
            ? $footerNote
            : 'Este correo fue enviado automáticamente. Si no esperabas este mensaje puedes responder a este correo.';
        // Preferir logo embebido (CID) para mejor compatibilidad en clientes de correo.
        $resolvedLogoSrc = null;
        if (!empty($logoPath) && isset($message)) {
            try {
                $resolvedLogoSrc = $message->embed($logoPath);
            } catch (\Throwable $e) {
                $resolvedLogoSrc = null;
            }
        }
        if (empty($resolvedLogoSrc) && !empty($logoUrl)) {
            $resolvedLogoSrc = $logoUrl;
        }
    @endphp
    <div class="preheader">{{ $preheader }}</div>
    <table role="presentation" width="100%" class="shell" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" class="card-wrap" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td align="center">
                            <table role="presentation" width="100%" class="card" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td class="hero" style="background: linear-gradient(135deg, {{ $headerFrom }} 0%, {{ $headerTo }} 100%); color: {{ $headerText }};">
                            <div class="brand-row">
                                <div class="brand-copy">
                                    <div class="eyebrow" style="color: {{ $headerAccent }};">{{ $headerLabel }}</div>
                                    <h1 class="brand-name" style="color: {{ $headerText }};">{{ $brandName }}</h1>
                                    <p class="subject" style="color: {{ $headerText }}; opacity: 0.86;">{{ $subjectLine }}</p>
                                </div>
                                @if(!empty($resolvedLogoSrc))
                                    <div class="brand-logo">
                                        <img src="{{ $resolvedLogoSrc }}" alt="{{ $brandName }}" class="logo">
                                    </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="body">
                            <div class="content" style="--mail-link: {{ $linkColor }};">
                                {!! $content !!}
                            </div>
                            <div class="divider"></div>
                        </td>
                    </tr>
                    <tr>
                        <td class="footer">
                            <div class="footer-card" style="background: {{ $footerBg }}; color: {{ $footerText }};">
                                @php
                                    $iconColor = strtoupper(preg_replace('/[^A-Fa-f0-9]/', '', ltrim((string) $footerText, '#')));
                                    if (strlen($iconColor) !== 3 && strlen($iconColor) !== 6) {
                                        $iconColor = 'FFFFFF';
                                    }
                                    $socialIcons = [
                                        'instagram' => 'https://cdn.simpleicons.org/instagram/' . $iconColor,
                                        'facebook'  => 'https://cdn.simpleicons.org/facebook/' . $iconColor,
                                        'x'         => 'https://cdn.simpleicons.org/x/' . $iconColor,
                                        'linkedin'  => 'https://cdn.simpleicons.org/linkedin/' . $iconColor,
                                        'tiktok'    => 'https://cdn.simpleicons.org/tiktok/' . $iconColor,
                                        'youtube'   => 'https://cdn.simpleicons.org/youtube/' . $iconColor,
                                        'reddit'    => 'https://cdn.simpleicons.org/reddit/' . $iconColor,
                                        'whatsapp'  => 'https://cdn.simpleicons.org/whatsapp/' . $iconColor,
                                        'telegram'  => 'https://cdn.simpleicons.org/telegram/' . $iconColor,
                                    ];
                                    $websiteText = !empty($websiteUrl) ? preg_replace('#^https?://#', '', $websiteUrl) : strtolower($brandName) . '.com';
                                @endphp

                                <div class="footer-simple">
                                    <div class="footer-left">
                                        <a href="{{ $websiteUrl ?: '#' }}" class="footer-website" target="_blank" rel="noopener noreferrer" style="color: {{ $footerText }};">{{ $websiteText }}</a>
                                    </div>
                                    <div class="footer-right">
                                        @foreach($socialLinks as $net => $href)
                                            <a href="{{ $href }}" class="footer-social-link" target="_blank" rel="noopener noreferrer" title="{{ ucfirst($net) }}">
                                                <img src="{{ $socialIcons[$net] ?? ('https://cdn.simpleicons.org/link/' . $iconColor) }}" alt="{{ ucfirst($net) }}">
                                            </a>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="footer-note" style="color: {{ $footerText }};">{{ $footerCopy }}</div>
                            </div>
                        </td>
                    </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
