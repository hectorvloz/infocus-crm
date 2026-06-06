<?php

namespace App\Http\Controllers;

use App\Repositories\FileStore;
use App\Support\TemplateMail;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CorreoController extends Controller
{
    private FileStore $templatesStore;
    private FileStore $settingsStore;
    private FileStore $historyStore;
    private FileStore $clientsStore;

    public function __construct()
    {
        $this->templatesStore = new FileStore('email_templates.json');
        $this->settingsStore = new FileStore('settings.json');
        $this->historyStore = new FileStore('email_history.json');
        $this->clientsStore = new FileStore('clientes.json');
    }

    public function index(): View
    {
        $settings = $this->settingsStore->find('settings') ?: [];
        $allTemplates = collect($this->templatesStore->all());
        $hostingHtml = <<<HTML
<h1>Hola {cliente},</h1>
<p>Te compartimos los datos de tu servicio contratado.</p>
<hr>
<h2>Detalle del servicio</h2>
<p><strong>Plan Hosting:</strong> WP2</p>
<p><strong>Dominio asociado:</strong> cuchillosyarte.com</p>
<p><strong>Valor del primer pago:</strong> \$220.000,00</p>
<p><strong>Valor recurrente:</strong> \$220.000,00</p>
<p><strong>Ciclo de facturación:</strong> Anual</p>
<p><strong>Próxima fecha de vencimiento:</strong> 17/03/2027</p>
<hr>
<h2>Acceso al panel de control (cPanel)</h2>
<p><strong>URL de acceso:</strong> http://www.cuchillosyarte.com/cpanel</p>
<p><strong>Usuario:</strong> cuchillo</p>
<p><strong>Contraseña:</strong> P#dd9(9yA27BKe</p>
<p>Saludos,<br>{empresa}</p>
HTML;

        $hostingTemplate = $allTemplates->first(fn ($tpl) => ($tpl['system_key'] ?? '') === 'hosting_domain_purchase');
        if (!$hostingTemplate) {
            $this->templatesStore->create([
                'system_key' => 'hosting_domain_purchase',
                'system_seed_version' => 2,
                'name' => 'Compra hosting/dominio',
                'subject' => 'Confirmación de compra de hosting y dominio - {empresa}',
                'body' => $hostingHtml,
            ]);
            $allTemplates = collect($this->templatesStore->all());
        } elseif ((int) ($hostingTemplate['system_seed_version'] ?? 0) < 2) {
            $this->templatesStore->update((string) $hostingTemplate['id'], [
                'system_seed_version' => 2,
                'body' => $hostingHtml,
            ]);
            $allTemplates = collect($this->templatesStore->all());
        }

        $customTemplates = $allTemplates
            ->sortByDesc('updated_at')
            ->values()
            ->all();

        $history = collect($this->historyStore->all())
            ->sortByDesc('created_at')
            ->values()
            ->take(200)
            ->all();
        $cronStatus = $this->cronStatus();
        $clients = collect($this->clientsStore->all())
            ->map(function (array $c) {
                $email = trim((string) ($c['contacto_email'] ?? $c['email'] ?? ''));
                return [
                    'id' => (string) ($c['id'] ?? ''),
                    'name' => (string) ($c['empresa'] ?? $c['nombre'] ?? $c['contacto_nombre'] ?? 'Cliente'),
                    'email' => $email,
                ];
            })
            ->filter(fn ($c) => $c['email'] !== '')
            ->sortBy('name')
            ->values()
            ->all();

        return view('correo.index', [
            'settings' => $settings,
            'customTemplates' => $customTemplates,
            'history' => $history,
            'clients' => $clients,
            'cronStatus' => $cronStatus,
        ]);
    }

    public function verifyCron(): RedirectResponse
    {
        $cronStatus = $this->cronStatus();
        if (!$cronStatus['last_run_at']) {
            return back()->with('error', 'No hay señal del cron todavía. Verifica que exista el cron de cPanel ejecutando schedule:run cada minuto.');
        }

        if ($cronStatus['is_ok']) {
            return back()->with('ok', 'Cron activo. Última ejecución detectada: '.$cronStatus['last_run_human'].'.');
        }

        return back()->with('error', 'Cron atrasado. Última ejecución detectada: '.$cronStatus['last_run_human'].'. Revisa la tarea cron en cPanel.');
    }

    private function cronStatus(): array
    {
        $lastRunAt = null;
        $lastRunHuman = 'Sin datos';
        $isOk = false;
        $lagMinutes = null;

        try {
            if (Storage::exists('cron_status.json')) {
                $raw = (string) Storage::get('cron_status.json');
                $json = json_decode($raw, true);
                $candidate = is_array($json) ? (string) ($json['last_run_at'] ?? '') : '';
                if ($candidate !== '') {
                    $dt = Carbon::parse($candidate);
                    $lagMinutes = $dt->diffInMinutes(now());
                    $lastRunAt = $dt->toDateTimeString();
                    $lastRunHuman = $dt->format('d/m/Y H:i:s');
                    $isOk = $lagMinutes <= 5;
                }
            }
        } catch (\Throwable) {
            // no-op
        }

        return [
            'last_run_at' => $lastRunAt,
            'last_run_human' => $lastRunHuman,
            'lag_minutes' => $lagMinutes,
            'is_ok' => $isOk,
        ];
    }

    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'to' => 'required|string',
            'subject' => 'required|string|max:180',
            'body' => 'required|string',
        ]);

        $to = collect(preg_split('/[,\n;]+/', (string) $data['to']))
            ->map(fn ($mail) => trim((string) $mail))
            ->filter(fn ($mail) => filter_var($mail, FILTER_VALIDATE_EMAIL))
            ->values()
            ->all();

        if (empty($to)) {
            return back()->withErrors(['to' => 'Debes ingresar al menos un correo válido.'])->withInput();
        }

        $settings = $this->settingsStore->find('settings') ?: [];
        $firstRecipient = strtolower((string) ($to[0] ?? ''));
        $client = collect($this->clientsStore->all())->first(function (array $c) use ($firstRecipient) {
            $email = strtolower(trim((string) ($c['contacto_email'] ?? $c['email'] ?? '')));
            return $email !== '' && $email === $firstRecipient;
        }) ?: [];

        $clientName = (string) (
            $client['contacto_nombre']
            ?? $client['nombre']
            ?? $client['empresa']
            ?? (str_contains($firstRecipient, '@') ? ucfirst(strtolower(strstr($firstRecipient, '@', true) ?: 'Cliente')) : 'Cliente')
        );

        $vars = [
            'empresa' => (string) ($settings['company_name'] ?? 'Infocus CRM'),
            'fecha' => now()->format('d/m/Y'),
            'usuario' => (string) (auth()->user()->name ?? session('user.name') ?? 'Equipo'),
            'cliente' => $clientName,
            'cliente_email' => (string) ($client['contacto_email'] ?? $client['email'] ?? ($to[0] ?? '')),
            'cliente_telefono' => (string) ($client['contacto_telefono'] ?? $client['telefono'] ?? ''),
            'cliente_nit' => (string) ($client['nit'] ?? $client['identificacion'] ?? ''),
            'cliente_direccion' => (string) ($client['direccion'] ?? ''),
        ];

        $subject = TemplateMail::tokenReplace((string) $data['subject'], $vars);
        $body = nl2br(TemplateMail::tokenReplace((string) $data['body'], $vars));

        TemplateMail::send($to, $subject, $body, [
            'source' => 'correo_manual',
            'sent_by' => (string) (auth()->user()->email ?? session('user.email') ?? 'sistema'),
            'sent_by_name' => (string) (auth()->user()->name ?? session('user.name') ?? 'Sistema'),
        ]);

        return back()->with('ok', 'Correo enviado correctamente.');
    }

    public function storeTemplate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'subject' => 'required|string|max:180',
            'body' => 'required|string',
        ]);

        $this->templatesStore->create([
            'name' => trim((string) $data['name']),
            'subject' => (string) $data['subject'],
            'body' => (string) $data['body'],
        ]);

        return back()->with('ok', 'Plantilla guardada.');
    }

    public function updateTemplate(Request $request, string $id): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'subject' => 'required|string|max:180',
            'body' => 'required|string',
        ]);

        $updated = $this->templatesStore->update($id, [
            'name' => trim((string) $data['name']),
            'subject' => (string) $data['subject'],
            'body' => (string) $data['body'],
        ]);

        if (!$updated) {
            return back()->withErrors(['template' => 'No se encontró la plantilla.']);
        }

        return back()->with('ok', 'Plantilla actualizada.');
    }

    public function destroyTemplate(string $id): RedirectResponse
    {
        $this->templatesStore->delete($id);
        return back()->with('ok', 'Plantilla eliminada.');
    }
}
