<?php

namespace App\Http\Controllers;

use App\Repositories\FileStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File as Fs;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Mail\GenericMail;
use App\Support\Ai\AiMemoryService;

class SettingsController extends Controller
{
    protected FileStore $store;
    protected FileStore $usersStore;

    /**
     * JSON datasets that can be exported/restored safely.
     */
    private const BACKUP_JSON_FILES = [
        'clientes.json',
        'facturas.json',
        'pagos.json',
        'recurring_invoice_runs.json',
        'cotizaciones.json',
        'proyectos.json',
        'tareas.json',
        'gastos.json',
        'contratos.json',
        'leads.json',
        'productos.json',
        'items.json',
        'proveedores.json',
        'gastos_config.json',
        'timelines.json',
        'documentos.json',
        'document_folders.json',
        'document_spaces.json',
        'mensajes.json',
        'notification_states.json',
        'scheduler_state.json',
        'cron_status.json',
        'portal_access_logs.json',
        'ai_action_logs.json',
        'ai_memories.json',
        'meeting_reminders.json',
        'reuniones.json',
        'email_templates.json',
        'email_history.json',
        'mis_notas.json',
        'mis_tareas_notas.json',
        'ai_chats.json',
        'settings.json',
        'payment_methods.json',
        'users.json',
        'roles.json',
        'team.json',
    ];


    public function __construct()
    {
        // En Laravel 12 se eliminó $this->middleware() del controlador base.
        // El check de rol se hace directo aquí; el middleware 'auth' ya corrió antes.
        $role = strtolower((string) (Auth::user()?->role ?? ''));
        if (!in_array($role, ['super_admin', 'admin'], true)) {
            abort(403);
        }

        $this->store = new FileStore('settings.json');
        $this->usersStore = new FileStore('users.json');
    }

    // --- General Settings ---
    public function edit()
    {
        $settings = $this->store->find('settings') ?: [];
        return view('settings.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'company_name' => 'nullable|string',
            'company_address' => 'nullable|string',
            'company_city' => 'nullable|string',
            'company_state' => 'nullable|string',
            'company_zip' => 'nullable|string',
            'company_country' => 'nullable|string',
            'company_website' => 'nullable|string|max:140',
            'company_timezone' => 'nullable|timezone:all',
            'base_currency' => 'nullable|string',
            'whatsapp_number' => 'nullable|string',
            'email_from' => 'nullable|email',
            'social_networks' => 'nullable|array',
            'social_networks.*' => 'nullable|string|in:instagram,facebook,x,linkedin,tiktok,youtube,reddit,whatsapp,telegram',
            'social_values' => 'nullable|array',
            'social_values.*' => 'nullable|string|max:200',
            'logo_large' => 'nullable|image|max:2048',
            'logo_small' => 'nullable|image|max:1024',
            'sidebar_use_company_logo' => 'nullable|boolean',
            'show_decimals' => 'nullable|boolean',
            'decimal_places' => 'nullable|numeric|min:0|max:4',
            'ui_theme' => 'nullable|string|in:lime,sky,violet,amber,rose,black',
        ]);

        $socialLinks = [];
        $networks = $request->input('social_networks', []);
        $values = $request->input('social_values', []);
        foreach ($networks as $idx => $network) {
            $network = trim((string) $network);
            $value = trim((string) ($values[$idx] ?? ''));
            if ($network === '' || $value === '') {
                continue;
            }
            $socialLinks[] = [
                'network' => $network,
                'value' => $value,
            ];
        }
        $data['social_links'] = $socialLinks;

        // Mantener compatibilidad con los campos legacy
        $legacyKeys = [
            'social_instagram',
            'social_facebook',
            'social_x',
            'social_linkedin',
            'social_tiktok',
            'social_youtube',
            'social_reddit',
            'social_whatsapp',
            'social_telegram',
        ];
        foreach ($legacyKeys as $legacy) {
            $data[$legacy] = '';
        }
        foreach ($socialLinks as $item) {
            $legacyKey = 'social_' . $item['network'];
            if (in_array($legacyKey, $legacyKeys, true)) {
                $data[$legacyKey] = $item['value'];
            }
        }
        
        $dir = public_path('uploads/branding');
        Fs::ensureDirectoryExists($dir);

        if ($request->hasFile('logo_large')) {
            $ext = strtolower((string) ($request->file('logo_large')->getClientOriginalExtension() ?: $request->file('logo_large')->guessExtension() ?: 'png'));
            $name = 'logo_large_' . time() . '.' . $ext;
            $request->file('logo_large')->move($dir, $name);
            $data['logo_large'] = '/uploads/branding/'.$name;
            $data['logo'] = $data['logo_large']; // Fallback/Backwards compatibility
        }

        if ($request->hasFile('logo_small')) {
            $ext = strtolower((string) ($request->file('logo_small')->getClientOriginalExtension() ?: $request->file('logo_small')->guessExtension() ?: 'png'));
            $name = 'logo_small_' . time() . '.' . $ext;
            $request->file('logo_small')->move($dir, $name);
            $data['logo_small'] = '/uploads/branding/'.$name;
            $data['favicon'] = $data['logo_small'];
        }

        $data['sidebar_use_company_logo'] = $request->boolean('sidebar_use_company_logo');
        
        $data['show_decimals'] = $request->boolean('show_decimals');
        if (array_key_exists('decimal_places', $data)) {
            $data['decimal_places'] = (int) $data['decimal_places'];
        }
        
        $this->saveSettings($data);
        return redirect()->route('settings.edit')->with('success', 'Información de la empresa actualizada.');
    }

    // --- SMTP Settings ---
    public function smtp()
    {
        $settings = $this->store->find('settings') ?: [];
        $users = collect($this->usersStore->all())
            ->filter(fn ($user) => ($user['active'] ?? true) && filter_var($user['email'] ?? '', FILTER_VALIDATE_EMAIL))
            ->sortBy(fn ($user) => Str::lower((string) ($user['name'] ?? $user['email'] ?? '')))
            ->values()
            ->all();
        return view('settings.smtp', compact('settings', 'users'));
    }

    public function updateSmtp(Request $request)
    {
        if ($request->input('scope') === 'invoices') {
            $data = $request->validate([
                'invoice_smtp_enabled' => 'nullable|boolean',
                'invoice_smtp_host' => 'nullable|string|max:190',
                'invoice_smtp_port' => 'nullable|numeric',
                'invoice_smtp_username' => 'nullable|string|max:190',
                'invoice_smtp_password' => 'nullable|string|max:500',
                'invoice_smtp_encryption' => 'nullable|in:tls,ssl,none',
                'invoice_mail_from_address' => 'nullable|email',
                'invoice_mail_from_name' => 'nullable|string|max:190',
            ]);

            $current = $this->store->find('settings') ?: [];
            $data['invoice_smtp_enabled'] = $request->boolean('invoice_smtp_enabled');
            if (!empty($data['invoice_smtp_password'])) {
                $data['invoice_smtp_password'] = 'ENC:' . Crypt::encryptString((string) $data['invoice_smtp_password']);
            } elseif (array_key_exists('invoice_smtp_password', $data) && empty($data['invoice_smtp_password'])) {
                $data['invoice_smtp_password'] = (string) ($current['invoice_smtp_password'] ?? '');
            }

            $this->saveSettings($data);
            return redirect()->route('settings.smtp', ['tab' => 'facturas'])->with('success', 'SMTP de facturas actualizado.');
        }

        if ($request->input('scope') === 'users') {
            $data = $request->validate([
                'user_smtp' => 'nullable|array',
                'user_smtp.*.email' => 'required|email',
                'user_smtp.*.username' => 'nullable|string|max:190',
                'user_smtp.*.password' => 'nullable|string|max:500',
            ]);

            $current = $this->store->find('settings') ?: [];
            $currentUserSmtp = is_array($current['user_smtp'] ?? null) ? $current['user_smtp'] : [];
            $userSmtp = [];

            foreach (($data['user_smtp'] ?? []) as $row) {
                $email = Str::lower(trim((string) ($row['email'] ?? '')));
                if ($email === '') {
                    continue;
                }
                $username = trim((string) ($row['username'] ?? ''));
                $password = (string) ($row['password'] ?? '');
                $previousPassword = (string) ($currentUserSmtp[$email]['password'] ?? '');

                $userSmtp[$email] = [
                    'email' => $email,
                    'username' => $username !== '' ? $username : $email,
                    'password' => $password !== '' ? ('ENC:' . Crypt::encryptString($password)) : $previousPassword,
                ];
            }

            $this->saveSettings(['user_smtp' => $userSmtp]);
            return redirect()->route('settings.smtp', ['tab' => 'usuarios'])->with('success', 'SMTP de usuarios actualizado.');
        }

        $data = $request->validate([
            'smtp_host' => 'nullable|string',
            'smtp_port' => 'nullable|numeric',
            'smtp_username' => 'nullable|string',
            'smtp_password' => 'nullable|string',
            'smtp_encryption' => 'nullable|in:tls,ssl,none',
            'mail_from_address' => 'nullable|email',
            'mail_from_name' => 'nullable|string',
        ]);

        $current = $this->store->find('settings') ?: [];
        if (!empty($data['smtp_password'])) {
            $data['smtp_password'] = 'ENC:' . Crypt::encryptString((string) $data['smtp_password']);
        } elseif (array_key_exists('smtp_password', $data) && empty($data['smtp_password'])) {
            $data['smtp_password'] = (string) ($current['smtp_password'] ?? '');
        }

        $this->saveSettings($data);
        return redirect()->route('settings.smtp')->with('success', 'Configuración SMTP actualizada.');
    }

    public function testSmtp(Request $request)
    {
        $request->validate([
            'test_email' => 'required|email',
            'email_type' => 'nullable|string|in:simple,factura,portal,reset,recordatorio,recurrente,recordatorio_servicio',
            'smtp_scope' => 'nullable|string|in:empresa,facturas',
        ]);

        $settings = $this->store->find('settings') ?: [];
        $scope = (string) $request->input('smtp_scope', 'empresa');
        $missingFields = [];
        $requiredFields = $scope === 'facturas'
            ? [
                'invoice_smtp_host' => 'Servidor SMTP de facturas',
                'invoice_smtp_username' => 'Usuario / Correo de facturas',
                'invoice_smtp_password' => 'Contraseña de facturas',
                'invoice_mail_from_address' => 'Correo del Remitente de facturas',
            ]
            : [
                'smtp_host'        => 'Servidor SMTP',
                'smtp_username'    => 'Usuario / Correo',
                'smtp_password'    => 'Contraseña',
                'mail_from_address'=> 'Correo del Remitente',
            ];

        foreach ($requiredFields as $field => $label) {
            if (blank($settings[$field] ?? null)) {
                $missingFields[] = $label;
            }
        }

        if ($scope === 'facturas' && empty($settings['invoice_smtp_enabled'])) {
            $missingFields[] = 'SMTP de facturas activo';
        }

        if (!empty($missingFields) || ($scope !== 'facturas' && config('mail.default') !== 'smtp')) {
            return redirect()->route('settings.smtp', ['tab' => $scope === 'facturas' ? 'facturas' : 'empresa'])->with(
                'error',
                'Completa la configuración SMTP antes de enviar una prueba. Faltan: ' . implode(', ', $missingFields ?: ['configuración SMTP activa'])
            );
        }

        $type = $request->email_type ?? 'simple';
        $appName = $settings['company_name'] ?? config('app.name', 'Infocus CRM');

        $templates = [
            'simple' => [
                'subject' => 'Prueba de Conexión SMTP — ' . $appName,
                'body'    =>
                    '<h2 style="margin:0 0 14px;font-size:24px;color:#0f172a;">✅ Tu correo ya está listo</h2>' .
                    '<p>Hicimos una prueba desde <strong>' . e($appName) . '</strong> y la entrega se completó correctamente.</p>' .
                    '<p>Eso significa que tu configuración SMTP está activa y ya puedes enviar notificaciones, accesos al portal y correos transaccionales desde la plataforma.</p>' .
                    '<p style="margin:20px 0 0;padding:12px 16px;border-radius:12px;background:#eff6ff;border:1px solid #bfdbfe;color:#1e3a8a;">Si recibiste este mensaje en tu bandeja principal, tu configuración está funcionando correctamente.</p>',
            ],
            'factura' => [
                'subject' => 'Nueva factura emitida — ' . $appName,
                'body'    =>
                    '<h2 style="margin:0 0 14px;font-size:24px;color:#0f172a;">🧾 Tienes una nueva factura</h2>' .
                    '<p>Hola, <strong>Cliente de Ejemplo</strong>.</p>' .
                    '<p>Hemos emitido la factura <strong>#FAC-0001</strong> a tu nombre por un valor de <strong>$1,500,000 COP</strong>.</p>' .
                    '<table style="width:100%;border-collapse:collapse;margin:16px 0;font-size:14px;">' .
                    '<tr style="background:#f8fafc;"><td style="padding:8px 12px;font-weight:600;">Fecha de emisión</td><td style="padding:8px 12px;">' . now()->format('d/m/Y') . '</td></tr>' .
                    '<tr><td style="padding:8px 12px;font-weight:600;">Fecha de vencimiento</td><td style="padding:8px 12px;">' . now()->addDays(30)->format('d/m/Y') . '</td></tr>' .
                    '<tr style="background:#f8fafc;"><td style="padding:8px 12px;font-weight:600;">Total</td><td style="padding:8px 12px;font-weight:700;color:#16a34a;">$1,500,000 COP</td></tr>' .
                    '</table>' .
                    '<p style="margin:20px 0 0;padding:12px 16px;border-radius:12px;background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;">Este es un correo de prueba generado desde los Ajustes SMTP de ' . e($appName) . '.</p>',
            ],
            'portal' => [
                'subject' => 'Acceso a tu portal de cliente — ' . $appName,
                'body'    =>
                    '<h2 style="margin:0 0 14px;font-size:24px;color:#0f172a;">🔑 Tu acceso al portal está listo</h2>' .
                    '<p>Hola, <strong>Cliente de Ejemplo</strong>.</p>' .
                    '<p>Hemos habilitado tu acceso al portal de clientes de <strong>' . e($appName) . '</strong> donde podrás consultar tus facturas, cotizaciones y el estado de tus proyectos.</p>' .
                    '<div style="margin:20px 0;padding:16px;border-radius:12px;background:#f8fafc;border:1px solid #e2e8f0;">' .
                    '<p style="margin:0 0 8px;font-size:13px;color:#64748b;">Tus credenciales de acceso:</p>' .
                    '<p style="margin:0;"><strong>Usuario:</strong> cliente@ejemplo.com</p>' .
                    '<p style="margin:4px 0 0;"><strong>Contraseña temporal:</strong> ••••••••</p>' .
                    '</div>' .
                    '<p style="margin:20px 0 0;padding:12px 16px;border-radius:12px;background:#eff6ff;border:1px solid #bfdbfe;color:#1e3a8a;">Este es un correo de prueba generado desde los Ajustes SMTP de ' . e($appName) . '.</p>',
            ],
            'reset' => [
                'subject' => 'Restablece tu contraseña — ' . $appName,
                'body'    =>
                    '<h2 style="margin:0 0 14px;font-size:24px;color:#0f172a;">🔒 Solicitud de restablecimiento</h2>' .
                    '<p>Recibimos una solicitud para restablecer la contraseña de tu cuenta en <strong>' . e($appName) . '</strong>.</p>' .
                    '<p>Si realizaste esta solicitud, haz clic en el botón de abajo para continuar:</p>' .
                    '<div style="margin:24px 0;text-align:center;">' .
                    '<a href="#" style="display:inline-block;padding:12px 28px;background:#0f172a;color:#ecfe88;border-radius:999px;font-weight:700;text-decoration:none;font-size:14px;">Restablecer contraseña</a>' .
                    '</div>' .
                    '<p style="font-size:13px;color:#64748b;">Este enlace expirará en 60 minutos. Si no solicitaste este cambio, ignora este mensaje.</p>' .
                    '<p style="margin:20px 0 0;padding:12px 16px;border-radius:12px;background:#fef9c3;border:1px solid #fde68a;color:#92400e;">Este es un correo de prueba generado desde los Ajustes SMTP de ' . e($appName) . '.</p>',
            ],
            'recordatorio' => [
                'subject' => 'Recordatorio de tarea pendiente — ' . $appName,
                'body'    =>
                    '<h2 style="margin:0 0 14px;font-size:24px;color:#0f172a;">📌 Tienes una tarea que vence pronto</h2>' .
                    '<p>Hola, este es un recordatorio automático sobre una tarea pendiente en <strong>' . e($appName) . '</strong>.</p>' .
                    '<div style="margin:20px 0;padding:16px;border-radius:12px;background:#f8fafc;border:1px solid #e2e8f0;">' .
                    '<p style="margin:0 0 6px;font-weight:700;color:#0f172a;">Revisión de propuesta comercial</p>' .
                    '<p style="margin:0;font-size:13px;color:#64748b;">Vence el <strong>' . now()->addDay()->format('d/m/Y') . '</strong> — Prioridad: Alta</p>' .
                    '</div>' .
                    '<p style="margin:20px 0 0;padding:12px 16px;border-radius:12px;background:#fef9c3;border:1px solid #fde68a;color:#92400e;">Este es un correo de prueba generado desde los Ajustes SMTP de ' . e($appName) . '.</p>',
            ],
            'recurrente' => [
                'subject' => 'Factura recurrente generada — ' . $appName,
                'body'    =>
                    '<h2 style="margin:0 0 14px;font-size:24px;color:#0f172a;">🔁 Factura recurrente emitida</h2>' .
                    '<p>Hola, <strong>Cliente de Ejemplo</strong>.</p>' .
                    '<p>Tu suscripción mensual ha generado automáticamente la factura <strong>#FAC-REC-001</strong>.</p>' .
                    '<table style="width:100%;border-collapse:collapse;margin:16px 0;font-size:14px;">' .
                    '<tr style="background:#f8fafc;"><td style="padding:8px 12px;font-weight:600;">Concepto</td><td style="padding:8px 12px;">Plan mensual — Servicio de ejemplo</td></tr>' .
                    '<tr><td style="padding:8px 12px;font-weight:600;">Período</td><td style="padding:8px 12px;">' . now()->format('F Y') . '</td></tr>' .
                    '<tr style="background:#f8fafc;"><td style="padding:8px 12px;font-weight:600;">Total</td><td style="padding:8px 12px;font-weight:700;color:#16a34a;">$500,000 COP</td></tr>' .
                    '</table>' .
                    '<p style="margin:20px 0 0;padding:12px 16px;border-radius:12px;background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;">Este es un correo de prueba generado desde los Ajustes SMTP de ' . e($appName) . '.</p>',
            ],
            'recordatorio_servicio' => [
                'subject' => 'Recordatorio de vencimiento de Servicio · Plan Basico Hosting / Proveedor — ' . $appName,
                'body'    =>
                    '<h2 style="margin:0 0 14px;font-size:24px;color:#0f172a;">⏰ Recordatorio de vencimiento de Servicio</h2>' .
                    '<p>Hola, <strong>Cliente de Ejemplo</strong>.</p>' .
                    '<p>Tu <strong>Servicio</strong> <strong>Plan Basico Hosting / Proveedor</strong> está próximo a vencerse. La factura recurrente asociada se enviará el <strong>' . now()->addDays(7)->format('d/m/Y') . '</strong>.</p>' .
                    '<div style="margin:16px 0;padding:14px 16px;border-radius:12px;background:#f8fafc;border:1px solid #e2e8f0;">' .
                    '<p style="margin:0 0 8px;font-size:13px;color:#64748b;font-weight:700;letter-spacing:.02em;text-transform:uppercase;">Descripción del servicio</p>' .
                    '<p style="margin:0;white-space:pre-line;color:#0f172a;">10 GB Espacio SSD NVMe' . "\n" . '500 GB de Tráfico / mes' . "\n" . '2 CPU / Garantizado' . "\n" . '4 GB de memoria RAM' . "\n" . 'IPv4 + IPv6 activado' . "\n" . '10 Cuentas E-mail</p>' .
                    '</div>' .
                    '<h3 style="margin:18px 0 10px;font-size:16px;color:#0f172a;">Items de la factura recurrente</h3>' .
                    '<table style="width:100%;border-collapse:collapse;font-size:14px;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;">' .
                    '<thead>' .
                    '<tr style="background:#f8fafc;color:#475569;">' .
                    '<th align="left" style="padding:10px 12px;border-bottom:1px solid #e2e8f0;">Descripción</th>' .
                    '<th align="right" style="padding:10px 12px;border-bottom:1px solid #e2e8f0;">Cantidad</th>' .
                    '<th align="right" style="padding:10px 12px;border-bottom:1px solid #e2e8f0;">Precio</th>' .
                    '<th align="right" style="padding:10px 12px;border-bottom:1px solid #e2e8f0;">Importe</th>' .
                    '</tr>' .
                    '</thead>' .
                    '<tbody>' .
                    '<tr>' .
                    '<td style="padding:10px 12px;border-bottom:1px solid #f1f5f9;">Plan Basico Hosting / Proveedor</td>' .
                    '<td align="right" style="padding:10px 12px;border-bottom:1px solid #f1f5f9;">1</td>' .
                    '<td align="right" style="padding:10px 12px;border-bottom:1px solid #f1f5f9;">$130.000 COP</td>' .
                    '<td align="right" style="padding:10px 12px;border-bottom:1px solid #f1f5f9;">$130.000 COP</td>' .
                    '</tr>' .
                    '</tbody>' .
                    '</table>' .
                    '<p style="margin:20px 0 0;padding:12px 16px;border-radius:12px;background:#fef9c3;border:1px solid #fde68a;color:#92400e;">Este es un correo de prueba del recordatorio de vencimiento de servicio/producto.</p>',
            ],
        ];

        $tpl = $templates[$type] ?? $templates['simple'];

        try {
            $mailer = $scope === 'facturas' ? \App\Support\TemplateMail::configureInvoiceMailer($settings) : config('mail.default');
            $from = $scope === 'facturas'
                ? \App\Support\TemplateMail::invoiceFrom($settings)
                : [
                    'address' => (string) ($settings['mail_from_address'] ?? config('mail.from.address')),
                    'name' => (string) ($settings['mail_from_name'] ?? config('mail.from.name')),
                ];
            $message = (new GenericMail($tpl['subject'], $tpl['body']))->from($from['address'], $from['name']);
            if (strtolower((string) $request->test_email) !== strtolower((string) $from['address'])) {
                $message->bcc((string) $from['address']);
            }
            Mail::mailer($mailer)->to($request->test_email)->send($message);
            return redirect()->route('settings.smtp', ['tab' => $scope === 'facturas' ? 'facturas' : 'empresa'])->with('success', 'Correo de prueba "' . $tpl['subject'] . '" enviado a ' . $request->test_email);
        } catch (\Exception $e) {
            return redirect()->route('settings.smtp', ['tab' => $scope === 'facturas' ? 'facturas' : 'empresa'])->with('error', 'Error al enviar: ' . $e->getMessage());
        }
    }

    // --- Invoice Settings ---
    public function invoice()
    {
        $settings = $this->store->find('settings') ?: [];
        return view('settings.invoice', compact('settings'));
    }

    public function updateInvoice(Request $request)
    {
        $data = $request->validate([
            'invoice_prefix' => 'nullable|string|max:10',
            'invoice_start_number' => 'nullable|numeric|min:1',
            'invoice_terms' => 'nullable|string',
            'invoice_footer' => 'nullable|string',
            'invoice_logo_size' => 'nullable|numeric|min:24|max:90',
            'invoice_logo_source' => 'nullable|in:company,custom',
            'invoice_logo' => 'nullable|image|max:2048',
            'invoice_logo_current' => 'nullable|string',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'tax_name' => 'nullable|string|max:20', // IVA, VAT, etc.
            'invoice_color_header' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'invoice_color_footer' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6})$/'],
        ]);

        if (array_key_exists('invoice_logo_size', $data)) {
            $data['invoice_logo_size'] = (int) round((float) $data['invoice_logo_size']);
        }

        $settings = $this->store->find('settings') ?: [];
        $logoLarge = $settings['logo_large'] ?? ($settings['logo'] ?? null);
        $source = $request->input('invoice_logo_source', 'company');
        if ($source === 'custom') {
            $customPath = null;
            if ($request->hasFile('invoice_logo')) {
                $dir = public_path('uploads/branding');
                Fs::ensureDirectoryExists($dir);
                $ext = strtolower((string) ($request->file('invoice_logo')->getClientOriginalExtension() ?: $request->file('invoice_logo')->guessExtension() ?: 'png'));
                $name = 'invoice_logo_' . time() . '.' . $ext;
                $request->file('invoice_logo')->move($dir, $name);
                $customPath = '/uploads/branding/' . $name;
            } else {
                $current = trim((string) $request->input('invoice_logo_current', ''));
                $customPath = $current !== '' ? $current : ($settings['invoice_logo'] ?? null);
            }
            $data['invoice_logo'] = $customPath ?: $logoLarge;
        } else {
            $data['invoice_logo'] = $logoLarge;
        }
        unset($data['invoice_logo_source'], $data['invoice_logo_current']);

        $this->saveSettings($data);
        return redirect()->route('settings.invoice')->with('success', 'Configuración de facturación actualizada.');
    }

    // --- Email Templates ---
    public function templates()
    {
        $settings = $this->store->find('settings') ?: [];
        return view('settings.templates', compact('settings'));
    }

    public function updateTemplates(Request $request)
    {
        $data = $request->validate([
            'template_invoice_subject' => 'nullable|string',
            'template_invoice_body' => 'nullable|string',
            'template_password_reset_subject' => 'nullable|string',
            'template_password_reset_body' => 'nullable|string',
            'template_welcome_subject' => 'nullable|string',
            'template_welcome_body' => 'nullable|string',
            'template_project_created_subject' => 'nullable|string',
            'template_project_created_body' => 'nullable|string',
            'template_weekly_hours_user_subject' => 'nullable|string',
            'template_weekly_hours_user_body' => 'nullable|string',
            'template_monthly_hours_user_subject' => 'nullable|string',
            'template_monthly_hours_user_body' => 'nullable|string',
            'template_payment_received_subject' => 'nullable|string',
            'template_payment_received_body' => 'nullable|string',
            'template_invoice_paid_subject' => 'nullable|string',
            'template_invoice_paid_body' => 'nullable|string',
            'template_invoice_due_subject' => 'nullable|string',
            'template_invoice_due_body' => 'nullable|string',
            'template_team_welcome_subject' => 'nullable|string',
            'template_team_welcome_body' => 'nullable|string',
            'template_role_permissions_changed_subject' => 'nullable|string',
            'template_role_permissions_changed_body' => 'nullable|string',
            'template_system_critical_alert_subject' => 'nullable|string',
            'template_system_critical_alert_body' => 'nullable|string',
            'template_meeting_scheduled_subject' => 'nullable|string',
            'template_meeting_scheduled_body' => 'nullable|string',
            'template_meeting_reminder_subject' => 'nullable|string',
            'template_meeting_reminder_body' => 'nullable|string',
            'template_lead_meet_scheduled_subject' => 'nullable|string',
            'template_lead_meet_scheduled_body' => 'nullable|string',
            'template_lead_meet_reminder_subject' => 'nullable|string',
            'template_lead_meet_reminder_body' => 'nullable|string',
            'mail_header_label' => 'nullable|string|max:80',
            'mail_header_gradient_from' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'mail_header_gradient_to' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'mail_header_accent' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'mail_header_text_color' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'mail_footer_note' => 'nullable|string|max:240',
            'mail_footer_bg' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'mail_footer_text_color' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'mail_link_color' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
        ]);
        
        // Backwards compatibility
        if ($request->has('template_invoice_subject')) $data['email_subject_invoice'] = $data['template_invoice_subject'];
        if ($request->has('template_invoice_body')) $data['email_body_invoice'] = $data['template_invoice_body'];

        $this->saveSettings($data);
        return redirect()->route('settings.templates')->with('success', 'Plantillas de correo actualizadas.');
    }

    // --- Backups ---
    public function backup()
    {
        return view('settings.backup');
    }

    public function downloadBackup(Request $request)
    {
        $types = $request->input('types', []);

        // Define mapping of types to files
        $map = [
            'clientes' => ['clientes.json'],
            'facturas' => ['facturas.json', 'pagos.json', 'recurring_invoice_runs.json'],
            'cotizaciones' => ['cotizaciones.json'],
            'correo' => ['email_templates.json', 'email_history.json'],
            'leads' => ['leads.json', 'mensajes.json'],
            'reuniones' => ['reuniones.json'],
            'proyectos' => [
                'proyectos.json',
                'tareas.json',
                'gastos.json',
                'contratos.json',
                'productos.json',
                'items.json',
                'proveedores.json',
                'gastos_config.json',
                'timelines.json',
                'documentos.json',
                'document_folders.json',
                'document_spaces.json',
            ],
            'ajustes' => ['settings.json', 'payment_methods.json', 'users.json', 'roles.json', 'team.json', 'notification_states.json', 'scheduler_state.json', 'cron_status.json', 'portal_access_logs.json'],
            'mis_notas' => ['mis_notas.json'],
            'notas_rapidas' => ['mis_tareas_notas.json'],
            'uploads' => ['uploads']
        ];

        // Direct access fallback
        if (empty($types)) {
            $types = array_keys($map);
        }

        $zipFile = storage_path('app/backup_'.date('Y-m-d_H-i-s').'.zip');
        $zip = new \ZipArchive();

        if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
            $manifest = [
                'created_at' => now()->toIso8601String(),
                'selected_types' => array_values((array) $types),
                'files' => [],
            ];

            foreach ($types as $type) {
                if (!isset($map[$type])) {
                    continue;
                }

                foreach ($map[$type] as $file) {
                    if ($file === 'uploads') {
                        $uploadDir = public_path('uploads');
                        if (is_dir($uploadDir)) {
                            $iterator = new \RecursiveIteratorIterator(
                                new \RecursiveDirectoryIterator($uploadDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                                \RecursiveIteratorIterator::LEAVES_ONLY
                            );
                            foreach ($iterator as $f) {
                                if (!$f->isDir()) {
                                    $filePath = $f->getRealPath();
                                    $relativePath = 'uploads/' . substr($filePath, strlen($uploadDir) + 1);
                                    $zip->addFile($filePath, $relativePath);
                                }
                            }
                        }
                    } elseif (str_ends_with($file, '.json')) {
                        $payload = $this->backupJsonPayload($file);
                        $zip->addFromString($file, json_encode($payload['data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                        $manifest['files'][$file] = [
                            'records' => $payload['records'],
                            'sources' => $payload['sources'],
                        ];
                    }
                }
            }

            $zip->addFromString('backup_manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            if ($zip->numFiles === 0) {
                $zip->addFromString('readme.txt', 'Este respaldo está vacío o no se encontraron los archivos seleccionados.');
            }

            $zip->close();
        }

        if (!file_exists($zipFile)) {
            return redirect()->back()->with('error', 'Error al generar el archivo de respaldo. Intenta de nuevo.');
        }

        return response()->download(
            $zipFile,
            basename($zipFile),
            [
                'Content-Type' => 'application/octet-stream',
                'Content-Disposition' => 'attachment; filename="' . basename($zipFile) . '"',
                'Content-Transfer-Encoding' => 'binary',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
            ]
        )->deleteFileAfterSend(true);
    }

    private function backupJsonPayload(string $file): array
    {
        $candidates = $this->backupJsonCandidates($file);
        $decodedSources = [];

        foreach ($candidates as $path) {
            if (!is_file($path)) {
                continue;
            }

            $raw = file_get_contents($path);
            $decoded = json_decode((string) $raw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                continue;
            }

            $decodedSources[] = [
                'path' => $path,
                'updated_at' => filemtime($path) ?: 0,
                'size' => filesize($path) ?: 0,
                'data' => $decoded,
            ];
        }

        if (empty($decodedSources)) {
            if (str_ends_with($file, '.json') && !Storage::exists($file)) {
                Storage::put($file, json_encode([]));
            }
            $fallback = Storage::exists($file) ? json_decode((string) Storage::get($file), true) : [];
            $fallback = is_array($fallback) ? $fallback : [];
            return [
                'data' => $fallback,
                'records' => $this->countBackupRecords($fallback),
                'sources' => [],
            ];
        }

        $merged = $this->mergeBackupDatasets(array_column($decodedSources, 'data'));

        return [
            'data' => $merged,
            'records' => $this->countBackupRecords($merged),
            'sources' => array_map(fn ($source) => [
                'path' => $source['path'],
                'records' => $this->countBackupRecords($source['data']),
                'size' => $source['size'],
                'updated_at' => date('c', (int) $source['updated_at']),
            ], $decodedSources),
        ];
    }

    private function backupJsonCandidates(string $file): array
    {
        $paths = [
            Storage::path($file),
            storage_path('app/private/' . $file),
            storage_path('app/' . $file),
            storage_path('app/public/app/private/' . $file),
        ];

        return array_values(array_unique(array_filter($paths)));
    }

    private function mergeBackupDatasets(array $datasets)
    {
        $datasets = array_values(array_filter($datasets, 'is_array'));
        if (empty($datasets)) {
            return [];
        }

        $allLists = collect($datasets)->every(fn ($dataset) => array_is_list($dataset));
        if (!$allLists) {
            usort($datasets, fn ($a, $b) => count((array) $b) <=> count((array) $a));
            return array_replace_recursive($datasets[0] ?? [], ...array_slice($datasets, 1));
        }

        $byKey = [];
        $order = [];
        foreach ($datasets as $dataset) {
            foreach ($dataset as $index => $row) {
                $key = is_array($row) && !empty($row['id'])
                    ? 'id:' . (string) $row['id']
                    : 'row:' . md5(json_encode($row));
                if (!array_key_exists($key, $byKey)) {
                    $order[] = $key;
                    $byKey[$key] = $row;
                    continue;
                }
                if (is_array($byKey[$key]) && is_array($row)) {
                    $byKey[$key] = array_replace_recursive($byKey[$key], $row);
                }
            }
        }

        return array_values(array_map(fn ($key) => $byKey[$key], $order));
    }

    private function countBackupRecords($data): int
    {
        return is_array($data) ? count($data) : 0;
    }

    public function restoreBackup(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file|mimes:zip,json|max:51200',
        ]);

        $file = $request->file('backup_file');
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'json') {
            $originalName = $file->getClientOriginalName();
            $normalized = strtolower(trim($originalName));
            $normalized = preg_replace('/\s+/', '', $normalized);
            $normalized = preg_replace('/\(\d+\)(?=\.json$)/', '', $normalized);

            $allowed = self::BACKUP_JSON_FILES;

            $targetName = null;
            foreach ($allowed as $name) {
                if ($normalized === $name || str_contains($normalized, $name)) {
                    $targetName = $name;
                    break;
                }
            }

            if (!$targetName) {
                return redirect()->route('settings.backup')->with('error', 'Nombre de archivo JSON no reconocido. Usa un nombre compatible (ej: clientes.json).');
            }

            $content = file_get_contents($file->path());
            $decoded = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return redirect()->route('settings.backup')->with('error', 'El archivo no contiene un JSON válido.');
            }

            $normalized = $this->normalizeRestoredDataset($targetName, $decoded);
            Storage::put($targetName, json_encode($normalized));
            return redirect()->route('settings.backup')->with('success', "Archivo $targetName restaurado correctamente.");
        }

        $zip = new \ZipArchive();
        if ($zip->open($file->path()) === TRUE) {
            $allowedJson = self::BACKUP_JSON_FILES;
            $restoredJsonFiles = [];
            $restoredUploads = 0;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $rawName = $zip->getNameIndex($i);
                if (!$rawName) {
                    continue;
                }

                $filename = str_replace('\\', '/', $rawName);
                $filename = ltrim($filename, './');

                // Skip directories and unsafe/system entries.
                if (str_ends_with($filename, '/')) {
                    continue;
                }
                if (str_contains($filename, '..')) {
                    continue;
                }
                if (str_starts_with($filename, '__MACOSX/') || str_contains($filename, '/__MACOSX/') || str_ends_with($filename, '.DS_Store')) {
                    continue;
                }

                $content = $zip->getFromIndex($i);
                if ($content === false) {
                    continue;
                }

                // Restore uploads even if ZIP has a root folder (e.g. backup_x/uploads/...).
                if (preg_match('#(^|/)uploads/#', $filename)) {
                    if (preg_match('/\.(php|phtml|phar|pl|py|rb|cgi|sh|exe)$/i', $filename)) {
                        continue;
                    }

                    $uploadRelative = preg_replace('#^.*?uploads/#', 'uploads/', $filename, 1);
                    $targetPath = public_path($uploadRelative);
                    Fs::ensureDirectoryExists(dirname($targetPath));
                    Fs::put($targetPath, $content);
                    $restoredUploads++;
                    continue;
                }

                // Restore known JSONs by basename, allowing ZIPs with nested root folder.
                if (str_ends_with(strtolower($filename), '.json')) {
                    $base = strtolower(basename($filename));
                    if (!in_array($base, $allowedJson, true)) {
                        continue;
                    }

                    $decoded = json_decode($content, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        continue;
                    }

                    $normalized = $this->normalizeRestoredDataset($base, $decoded);
                    Storage::put($base, json_encode($normalized));
                    $restoredJsonFiles[$base] = true;
                }
            }

            $zip->close();

            $restoredJsonCount = count($restoredJsonFiles);
            if ($restoredJsonCount === 0 && $restoredUploads === 0) {
                return redirect()->route('settings.backup')->with('error', 'El ZIP no contiene archivos compatibles para restaurar (JSON permitidos o carpeta uploads/).');
            }

            $jsonList = implode(', ', array_keys($restoredJsonFiles));
            $details = $restoredJsonCount > 0 ? " [{$jsonList}]" : '';
            return redirect()->route('settings.backup')->with('success', "Respaldo restaurado correctamente. JSON: {$restoredJsonCount}{$details}, archivos: {$restoredUploads}.");
        }

        return redirect()->route('settings.backup')->with('error', 'No se pudo abrir el archivo ZIP.');
    }

    private function normalizeRestoredDataset(string $filename, $decoded)
    {
        if (!is_array($decoded)) {
            return $decoded;
        }

        if ($filename === 'facturas.json') {
            return collect($decoded)->map(function ($factura) {
                if (!is_array($factura)) {
                    return $factura;
                }

                // Migración suave de items: asegurar compatibilidad con producto_id.
                if (isset($factura['items']) && is_array($factura['items'])) {
                    $factura['items'] = collect($factura['items'])->map(function ($item) {
                        if (!is_array($item)) {
                            return $item;
                        }
                        if (!array_key_exists('producto_id', $item)) {
                            $item['producto_id'] = null;
                        }
                        return $item;
                    })->values()->all();
                }

                // Migración suave de recurrencia con estructura nueva.
                if (isset($factura['recurrencia']) && is_array($factura['recurrencia'])) {
                    $rec = $factura['recurrencia'];
                    if (isset($rec['freq']) && !isset($rec['every_months'])) {
                        // formato legacy: mensual -> 1
                        $rec['every_months'] = 1;
                    }
                    if (!isset($rec['enabled'])) {
                        $rec['enabled'] = !empty($rec);
                    }
                    if (!isset($rec['day_of_month']) && !empty($factura['fecha'])) {
                        $rec['day_of_month'] = (int) date('j', strtotime((string) $factura['fecha']));
                    }
                    if (!isset($rec['next_send']) && !empty($rec['siguiente'])) {
                        $rec['next_send'] = $rec['siguiente'];
                    }
                    if (!isset($rec['last_sent_at'])) {
                        $rec['last_sent_at'] = null;
                    }
                    unset($rec['siguiente']);
                    $factura['recurrencia'] = $rec;
                }

                return $factura;
            })->values()->all();
        }

        return $decoded;
    }

    // --- Integrations ---
    public function integrations()
    {
        $settings = $this->store->find('settings') ?: [];
        $googleCalendars = [];
        $googleCalendarListError = null;

        if (!empty($settings['google_calendar_access_token']) || !empty($settings['google_calendar_refresh_token'])) {
            [$googleCalendars, $googleCalendarListError] = $this->fetchGoogleCalendarList($settings);
        }
        $timezones = \DateTimeZone::listIdentifiers();

        return view('settings.integrations', compact('settings', 'googleCalendars', 'googleCalendarListError', 'timezones'));
    }

    public function updateIntegrations(Request $request)
    {
        $data = $request->validate([
            'payment_gateway' => 'nullable|in:stripe,paypal,wompi',
            'stripe_mode' => 'nullable|in:test,live',
            'stripe_key' => 'nullable|string',
            'stripe_secret' => 'nullable|string',
            'stripe_currency' => 'nullable|string|size:3',
            'wompi_public_key' => 'nullable|string',
            'wompi_integrity_secret' => 'nullable|string',
            'wompi_mode' => 'nullable|in:test,live',
            'wompi_currency' => 'nullable|string|size:3',
            'google_analytics_id' => 'nullable|string',
            'google_calendar_enabled' => 'nullable|in:on,1,true',
            'google_calendar_id' => 'nullable|string',
            'google_calendar_embed_url' => 'nullable|string',
            'google_meet_enabled' => 'nullable|in:on,1,true',
            'google_meet_timezone' => 'nullable|string|max:120',
            'google_meet_default_duration' => 'nullable|integer|min:5|max:240',
            'google_meet_fallback_url' => 'nullable|url',
            'google_meet_notes' => 'nullable|string|max:1000',
            'paypal_client_id' => 'nullable|string',
            'paypal_secret' => 'nullable|string',
            'paypal_mode' => 'nullable|in:sandbox,live',
            'tinymce_api_key' => 'nullable|string',
        ]);
        // Encrypt sensitive secrets — only overwrite if a new non-empty value was submitted
        $current = $this->store->find('settings') ?: [];
        $secretFields = ['stripe_secret', 'paypal_secret', 'wompi_integrity_secret'];
        foreach ($secretFields as $field) {
            if (!empty($data[$field])) {
                // Only re-encrypt if user typed something new (not the masked placeholder)
                if ($data[$field] !== '••••••••') {
                    $data[$field] = 'ENC:' . Crypt::encryptString($data[$field]);
                } else {
                    // Keep existing encrypted value
                    $data[$field] = $current[$field] ?? '';
                }
            } elseif (array_key_exists($field, $data) && empty($data[$field])) {
                // Empty submitted = keep existing (don't clear the secret)
                $data[$field] = $current[$field] ?? '';
            }
        }
        $data['google_calendar_enabled'] = $request->boolean('google_calendar_enabled');
        $data['google_meet_enabled'] = $request->boolean('google_meet_enabled');
        $this->saveSettings($data);
        return redirect()->route('settings.integrations')->with('success', 'Integraciones actualizadas.');
    }

    public function googleCalendarConnect(Request $request)
    {
        $clientId = config('services.google_calendar.client_id');
        $clientSecret = config('services.google_calendar.client_secret');
        if (!$clientId || !$clientSecret) {
            return redirect()->route('settings.integrations')->with('error', 'Configura GOOGLE_CALENDAR_CLIENT_ID y GOOGLE_CALENDAR_CLIENT_SECRET en el archivo .env.');
        }
        $state = Str::random(32);
        $request->session()->put('google_calendar_oauth_state', $state);
        $redirectUri = config('services.google_calendar.redirect') ?: route('settings.integrations.google.callback');
        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', [
                'https://www.googleapis.com/auth/calendar.events',
                'https://www.googleapis.com/auth/calendar.calendarlist.readonly',
            ]),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ]);
        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.$query);
    }

    public function googleCalendarCallback(Request $request)
    {
        $state = $request->query('state');
        $code = $request->query('code');
        if (!$state || $state !== $request->session()->pull('google_calendar_oauth_state')) {
            return redirect()->route('settings.integrations')->with('error', 'Estado OAuth inválido.');
        }
        if (!$code) {
            return redirect()->route('settings.integrations')->with('error', 'No se recibió el código de autorización.');
        }
        $settings = $this->store->find('settings') ?: [];
        $clientId = config('services.google_calendar.client_id');
        $clientSecret = config('services.google_calendar.client_secret');
        $redirectUri = config('services.google_calendar.redirect') ?: route('settings.integrations.google.callback');
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]);
        if (!$response->ok()) {
            return redirect()->route('settings.integrations')->with('error', 'No se pudo conectar Google Calendar.');
        }
        $payload = $response->json();
        $settings['google_calendar_access_token'] = $payload['access_token'] ?? null;
        if (!empty($payload['refresh_token'])) {
            $settings['google_calendar_refresh_token'] = $payload['refresh_token'];
        }
        $settings['google_calendar_expires_at'] = now()->addSeconds((int) ($payload['expires_in'] ?? 0))->toISOString();
        $settings['google_calendar_enabled'] = true;
        $settings['google_meet_enabled'] = true;
        $this->store->update('settings', $settings);
        return redirect()->route('settings.integrations')->with('success', 'Google Calendar conectado correctamente.');
    }

    public function googleCalendarDisconnect()
    {
        $settings = $this->store->find('settings') ?: [];
        $settings['google_calendar_access_token'] = null;
        $settings['google_calendar_refresh_token'] = null;
        $settings['google_calendar_expires_at'] = null;
        $settings['google_calendar_enabled'] = false;
        $this->store->update('settings', $settings);
        return redirect()->route('settings.integrations')->with('success', 'Google Calendar desconectado.');
    }

    protected function fetchGoogleCalendarList(array $settings): array
    {
        $token = $this->getGoogleCalendarAccessToken($settings);
        if (!$token) {
            return [[], 'Vuelve a conectar Google Calendar para cargar tus calendarios.'];
        }

        $response = Http::withToken($token)->get('https://www.googleapis.com/calendar/v3/users/me/calendarList', [
            'minAccessRole' => 'writer',
        ]);

        if (!$response->ok()) {
            return [[], 'No se pudo cargar la lista de calendarios de Google.'];
        }

        $items = collect($response->json('items') ?? [])
            ->filter(fn ($calendar) => !empty($calendar['id']))
            ->map(fn ($calendar) => [
                'id' => (string) $calendar['id'],
                'summary' => (string) ($calendar['summary'] ?? $calendar['id']),
                'primary' => (bool) ($calendar['primary'] ?? false),
            ])
            ->sortBy([
                ['primary', 'desc'],
                ['summary', 'asc'],
            ])
            ->values()
            ->all();

        return [$items, null];
    }

    protected function getGoogleCalendarAccessToken(array $settings): ?string
    {
        $token = $settings['google_calendar_access_token'] ?? null;
        $expiresAt = $settings['google_calendar_expires_at'] ?? null;

        if ($token && $expiresAt && \Carbon\Carbon::parse($expiresAt)->subSeconds(60)->isFuture()) {
            return $token;
        }

        $refreshToken = $settings['google_calendar_refresh_token'] ?? null;
        $clientId = config('services.google_calendar.client_id') ?: ($settings['google_calendar_client_id'] ?? null);
        $clientSecret = config('services.google_calendar.client_secret') ?: $this->decryptSetting($settings['google_calendar_client_secret'] ?? null);
        if (!$refreshToken || !$clientId || !$clientSecret) {
            return $token;
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if (!$response->ok()) {
            return $token;
        }

        $payload = $response->json();
        $settings['google_calendar_access_token'] = $payload['access_token'] ?? $token;
        $settings['google_calendar_expires_at'] = now()->addSeconds((int) ($payload['expires_in'] ?? 0))->toISOString();
        $this->store->update('settings', $settings);

        return $settings['google_calendar_access_token'] ?? $token;
    }

    // --- Payment Methods ---
    public function paymentMethods()
    {
        $store = new FileStore('payment_methods.json');
        $methods = $store->all() ?: [];
        // Ensure array structure
        if (isset($methods['id'])) $methods = [$methods]; 
        // If empty, maybe seed some defaults?
        if (empty($methods)) {
            $methods = [
                ['id' => uniqid(), 'name' => 'Transferencia Bancaria', 'details' => 'Banco: X\nCuenta: 1234', 'active' => true],
                ['id' => uniqid(), 'name' => 'Efectivo', 'details' => 'Pago en oficina', 'active' => true],
            ];
            $store->save($methods);
        }
        return view('settings.payment_methods', compact('methods'));
    }

    public function updatePaymentMethods(Request $request)
    {
        $data = $request->validate([
            'methods' => 'array',
            'methods.*.name' => 'required|string',
            'methods.*.details' => 'nullable|string',
            'methods.*.active' => 'nullable|boolean',
        ]);

        $methods = [];
        if ($request->has('methods')) {
            foreach ($request->methods as $m) {
                $methods[] = [
                    'id' => $m['id'] ?? uniqid(),
                    'name' => $m['name'],
                    'details' => $m['details'] ?? '',
                    'active' => isset($m['active']) && $m['active'] == '1'
                ];
            }
        }

        $store = new FileStore('payment_methods.json');
        $store->save($methods);

        return redirect()->route('settings.payment_methods')->with('success', 'Formas de pago actualizadas.');
    }

    public function ai()
    {
        $settings = $this->store->find('settings') ?: [];
        $apiKeys = is_array($settings['ai_api_keys'] ?? null) ? $settings['ai_api_keys'] : [];
        $provider = (string) ($settings['ai_provider'] ?? 'gemini');
        $legacyKey = (string) ($settings['ai_api_key'] ?? '');
        $settings['ai_api_key_previews'] = [
            'gemini' => $this->maskedSecret((string) ($apiKeys['gemini'] ?? ($provider === 'gemini' ? $legacyKey : ''))),
            'openai' => $this->maskedSecret((string) ($apiKeys['openai'] ?? ($provider === 'openai' ? $legacyKey : ''))),
            'deepseek' => $this->maskedSecret((string) ($apiKeys['deepseek'] ?? ($provider === 'deepseek' ? $legacyKey : ''))),
        ];
        $settings['ai_api_key_preview'] = $settings['ai_api_key_previews'][$provider] ?? 'Sin configurar';
        $aiMemories = (new AiMemoryService())->groupedForUser();

        return view('settings.ai', compact('settings', 'aiMemories'));
    }

    public function updateAi(Request $request)
    {
        $data = $request->validate([
            'ai_enabled' => 'nullable|boolean',
            'ai_provider' => 'required|string|in:openai,gemini,deepseek',
            'ai_model' => 'nullable|string|max:120',
            'ai_api_key' => 'nullable|string|max:800',
            'ai_send_visible_context' => 'nullable|boolean',
            'ai_system_prompt' => 'nullable|string|max:3000',
        ]);

        $current = $this->store->find('settings') ?: [];

        $payload = [
            'ai_enabled' => $request->boolean('ai_enabled'),
            'ai_provider' => $data['ai_provider'],
            'ai_model' => trim((string) ($data['ai_model'] ?? 'auto')) ?: 'auto',
            'ai_temperature' => 0.4,
            'ai_send_visible_context' => $request->boolean('ai_send_visible_context', false),
            'ai_system_prompt' => trim((string) ($data['ai_system_prompt'] ?? '')),
        ];

        $apiKey = trim((string) ($data['ai_api_key'] ?? ''));
        $apiKeys = is_array($current['ai_api_keys'] ?? null) ? $current['ai_api_keys'] : [];
        if (empty($apiKeys[$data['ai_provider']] ?? '') && !empty($current['ai_api_key'] ?? '') && (string) ($current['ai_provider'] ?? '') === $data['ai_provider']) {
            $apiKeys[$data['ai_provider']] = (string) $current['ai_api_key'];
        }
        if ($apiKey !== '') {
            $apiKeys[$data['ai_provider']] = 'ENC:' . Crypt::encryptString($apiKey);
        }
        $payload['ai_api_keys'] = $apiKeys;
        $payload['ai_api_key'] = (string) ($apiKeys[$data['ai_provider']] ?? '');

        $this->saveSettings($payload);

        return redirect()->route('settings.ai')->with('success', 'Configuración de IA actualizada.');
    }

    public function updateAiMemory(Request $request, string $id)
    {
        $data = $request->validate([
            'text' => 'required|string|max:700',
        ]);

        $updated = (new AiMemoryService())->update($id, (string) $data['text']);
        if (! $updated) {
            return redirect()->route('settings.ai', ['tab' => 'memoria'])->with('error', 'No pude actualizar esa memoria.');
        }

        return redirect()->route('settings.ai', ['tab' => 'memoria'])->with('success', 'Memoria actualizada.');
    }

    public function deleteAiMemory(string $id)
    {
        (new AiMemoryService())->delete($id);

        return redirect()->route('settings.ai', ['tab' => 'memoria'])->with('success', 'Memoria eliminada.');
    }

    private function maskedSecret(string $value): string
    {
        if ($value === '') {
            return 'Sin configurar';
        }

        if (str_starts_with($value, 'ENC:')) {
            try {
                $value = Crypt::decryptString(substr($value, 4));
            } catch (\Throwable) {
                return 'Configurada';
            }
        }

        $prefix = Str::upper(Str::substr(trim($value), 0, 3));

        return $prefix !== '' ? $prefix . '••••' : 'Configurada';
    }

    // Helper to merge and save
    protected function saveSettings($data)
    {
        $current = $this->store->find('settings') ?: [];
        $payload = array_merge($current, $data);
        
        if ($current) {
            $this->store->update('settings', $payload);
        } else {
            $payload['id'] = 'settings';
            $this->store->create($payload);
        }
    }

    protected function decryptSetting(?string $value): string
    {
        if (!is_string($value) || $value === '') {
            return '';
        }
        if (str_starts_with($value, 'ENC:')) {
            try {
                return Crypt::decryptString(substr($value, 4));
            } catch (\Throwable) {
                return '';
            }
        }
        return $value;
    }
}
