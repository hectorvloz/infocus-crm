@php
    $company = trim((string) ($settings['company_name'] ?? '')) ?: 'Estudio Indigo';
    $taxLabel = trim((string) ($settings['company_tax_label'] ?? '')) ?: 'NIT';
    $taxId = trim((string) ($settings['company_tax_id'] ?? ''));
    $city = trim(collect([$settings['company_city'] ?? null, $settings['company_country'] ?? null])->filter()->implode(', '));
    $phone = trim(($settings['company_phone_code'] ?? '') . ' ' . ($settings['company_phone_number'] ?? ''));
    $web = trim((string) ($settings['company_website'] ?? ''));
    $email = trim((string) ($settings['email_from'] ?? ''));
    $clientName = (string) ($cliente['empresa'] ?? ($contrato['cliente_nombre'] ?? ''));
    $issued = !empty($contrato['created_at']) ? \Illuminate\Support\Carbon::parse($contrato['created_at'])->setTimezone('America/Bogota')->locale('es')->translatedFormat('j \\d\\e F \\d\\e Y') : '';
    $estado = $contrato['estado'] ?? 'Borrador';
    $estadoLabel = $estado === 'Firmado' ? 'Firmado' : ($estado === 'Enviado' ? 'Pendiente de firma' : 'Borrador');

    // Mismo sistema visual de la factura: encabezado oscuro con el logo y acento de marca.
    $dark = preg_match('/^#[0-9a-f]{6}$/i', (string) ($settings['invoice_color_header'] ?? '')) ? $settings['invoice_color_header'] : '#0f172a';
    $accent = preg_match('/^#[0-9a-f]{6}$/i', (string) ($settings['invoice_color_footer'] ?? '')) ? $settings['invoice_color_footer'] : '#f1c844';
    $logoMm = max(22, min(36, (int) ($settings['invoice_logo_size'] ?? 30)));
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $contrato['titulo'] }}</title>
    <style>
        @page { margin: 132px 60px 84px 60px; }
        * { box-sizing: border-box; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 9.4pt; line-height: 1.62; color: #1f2937; }

        /* Membrete en cada página: franja oscura con el logo, como la factura */
        .letterhead { position: fixed; top: -132px; left: -60px; right: -60px; height: 92px; background: {{ $dark }}; }
        .letterhead table { width: 100%; border-collapse: collapse; }
        .letterhead td { padding: 13px 60px 0; vertical-align: middle; }
        .letterhead td.right { text-align: right; padding-left: 0; }
        .letterhead .logo { width: {{ $logoMm }}mm; height: auto; }
        .letterhead .company { font-size: 15pt; font-weight: bold; color: #fff; }
        .letterhead .doc { font-size: 7pt; font-weight: bold; letter-spacing: .2em; color: {{ $accent }}; }
        .letterhead .num { font-size: 12pt; font-weight: bold; color: #fff; letter-spacing: .04em; }
        .letterhead .sub { font-size: 7pt; color: #9ca3af; }
        .letterhead-rule { position: fixed; top: -40px; left: -60px; right: -60px; height: 4px; background: {{ $accent }}; }

        .footer { position: fixed; bottom: -84px; left: -60px; right: -60px; height: 44px; background: {{ $accent }}; }
        .footer table { width: 100%; border-collapse: collapse; }
        .footer td { padding: 15px 60px 0; vertical-align: middle; font-size: 7pt; color: #111827; }
        .footer td.right { text-align: right; padding-left: 0; font-weight: bold; }
        .footer .pagenum:before { content: counter(page); }

        /* Portada */
        .eyebrow { font-size: 7.2pt; font-weight: bold; letter-spacing: .22em; text-transform: uppercase; color: #6b7280; }
        h1.title { font-size: 19pt; line-height: 1.22; color: #111827; margin: 6px 0 10px; }
        .accent-bar { width: 56px; height: 4px; background: {{ $accent }}; margin-bottom: 12px; }
        .status { display: inline-block; font-size: 7.2pt; font-weight: bold; letter-spacing: .06em; text-transform: uppercase; padding: 3px 10px; border-radius: 10px; background: #f3f4f6; color: #374151; }
        .status.firmado { background: #111827; color: {{ $accent }}; }

        .parties { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .gap { width: 2%; padding: 0 !important; border: 0 !important; background: none !important; }
        .parties td.card { width: 49%; vertical-align: top; background: #f8fafc; border-left: 3px solid {{ $accent }}; padding: 13px 15px 12px; }
        .parties .label { font-size: 6.8pt; font-weight: bold; letter-spacing: .18em; text-transform: uppercase; color: #9ca3af; margin-bottom: 5px; }
        .parties .name { font-size: 11pt; font-weight: bold; color: #111827; margin-bottom: 3px; }
        .parties .line { font-size: 8pt; color: #4b5563; line-height: 1.55; }

        .facts { width: 100%; border-collapse: collapse; margin: 12px 0 22px; }
        .facts td.box { vertical-align: top; border: 1px solid #e5e7eb; padding: 10px 14px; }
        .facts .k { font-size: 6.8pt; font-weight: bold; letter-spacing: .16em; text-transform: uppercase; color: #9ca3af; }
        .facts .v { font-size: 9.6pt; font-weight: bold; color: #111827; margin-top: 3px; }
        .facts .v.big { font-size: 12pt; }
        .facts .note { font-size: 7pt; color: #6b7280; }

        .section-label { font-size: 7pt; font-weight: bold; letter-spacing: .22em; text-transform: uppercase; color: #6b7280; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px; margin-bottom: 12px; }

        /* Cuerpo del contrato */
        .body h1 { display: none; }
        .body h2 { font-size: 9.2pt; font-weight: bold; text-transform: uppercase; letter-spacing: .05em; color: #111827; margin: 18px 0 6px; padding-left: 9px; border-left: 3px solid {{ $accent }}; page-break-after: avoid; }
        .body h3 { font-size: 9.4pt; font-weight: bold; color: #111827; margin: 12px 0 4px; page-break-after: avoid; }
        .body p { margin: 0 0 7px; text-align: justify; }
        .body ol, .body ul { margin: 0 0 9px 0; padding-left: 20px; }
        .body li { margin-bottom: 3px; }
        .body strong { color: #111827; }
        .body .ql-align-center { text-align: center; }
        .body .ql-align-right { text-align: right; }
        .body .ql-indent-1 { padding-left: 24px; }
        .body .ql-indent-2 { padding-left: 48px; }

        /* Firmas */
        .sign-wrap { margin-top: 30px; page-break-inside: avoid; }
        .signatures { width: 100%; border-collapse: collapse; }
        .signatures td.card { width: 49%; vertical-align: top; border: 1px solid #e5e7eb; padding: 14px 16px 13px; }
        .sig-role { font-size: 6.8pt; font-weight: bold; letter-spacing: .18em; text-transform: uppercase; color: #9ca3af; }
        .sig-space { height: 54px; border-bottom: 1px solid #111827; margin: 6px 0 8px; }
        .sig-script { font-size: 16pt; font-style: italic; color: #1e3a8a; padding-top: 20px; }
        .sig-pending { font-size: 7.6pt; color: #9ca3af; padding-top: 32px; }
        .sig-who { font-size: 9.4pt; font-weight: bold; color: #111827; }
        .sig-id { font-size: 7.8pt; color: #4b5563; }
        .sig-evidence { margin-top: 7px; padding-top: 7px; border-top: 1px dashed #e5e7eb; font-size: 6.9pt; color: #6b7280; line-height: 1.5; }

        .seal { margin-top: 14px; background: #111827; color: #d1d5db; padding: 11px 16px; font-size: 7pt; line-height: 1.55; page-break-inside: avoid; }
        .seal strong { color: {{ $accent }}; }
    </style>
</head>
<body>
    <div class="letterhead">
        <table>
            <tr>
                <td>
                    @if($logo)
                        <img src="{{ $logo }}" class="logo" alt="{{ $company }}">
                    @else
                        <span class="company">{{ $company }}</span>
                    @endif
                </td>
                <td class="right">
                    <div class="doc">CONTRATO</div>
                    <div class="num">{{ $numero }}</div>
                    <div class="sub">{{ $company }}@if($taxId) · {{ $taxLabel }} {{ $taxId }}@endif</div>
                </td>
            </tr>
        </table>
    </div>
    <div class="letterhead-rule"></div>

    <div class="footer">
        <table>
            <tr>
                <td>{{ $company }}@if($city) · {{ $city }}@endif @if($web) · {{ $web }}@endif @if($email) · {{ $email }}@endif</td>
                <td class="right">{{ $numero }} · Página <span class="pagenum"></span></td>
            </tr>
        </table>
    </div>

    <div class="eyebrow">Contrato {{ $numero }}</div>
    <h1 class="title">{{ $contrato['titulo'] }}</h1>
    <div class="accent-bar"></div>
    <span class="status {{ $estado === 'Firmado' ? 'firmado' : '' }}">{{ $estadoLabel }}</span>

    <table class="parties">
        <tr>
            <td class="card">
                <div class="label">El contratista</div>
                <div class="name">{{ $company }}</div>
                @if($taxId)<div class="line">{{ $taxLabel }} {{ $taxId }}</div>@endif
                @if($city)<div class="line">{{ $city }}</div>@endif
                @if($email)<div class="line">{{ $email }}</div>@endif
                @if(trim($phone))<div class="line">{{ $phone }}</div>@endif
            </td>
            <td class="gap"></td>
            <td class="card">
                <div class="label">El contratante</div>
                <div class="name">{{ $clientName }}</div>
                @if(!empty($cliente['nit']))<div class="line">NIT/C.C. {{ $cliente['nit'] }}</div>@endif
                @if(!empty($cliente['contacto_nombre']))<div class="line">{{ $cliente['contacto_nombre'] }}</div>@endif
                @if(!empty($cliente['ciudad']))<div class="line">{{ $cliente['ciudad'] }}</div>@endif
                @if(!empty($cliente['contacto_email']))<div class="line">{{ $cliente['contacto_email'] }}</div>@endif
            </td>
        </tr>
    </table>

    <table class="facts">
        <tr>
            <td class="box" style="width: {{ $valor ? '28%' : '49%' }};">
                <div class="k">Fecha de emisión</div>
                <div class="v">{{ $issued }}</div>
            </td>
            <td class="gap"></td>
            <td class="box" style="width: {{ $valor ? '28%' : '49%' }};">
                <div class="k">Proyecto</div>
                <div class="v">{{ $contrato['proyecto_nombre'] ?? '—' }}</div>
            </td>
            @if($valor)
                <td class="gap"></td>
                <td class="box" style="width: 40%;">
                    <div class="k">Valor del contrato</div>
                    <div class="v big">{{ $valor }}</div>
                    <div class="note">Más los impuestos que apliquen</div>
                </td>
            @endif
        </tr>
    </table>

    <div class="section-label">Cláusulas</div>
    <div class="body">{!! $contrato['contenido'] !!}</div>

    <div class="sign-wrap">
        <div class="section-label">Firmas</div>
        <table class="signatures">
            <tr>
                <td class="card">
                    <div class="sig-role">El contratista</div>
                    <div class="sig-space">
                        @if($firmaEmpresa)
                            <div class="sig-script">{{ $firmaEmpresa['nombre'] ?? $company }}</div>
                        @else
                            <div class="sig-pending">Pendiente de firma</div>
                        @endif
                    </div>
                    <div class="sig-who">{{ $company }}</div>
                    @if($taxId)<div class="sig-id">{{ $taxLabel }} {{ $taxId }}</div>@endif
                    @if($firmaEmpresa)
                        <div class="sig-evidence">Firmado electrónicamente por {{ $firmaEmpresa['nombre'] ?? '' }}<br>{{ \App\Support\ContractDocuments::formatDate($firmaEmpresa['fecha'] ?? null) }}</div>
                    @endif
                </td>
                <td class="gap"></td>
                <td class="card">
                    <div class="sig-role">El contratante</div>
                    <div class="sig-space">
                        @if($firmaCliente)
                            <div class="sig-script">{{ $firmaCliente['nombre'] ?? '' }}</div>
                        @else
                            <div class="sig-pending">Pendiente de firma</div>
                        @endif
                    </div>
                    <div class="sig-who">{{ $clientName }}</div>
                    @if(!empty($cliente['nit']))<div class="sig-id">NIT/C.C. {{ $cliente['nit'] }}</div>@endif
                    @if($firmaCliente)
                        <div class="sig-evidence">Firmado electrónicamente por {{ $firmaCliente['nombre'] ?? '' }}@if(!empty($firmaCliente['documento'])) · Doc. {{ $firmaCliente['documento'] }}@endif<br>{{ \App\Support\ContractDocuments::formatDate($firmaCliente['fecha'] ?? null) }}@if(!empty($firmaCliente['ip'])) · IP {{ $firmaCliente['ip'] }}@endif</div>
                    @endif
                </td>
            </tr>
        </table>

        @if($firmaCliente || $firmaEmpresa)
            <div class="seal">
                <strong>Documento firmado electrónicamente</strong> conforme a la Ley 527 de 1999 y el Decreto 2364 de 2012. Código del documento: <strong>{{ $numero }}</strong>.
                @if(!empty($firmaCliente['hash_contenido']))<br>Huella del contenido firmado (SHA-256): {{ $firmaCliente['hash_contenido'] }}@endif
            </div>
        @endif
    </div>
</body>
</html>
