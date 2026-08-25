<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\FileStore;
use App\Repositories\TimelineStore;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use ZipArchive;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use App\Mail\GenericMail;
use Illuminate\Support\Facades\Mail;
use App\Support\TemplateMail;
use App\Models\User;

class PortalController extends Controller
{
    protected FileStore $clientes;
    protected FileStore $facturas;
    protected FileStore $proyectos;
    protected FileStore $cotizaciones;
    protected FileStore $documentos;
    protected FileStore $documentFolders;
    protected FileStore $mensajes;
    protected FileStore $settings;
    protected FileStore $portalAccessLogs;
    protected TimelineStore $timeline;

    public function __construct()
    {
        $this->clientes = new FileStore('clientes.json');
        $this->facturas = new FileStore('facturas.json');
        $this->proyectos = new FileStore('proyectos.json');
        $this->cotizaciones = new FileStore('cotizaciones.json');
        $this->documentos = new FileStore('documentos.json');
        $this->documentFolders = new FileStore('document_folders.json');
        $this->mensajes = new FileStore('mensajes.json');
        $this->settings = new FileStore('settings.json');
        $this->portalAccessLogs = new FileStore('portal_access_logs.json');
        $this->timeline = new TimelineStore();
    }

    protected function getClient($id, $token)
    {
        $client = $this->clientes->find($id);
        if (!$client) abort(404);

        $expected = $this->portalTokenForClient((string) $id);
        $legacy = md5($id . 'crm_portal_secret');

        if (!hash_equals($expected, (string) $token) && !hash_equals($legacy, (string) $token)) {
            abort(403, 'Acceso denegado');
        }
        
        return $client;
    }

    protected function portalTokenForClient(string $clientId): string
    {
        return hash_hmac('sha256', $clientId, (string) config('app.key') . '|portal');
    }

    protected function getClientFromAuth(): array
    {
        $user = Auth::user();
        abort_unless($user, 401);
        abort_unless(($user->role ?? null) === 'client', 403);
        abort_unless(!empty($user->cliente_id), 403);

        $client = $this->clientes->find((string) $user->cliente_id);
        abort_if(!$client, 404, 'Cliente no encontrado.');

        return $client;
    }

    protected function invoiceMonthSegment(array $invoice): string
    {
        $rawDate = (string) ($invoice['fecha'] ?? $invoice['updated_at'] ?? '');
        try {
            return $rawDate !== '' ? Carbon::parse($rawDate)->format('Y-m') : now()->format('Y-m');
        } catch (\Throwable) {
            return now()->format('Y-m');
        }
    }

    protected function invoiceFolderPath(array $invoice): string
    {
        $month = $this->invoiceMonthSegment($invoice);
        $year = substr($month, 0, 4);
        $mm = substr($month, 5, 2);
        return 'Facturas / '.$year.' / '.$mm;
    }

    protected function buildPortalData(array $client, ?Request $request = null): array
    {
        $settings = $this->settings->find('settings') ?: [];
        $clientId = $client['id'] ?? '';
        $clientCurrency = strtoupper((string) ($client['moneda'] ?? $settings['base_currency'] ?? 'USD'));

        $allInvoices = collect($this->facturas->all());
        $invoices = $allInvoices->where('cliente_id', $clientId)->sortByDesc('fecha')->values();

        $allProjects = collect($this->proyectos->all());
        $projects = $allProjects
            ->where('cliente_id', $clientId)
            ->reject(function ($project) {
                $archived = (bool) ($project['archived'] ?? false);
                $deleted = (bool) ($project['deleted'] ?? false);
                $hasDeletedAt = !empty($project['deleted_at'] ?? null);
                $etapa = strtolower((string) ($project['etapa'] ?? ''));
                $esInactivoPorEtapa = in_array($etapa, ['archivado', 'archivado(s)', 'eliminado', 'eliminada'], true);

                return $archived || $deleted || $hasDeletedAt || $esInactivoPorEtapa;
            })
            ->sortByDesc('vencimiento')
            ->values();

        $now = Carbon::today();
        $totalInvoiced = $invoices->sum('total');
        $paidAmount = $invoices->where('estado', 'Pagada')->sum('total');
        $overdueAmount = $invoices->filter(fn($inv) =>
            ($inv['estado'] ?? '') !== 'Pagada' &&
            !empty($inv['vencimiento']) &&
            Carbon::parse($inv['vencimiento'])->lt($now)
        )->sum('total');
        $dueAmount = $invoices->filter(fn($inv) => in_array(($inv['estado'] ?? ''), ['Pendiente', 'Enviada'], true))->sum('total');
        $upcoming7 = $invoices->filter(fn($inv) =>
            ($inv['estado'] ?? '') !== 'Pagada' && !empty($inv['vencimiento']) &&
            Carbon::parse($inv['vencimiento'])->gte($now) &&
            Carbon::parse($inv['vencimiento'])->lte(Carbon::today()->addDays(7))
        )->sum('total');
        $upcoming15 = $invoices->filter(fn($inv) =>
            ($inv['estado'] ?? '') !== 'Pagada' && !empty($inv['vencimiento']) &&
            Carbon::parse($inv['vencimiento'])->gte($now) &&
            Carbon::parse($inv['vencimiento'])->lte(Carbon::today()->addDays(15))
        )->sum('total');
        $upcoming30 = $invoices->filter(fn($inv) =>
            ($inv['estado'] ?? '') !== 'Pagada' && !empty($inv['vencimiento']) &&
            Carbon::parse($inv['vencimiento'])->gte($now) &&
            Carbon::parse($inv['vencimiento'])->lte(Carbon::today()->addDays(30))
        )->sum('total');

        $pagosHistory = $invoices->flatMap(function ($inv) {
            return collect($inv['pagos'] ?? [])->map(fn($p) => array_merge($p, [
                'numero' => $inv['numero'] ?? '',
                'invoice_id' => $inv['id'] ?? '',
                'moneda' => $p['moneda'] ?? ($inv['moneda'] ?? null),
            ]));
        })->sortByDesc('fecha')->values();

        $timeline = collect($this->timeline->for($clientId));

        $mensajes = collect($this->mensajes->all())
            ->where('cliente_id', $clientId)
            ->sortBy('created_at')
            ->values();

        $docFolder = trim((string) ($request?->query('doc_folder', '') ?? ''));
        $docQ = trim((string) ($request?->query('doc_q', '') ?? ''));
        $docSort = (string) ($request?->query('doc_sort', 'recent') ?? 'recent');

        $folderVisibilityRows = collect($this->documentFolders->all())
            ->where('cliente_id', $clientId)
            ->map(function ($f) {
                return [
                    'name' => trim((string) ($f['name'] ?? '')),
                    'client_visible' => (bool) ($f['client_visible'] ?? true),
                ];
            })
            ->filter(fn($f) => $f['name'] !== '')
            ->values();

        $isFolderVisibleForClient = function (string $folderPath) use ($folderVisibilityRows): bool {
            $folderPath = trim($folderPath);
            if ($folderPath === '') return true;

            foreach ($folderVisibilityRows as $row) {
                $configured = (string) ($row['name'] ?? '');
                if ($configured === '') continue;
                if ($folderPath === $configured || Str::startsWith($folderPath, $configured.' / ')) {
                    if (($row['client_visible'] ?? true) === false) {
                        return false;
                    }
                }
            }

            return true;
        };

        $visibleClientDocumentsBase = collect($this->documentos->all())
            ->where('cliente_id', $clientId)
            ->map(function ($d) {
                $name = Str::lower((string) ($d['name'] ?? $d['original_name'] ?? ''));
                if (($d['folder'] ?? '') === '' && str_contains($name, 'factura')) {
                    $d['folder'] = 'Facturas / '.now()->format('Y').' / '.now()->format('m');
                }
                $d['folder'] = trim((string) ($d['folder'] ?? ''));
                return $d;
            })
            ->filter(fn($d) => $isFolderVisibleForClient((string) ($d['folder'] ?? '')));

        $invoiceDocs = $invoices->map(function ($inv) use ($clientId) {
            $numero = (string) ($inv['numero'] ?? $inv['id'] ?? 'Factura');
            $estado = (string) ($inv['estado'] ?? 'Pendiente');

            return [
                'id' => 'factura-'.$inv['id'],
                'source_id' => (string) ($inv['id'] ?? ''),
                'cliente_id' => $clientId,
                'folder' => $this->invoiceFolderPath($inv),
                'name' => 'Factura '.$numero,
                'original_name' => 'Factura '.$numero.'.pdf',
                'storage' => 'factura',
                'estado_factura' => $estado,
                'uploaded_by' => 'Sistema',
                'uploaded_at' => (string) ($inv['updated_at'] ?? $inv['fecha'] ?? now()->toIso8601String()),
                'size' => null,
                'mime' => 'application/pdf',
                'ext' => 'pdf',
                'created_at' => (string) ($inv['updated_at'] ?? $inv['fecha'] ?? now()->toIso8601String()),
            ];
        });

        $documents = $visibleClientDocumentsBase
            ->when($docFolder !== '', fn($col) => $col->where('folder', $docFolder))
            ->merge($invoiceDocs)
            ->unique(fn($d) => (string) ($d['id'] ?? '').'|'.(string) ($d['storage'] ?? 'local'))
            ->when($docQ !== '', fn($col) => $col->filter(function ($d) use ($docQ) {
                $needle = Str::lower($docQ);
                $name = Str::lower((string) ($d['name'] ?? ''));
                $orig = Str::lower((string) ($d['original_name'] ?? ''));
                return str_contains($name, $needle) || str_contains($orig, $needle);
            }));

        if ($docSort === 'name') {
            $documents = $documents->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE);
        } else {
            $documents = $documents->sortByDesc('created_at');
        }

        $documents = $documents->values();

        $folderNames = collect($this->documentFolders->all())
            ->where('cliente_id', $clientId)
            ->filter(fn($f) => (bool) ($f['client_visible'] ?? true))
            ->pluck('name')
            ->merge($visibleClientDocumentsBase->pluck('folder'))
            ->merge($invoiceDocs->pluck('folder'))
            ->prepend('Facturas')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return compact(
            'settings', 'invoices', 'projects',
            'totalInvoiced', 'dueAmount', 'paidAmount', 'overdueAmount',
            'upcoming7', 'upcoming15', 'upcoming30',
            'pagosHistory', 'timeline', 'mensajes',
            'documents', 'folderNames', 'docFolder', 'docQ', 'docSort',
            'clientCurrency'
        );
    }

    public function dashboard(Request $request, $id, $token)
    {
        $client = $this->getClient($id, $token);
        $data = $this->buildPortalData($client, $request);
        extract($data);
        $useTokenLinks = true;
        return view('portal.dashboard', compact(
            'client', 'token', 'invoices', 'projects', 'settings',
            'totalInvoiced', 'dueAmount', 'paidAmount', 'overdueAmount',
            'upcoming7', 'upcoming15', 'upcoming30',
            'pagosHistory', 'timeline', 'mensajes',
            'documents', 'folderNames', 'docFolder', 'docQ', 'docSort',
            'clientCurrency', 'useTokenLinks'
        ));
    }

    public function dashboardAuth(Request $request)
    {
        $client = $this->getClientFromAuth();
        $this->registerPortalAccess($client, $request);
        $data = $this->buildPortalData($client, $request);
        extract($data);
        $token = null;
        $useTokenLinks = false;
        return view('portal.dashboard', compact(
            'client', 'token', 'invoices', 'projects', 'settings',
            'totalInvoiced', 'dueAmount', 'paidAmount', 'overdueAmount',
            'upcoming7', 'upcoming15', 'upcoming30',
            'pagosHistory', 'timeline', 'mensajes',
            'documents', 'folderNames', 'docFolder', 'docQ', 'docSort',
            'clientCurrency', 'useTokenLinks'
        ));
    }

    public function storeProjectTaskNoteAuth(Request $request)
    {
        $client = $this->getClientFromAuth();
        return $this->storeProjectTaskNoteForClient($request, $client);
    }

    public function storeProjectTaskNoteToken(Request $request, $id, $token)
    {
        $client = $this->getClient($id, $token);
        return $this->storeProjectTaskNoteForClient($request, $client);
    }

    protected function storeProjectTaskNoteForClient(Request $request, array $client)
    {
        $data = $request->validate([
            'project_id' => 'required|string',
            'task_id' => 'required|string',
            'texto' => 'required|string|max:1500',
        ]);

        $project = $this->proyectos->find($data['project_id']);
        abort_if(!$project, 404);
        abort_if((string) ($project['cliente_id'] ?? '') !== (string) ($client['id'] ?? ''), 403);

        $noteText = trim((string) $data['texto']);
        abort_if($noteText === '', 422, 'La nota no puede estar vacía.');

        $tasks = array_values($project['tareas'] ?? []);
        $found = false;
        $clientName = trim((string) ($client['empresa'] ?? $client['contacto_nombre'] ?? 'Cliente'));
        if ($clientName === '') {
            $clientName = 'Cliente';
        }

        foreach ($tasks as &$task) {
            if ((string) ($task['id'] ?? '') !== (string) $data['task_id']) {
                continue;
            }

            $notes = array_values($task['notes'] ?? []);
            $notes[] = [
                'id' => (string) Str::ulid(),
                'texto' => $noteText,
                'created_at' => now()->toIso8601String(),
                'updated_at' => null,
                'author_name' => $clientName,
                'author_id' => null,
                'source' => 'portal_cliente',
            ];
            $task['notes'] = $notes;
            $found = true;
            break;
        }
        unset($task);

        abort_if(!$found, 404);

        $updated = $this->proyectos->update((string) $project['id'], ['tareas' => $tasks]);
        $updatedTask = collect($updated['tareas'] ?? [])
            ->first(fn($task) => (string) ($task['id'] ?? '') === (string) $data['task_id']);

        return response()->json([
            'ok' => true,
            'task' => $updatedTask,
        ]);
    }

    private function registerPortalAccess(array $client, Request $request): void
    {
        try {
            $clientId = (string) ($client['id'] ?? '');
            if ($clientId === '') {
                return;
            }

            $rows = collect($this->portalAccessLogs->all());
            $last = $rows
                ->filter(fn ($r) => (string) ($r['client_id'] ?? '') === $clientId)
                ->sortByDesc('created_at')
                ->first();

            if ($last) {
                $lastAtRaw = trim((string) ($last['created_at'] ?? ''));
                if ($lastAtRaw !== '') {
                    try {
                        $lastAt = Carbon::parse($lastAtRaw);
                        if ($lastAt->diffInMinutes(now()) < 30) {
                            return;
                        }
                    } catch (\Throwable $e) {
                        // Continuar y registrar igual.
                    }
                }
            }

            $this->portalAccessLogs->create([
                'id' => (string) Str::ulid(),
                'client_id' => $clientId,
                'client_name' => (string) ($client['empresa'] ?? $client['contacto_nombre'] ?? 'Cliente'),
                'portal_email' => (string) ($client['portal_email'] ?? ''),
                'user_email' => (string) (Auth::user()?->email ?? ''),
                'ip' => (string) ($request->ip() ?? ''),
                'user_agent' => substr((string) ($request->userAgent() ?? ''), 0, 300),
                'created_at' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            // Nunca romper dashboard del cliente por falla de log interno.
        }
    }

    public function invoice($id, $token, $invoiceId)
    {
        $client = $this->getClient($id, $token);
        $settings = $this->settings->find('settings') ?: [];
        $invoice = $this->facturas->find($invoiceId);
        
        if (!$invoice || ($invoice['cliente_id'] ?? '') !== $id) {
            abort(404);
        }
        
        $useTokenLinks = true;
        return view('portal.invoice', compact('client', 'token', 'invoice', 'settings', 'useTokenLinks'));
    }

    public function invoiceAuth($invoiceId)
    {
        $client = $this->getClientFromAuth();
        $settings = $this->settings->find('settings') ?: [];
        $invoice = $this->facturas->find($invoiceId);

        if (!$invoice || ($invoice['cliente_id'] ?? '') !== ($client['id'] ?? '')) {
            abort(404);
        }

        $token = null;
        $useTokenLinks = false;
        return view('portal.invoice', compact('client', 'token', 'invoice', 'settings', 'useTokenLinks'));
    }

    private function buildInvoicePdfDownload(array $invoice)
    {
        if (class_exists('\\Barryvdh\\DomPDF\\Facade\\Pdf')) {
            return $this->invoicePdfObject($invoice)->download($this->invoicePdfFileName($invoice));
        }

        return view('ventas.facturas_print', ['factura' => $invoice]);
    }

    private function invoicePdfOutput(array $invoice): string
    {
        return $this->invoicePdfObject($invoice)->output();
    }

    private function invoicePdfObject(array $invoice)
    {
        $this->ensureInvoicePdfRuntime();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('ventas.facturas_print', [
            'factura' => $invoice,
            'pdfMode' => true,
        ])->setPaper('a4');

        if (method_exists($pdf, 'setOptions')) {
            $pdf->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'fontDir' => storage_path('fonts'),
                'fontCache' => storage_path('fonts'),
                'tempDir' => storage_path('app/dompdf-temp'),
                'chroot' => base_path(),
            ]);
        }

        return $pdf;
    }

    private function ensureInvoicePdfRuntime(): void
    {
        foreach ([storage_path('fonts'), storage_path('app/dompdf-temp')] as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
        }
    }

    private function invoicePdfFileName(array $invoice): string
    {
        $numero = trim((string) ($invoice['numero'] ?? $invoice['id'] ?? 'factura'));
        $numero = preg_replace('/[^A-Za-z0-9._-]+/', '_', $numero) ?: 'factura';
        return 'factura_' . $numero . '.pdf';
    }

    public function invoicePdf($id, $token, $invoiceId)
    {
        $client = $this->getClient($id, $token);
        $invoice = $this->facturas->find($invoiceId);
        if (!$invoice || ($invoice['cliente_id'] ?? '') !== ($client['id'] ?? '')) {
            abort(404);
        }

        return $this->buildInvoicePdfDownload($invoice);
    }

    public function invoicePdfAuth($invoiceId)
    {
        $client = $this->getClientFromAuth();
        $invoice = $this->facturas->find($invoiceId);
        if (!$invoice || ($invoice['cliente_id'] ?? '') !== ($client['id'] ?? '')) {
            abort(404);
        }

        return $this->buildInvoicePdfDownload($invoice);
    }

    public function downloadDocument($id, $token, $docId)
    {
        $client = $this->getClient($id, $token);
        $doc = $this->documentos->find($docId);
        if (!$doc || ($doc['cliente_id'] ?? '') !== ($client['id'] ?? '')) {
            abort(404);
        }

        if (($doc['storage'] ?? 'local') === 'drive') {
            $url = $doc['drive_url'] ?? '';
            abort_if($url === '', 404);
            return redirect()->away($url);
        }

        $path = $doc['path'] ?? '';
        abort_if($path === '' || !Storage::disk('public')->exists($path), 404);
        return Storage::disk('public')->download($path, $doc['original_name'] ?? basename($path));
    }

    public function downloadDocumentAuth($docId)
    {
        $client = $this->getClientFromAuth();
        $doc = $this->documentos->find($docId);
        if (!$doc || ($doc['cliente_id'] ?? '') !== ($client['id'] ?? '')) {
            abort(404);
        }

        if (($doc['storage'] ?? 'local') === 'drive') {
            $url = $doc['drive_url'] ?? '';
            abort_if($url === '', 404);
            return redirect()->away($url);
        }

        $path = $doc['path'] ?? '';
        abort_if($path === '' || !Storage::disk('public')->exists($path), 404);
        return Storage::disk('public')->download($path, $doc['original_name'] ?? basename($path));
    }

    // -------------------------------------------------------------------------
    // Mensajería
    // -------------------------------------------------------------------------
    public function storeMessageAuth(Request $request)
    {
        $client = $this->getClientFromAuth();
        $data = $request->validate([
            'mensaje'  => 'required|string|max:2000',
            'ref_type' => 'nullable|in:general,factura,proyecto',
            'ref_id'   => 'nullable|string|max:100',
        ]);
        $record = [
            'id'         => (string) Str::ulid(),
            'cliente_id' => $client['id'],
            'from'       => 'client',
            'user_id'    => null,
            'mensaje'    => strip_tags($data['mensaje']),
            'ref_type'   => $data['ref_type'] ?? 'general',
            'ref_id'     => $data['ref_id'] ?? null,
            'created_at' => now()->toISOString(),
        ];
        $this->mensajes->create($record);
        $this->timeline->add($client['id'], 'mensaje', ['mensaje' => Str::limit($record['mensaje'], 80), 'from' => 'client']);
        return back()->with('msg_ok', '¡Mensaje enviado!');
    }

    public function storeMessageToken(Request $request, $id, $token)
    {
        $client = $this->getClient($id, $token);
        $data = $request->validate([
            'mensaje'  => 'required|string|max:2000',
            'ref_type' => 'nullable|in:general,factura,proyecto',
            'ref_id'   => 'nullable|string|max:100',
        ]);
        $record = [
            'id'         => (string) Str::ulid(),
            'cliente_id' => $client['id'],
            'from'       => 'client',
            'user_id'    => null,
            'mensaje'    => strip_tags($data['mensaje']),
            'ref_type'   => $data['ref_type'] ?? 'general',
            'ref_id'     => $data['ref_id'] ?? null,
            'created_at' => now()->toISOString(),
        ];
        $this->mensajes->create($record);
        return redirect()->route('portal.dashboard', ['id' => $id, 'token' => $token])
            ->with('msg_ok', '¡Mensaje enviado!');
    }

    // -------------------------------------------------------------------------
    // Descarga masiva ZIP de facturas
    // -------------------------------------------------------------------------
    private function buildZipResponse(array $client, array $invoices, string $prefix): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'facturas_zip_');

        $zip = new ZipArchive();
        if ($zip->open($tmpFile, ZipArchive::OVERWRITE) !== true) {
            abort(500, 'No se pudo crear el archivo ZIP.');
        }

        foreach ($invoices as $inv) {
            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                $zip->addFromString($this->invoicePdfFileName($inv), $this->invoicePdfOutput($inv));
            }
        }
        $zip->close();

        $filename = $prefix . '_facturas_' . date('Ymd') . '.zip';
        return response()->streamDownload(function () use ($tmpFile) {
            readfile($tmpFile);
            @unlink($tmpFile);
        }, $filename, ['Content-Type' => 'application/zip']);
    }

    public function zipFacturasAuth(Request $request)
    {
        $client = $this->getClientFromAuth();
        $from = $request->query('desde');
        $to   = $request->query('hasta');
        $invoices = collect($this->facturas->all())
            ->where('cliente_id', $client['id'])
            ->when($from, fn($c) => $c->filter(fn($inv) => ($inv['fecha'] ?? '') >= $from))
            ->when($to,   fn($c) => $c->filter(fn($inv) => ($inv['fecha'] ?? '') <= $to))
            ->sortBy('fecha')->values()->all();
        return $this->buildZipResponse($client, $invoices, Str::slug($client['empresa'] ?? 'cliente'));
    }

    public function zipFacturasToken(Request $request, $id, $token)
    {
        $client = $this->getClient($id, $token);
        $from = $request->query('desde');
        $to   = $request->query('hasta');
        $invoices = collect($this->facturas->all())
            ->where('cliente_id', $client['id'])
            ->when($from, fn($c) => $c->filter(fn($inv) => ($inv['fecha'] ?? '') >= $from))
            ->when($to,   fn($c) => $c->filter(fn($inv) => ($inv['fecha'] ?? '') <= $to))
            ->sortBy('fecha')->values()->all();
        return $this->buildZipResponse($client, $invoices, Str::slug($client['empresa'] ?? 'cliente'));
    }

    // -------------------------------------------------------------------------
    // Cambio de contraseña (primer acceso)
    // -------------------------------------------------------------------------
    public function showChangePassword()
    {
        return view('portal.change-password');
    }

    public function storeChangePassword(Request $request)
    {
        $data = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);
        $user = Auth::user();
        abort_unless($user, 401);
        $user->password = Hash::make($data['password']);
        $user->must_change_password = false;
        $user->save();
        return redirect()->route('portal.auth.dashboard')->with('pw_changed', true);
    }

    // ── Admin: ver mensajes de un cliente ──────────────────────────
    public function adminMensajes(string $id)
    {
        $client = $this->clientes->find($id);
        if (!$client) abort(404);

        $all = collect($this->mensajes->all())
            ->filter(fn($m) => ($m['cliente_id'] ?? '') === $id)
            ->sortBy('created_at')
            ->values();

        return view('portal.admin-mensajes', compact('client', 'all'));
    }

    public function adminReply(Request $request, string $id)
    {
        $data = $request->validate(['message' => 'required|string|max:2000']);
        $client = $this->clientes->find($id);
        if (!$client) abort(404);

        $msg = [
            'id'         => Str::uuid()->toString(),
            'cliente_id' => $id,
            'from'       => 'team',
            'author'     => Auth::user()->name ?? 'Equipo',
            'message'    => $data['message'],
            'created_at' => now()->toIso8601String(),
        ];
        $this->mensajes->push($msg);
        $this->timeline->log($id, 'mensaje', '💬 Respuesta del equipo: '.$data['message']);

        return back()->with('reply_ok', 'Respuesta enviada.');
    }

    // ── Helpers ───────────────────────────────────────────────────
    protected function decryptSetting(?string $value): string
    {
        if (!$value) return '';
        if (str_starts_with($value, 'ENC:')) {
            try { return Crypt::decryptString(substr($value, 4)); } catch (\Throwable) { return ''; }
        }
        return $value;
    }

    protected function preferredPaymentGateway(array $settings): string
    {
        $gateway = strtolower((string) ($settings['payment_gateway'] ?? 'stripe'));
        return in_array($gateway, ['stripe', 'paypal', 'wompi'], true) ? $gateway : 'stripe';
    }

    protected function hasStripeConfigured(array $settings): bool
    {
        return $this->decryptSetting($settings['stripe_secret'] ?? '') !== '';
    }

    protected function hasPaypalConfigured(array $settings): bool
    {
        return trim((string) ($settings['paypal_client_id'] ?? '')) !== ''
            && $this->decryptSetting($settings['paypal_secret'] ?? '') !== '';
    }

    protected function hasWompiConfigured(array $settings): bool
    {
        return trim((string) ($settings['wompi_public_key'] ?? '')) !== '';
    }

    protected function resolvePaymentGatewayForInvoice(array $invoice, array $settings): ?string
    {
        $currency = strtoupper((string) ($invoice['moneda'] ?? $settings['wompi_currency'] ?? $settings['stripe_currency'] ?? 'COP'));
        $paypalPriorityCurrencies = ['USD', 'EUR'];

        if ($currency === 'COP') {
            if ($this->hasWompiConfigured($settings)) {
                return 'wompi';
            }

            if ($this->hasStripeConfigured($settings)) {
                return 'stripe';
            }

            if ($this->hasPaypalConfigured($settings)) {
                return 'paypal';
            }

            return null;
        }

        if (in_array($currency, $paypalPriorityCurrencies, true)) {
            if ($this->hasPaypalConfigured($settings)) {
                return 'paypal';
            }

            if ($this->hasStripeConfigured($settings)) {
                return 'stripe';
            }

            return null;
        }

        if ($this->hasStripeConfigured($settings)) {
            return 'stripe';
        }

        if ($this->hasPaypalConfigured($settings)) {
            return 'paypal';
        }

        return null;
    }

    protected function buildCheckoutForGateway(string $gateway, array $invoice, array $client, ?string $id, ?string $token, ?string $publicInvoiceId = null)
    {
        if ($gateway === 'paypal') {
            return $this->buildPaypalCheckout($invoice, $client, $id, $token, $publicInvoiceId);
        }

        if ($gateway === 'wompi') {
            return $this->buildWompiCheckout($invoice, $client, $id, $token, $publicInvoiceId);
        }

        return $this->buildStripeSession($invoice, $client, $id, $token, $publicInvoiceId);
    }

    protected function gatewayRedirect(string $gateway, string $url, array $invoice, ?string $cancelUrl = null)
    {
        return response()
            ->view('portal.payment_redirect', [
                'gateway' => $gateway,
                'gatewayUrl' => $url,
                'cancelUrl' => $cancelUrl,
                'invoice' => $invoice,
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    protected function paypalErrorMessage($response, string $fallback): string
    {
        $payload = $response->json();
        $parts = [];

        $name = trim((string) ($payload['name'] ?? $payload['error'] ?? ''));
        $description = trim((string) ($payload['error_description'] ?? $payload['message'] ?? ''));

        if ($name !== '') {
            $parts[] = $name;
        }

        if ($description !== '') {
            $parts[] = $description;
        }

        $detail = implode(': ', $parts);

        return $detail !== '' ? $fallback.' '.$detail : $fallback;
    }

    public function payCheckout(Request $request, string $id, string $token, string $invoiceId)
    {
        $client = $this->getClient($id, $token);
        $invoice = $this->facturas->find($invoiceId);
        if (!$invoice || ($invoice['cliente_id'] ?? '') !== $id) abort(404);
        if (($invoice['estado'] ?? '') === 'Pagada') {
            return redirect()->route('portal.dashboard', compact('id', 'token'))
                ->with('msg_ok', 'Esta factura ya esta pagada.');
        }

        $settings = $this->settings->find('settings') ?: [];
        $gateway = $this->resolvePaymentGatewayForInvoice($invoice, $settings);
        if ($gateway === null) {
            return back()->withErrors(['pago' => 'No hay una pasarela compatible configurada para la moneda de esta factura.']);
        }

        return $this->buildCheckoutForGateway($gateway, $invoice, $client, $id, $token);
    }

    public function payCheckoutAuth(Request $request, string $invoiceId)
    {
        $client = $this->getClientFromAuth();
        $invoice = $this->facturas->find($invoiceId);
        if (!$invoice || ($invoice['cliente_id'] ?? '') !== ($client['id'] ?? '')) abort(404);
        if (($invoice['estado'] ?? '') === 'Pagada') {
            return redirect()->route('portal.auth.dashboard')->with('msg_ok', 'Esta factura ya esta pagada.');
        }

        $settings = $this->settings->find('settings') ?: [];
        $gateway = $this->resolvePaymentGatewayForInvoice($invoice, $settings);
        if ($gateway === null) {
            return back()->withErrors(['pago' => 'No hay una pasarela compatible configurada para la moneda de esta factura.']);
        }

        return $this->buildCheckoutForGateway($gateway, $invoice, $client, null, null);
    }

    private function getPublicInvoiceAndClient(string $invoiceId): array
    {
        $invoice = $this->facturas->find($invoiceId);
        abort_if(!$invoice, 404);

        $clientId = (string) ($invoice['cliente_id'] ?? '');
        abort_if($clientId === '', 404);

        $client = $this->clientes->find($clientId);
        abort_if(!$client, 404);

        return [$invoice, $client];
    }

    public function publicPayCheckout(Request $request, string $invoiceId)
    {
        [$invoice, $client] = $this->getPublicInvoiceAndClient($invoiceId);

        if (($invoice['estado'] ?? '') === 'Pagada') {
            return redirect()->route('facturas.public', $invoiceId)
                ->with('msg_ok', 'Esta factura ya esta pagada.');
        }

        $settings = $this->settings->find('settings') ?: [];
        $gateway = $this->resolvePaymentGatewayForInvoice($invoice, $settings);
        if ($gateway === null) {
            return back()->withErrors(['pago' => 'No hay una pasarela compatible configurada para la moneda de esta factura.']);
        }

        return $this->buildCheckoutForGateway($gateway, $invoice, $client, null, null, $invoiceId);
    }

    private function buildWompiCheckout(array $invoice, array $client, ?string $id, ?string $token, ?string $publicInvoiceId = null)
    {
        $settings = $this->settings->find('settings') ?: [];
        $publicKey = trim((string) ($settings['wompi_public_key'] ?? ''));
        $mode = strtolower((string) ($settings['wompi_mode'] ?? 'test'));
        if ($publicKey === '') {
            return back()->withErrors(['pago' => 'Wompi no esta configurado. Contacta al equipo.']);
        }

        $integritySecret = trim($this->decryptSetting($settings['wompi_integrity_secret'] ?? ''));
        if ($integritySecret === '') {
            return back()->withErrors([
                'pago' => 'Wompi no esta configurado completamente: falta el Secreto de Integridad.',
            ]);
        }

        $eventSecret = trim($this->decryptSetting($settings['wompi_event_secret'] ?? ''));
        if ($eventSecret === '') {
            return back()->withErrors([
                'pago' => 'Wompi no esta configurado completamente: falta el Secreto de Eventos.',
            ]);
        }

        // Evitar configuraciones cruzadas (llave live en test o viceversa).
        if ($mode === 'test' && !str_starts_with($publicKey, 'pub_test_')) {
            return back()->withErrors(['pago' => 'Wompi en modo Test requiere una llave publica que empiece por pub_test_.']);
        }
        if ($mode === 'live' && !str_starts_with($publicKey, 'pub_prod_')) {
            return back()->withErrors(['pago' => 'Wompi en modo Live requiere una llave publica que empiece por pub_prod_.']);
        }
        if ($mode === 'test' && (!str_starts_with($integritySecret, 'test_integrity_') || !str_starts_with($eventSecret, 'test_events_'))) {
            return back()->withErrors(['pago' => 'Los secretos de Wompi no corresponden al modo Test.']);
        }
        if ($mode === 'live' && (!str_starts_with($integritySecret, 'prod_integrity_') || !str_starts_with($eventSecret, 'prod_events_'))) {
            return back()->withErrors(['pago' => 'Los secretos de Wompi no corresponden al modo Live.']);
        }

        $amount = (int) round((float) ($invoice['total'] ?? 0) * 100);
        if ($amount <= 0) {
            return back()->withErrors(['pago' => 'Monto invalido para pago.']);
        }

        $currency = 'COP';

        $reference = 'INV-'.$invoice['id'].'-'.Str::upper(Str::random(6));

        if ($publicInvoiceId) {
            $redirectUrl = route('public.wompi.success', [
                'invoiceId' => $publicInvoiceId,
                'invoice_id' => $invoice['id'],
                'reference' => $reference,
            ]);
        } elseif ($id && $token) {
            $redirectUrl = route('portal.wompi.success', [
                'id' => $id,
                'token' => $token,
                'invoice_id' => $invoice['id'],
                'reference' => $reference,
            ]);
        } else {
            $redirectUrl = route('portal.auth.wompi.success', [
                'invoice_id' => $invoice['id'],
                'reference' => $reference,
            ]);
        }

        $query = [
            'public-key' => $publicKey,
            'currency' => $currency,
            'amount-in-cents' => $amount,
            'reference' => $reference,
        ];

        // Wompi/CloudFront puede bloquear callbacks locales (127.0.0.1/localhost).
        // En entornos locales omitimos redirect-url para evitar 403.
        if (!$this->isLocalUrl($redirectUrl)) {
            $query['redirect-url'] = $redirectUrl;
        }

        $email = trim((string) ($client['contacto_email'] ?? ''));
        if ($email !== '') {
            $query['customer-data:email'] = $email;
        }

        $query['signature:integrity'] = hash('sha256', $reference.$amount.$currency.$integritySecret);

        return redirect()->away('https://checkout.wompi.co/p/?'.http_build_query($query));
    }

    private function isLocalUrl(string $url): bool
    {
        $host = (string) (parse_url($url, PHP_URL_HOST) ?? '');
        $host = strtolower(trim($host));
        if ($host === '' || $host === 'localhost' || $host === '127.0.0.1') {
            return true;
        }
        if (str_ends_with($host, '.local') || str_ends_with($host, '.test')) {
            return true;
        }
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return !filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }
        return false;
    }

    private function buildPaypalCheckout(array $invoice, array $client, ?string $id, ?string $token, ?string $publicInvoiceId = null)
    {
        $settings = $this->settings->find('settings') ?: [];
        $paypalClientId = trim((string) ($settings['paypal_client_id'] ?? ''));
        $paypalSecret = $this->decryptSetting($settings['paypal_secret'] ?? '');

        if ($paypalClientId === '' || $paypalSecret === '') {
            return back()->withErrors(['pago' => 'PayPal no esta configurado. Contacta al equipo.']);
        }

        $mode = strtolower((string) ($settings['paypal_mode'] ?? 'sandbox'));
        $apiBase = $mode === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

        $tokenResponse = Http::withBasicAuth($paypalClientId, $paypalSecret)
            ->asForm()
            ->post($apiBase.'/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if (!$tokenResponse->successful()) {
            return back()->withErrors([
                'pago' => $this->paypalErrorMessage($tokenResponse, 'No se pudo conectar con PayPal para iniciar el cobro.')
            ]);
        }

        $accessToken = (string) $tokenResponse->json('access_token', '');
        if ($accessToken === '') {
            return back()->withErrors(['pago' => 'PayPal no devolvio token de acceso.']);
        }

        $amount = number_format((float) ($invoice['total'] ?? 0), 2, '.', '');
        $currency = strtoupper((string) ($invoice['moneda'] ?? 'USD'));
        $empresa = (string) ($settings['company_name'] ?? 'Mi Empresa');
        $numero = (string) ($invoice['numero'] ?? $invoice['id']);

        if ($publicInvoiceId) {
            $returnUrl = route('public.paypal.success', ['invoiceId' => $publicInvoiceId, 'invoice_id' => $invoice['id']]);
            $cancelUrl = route('facturas.public', $publicInvoiceId);
        } elseif ($id && $token) {
            $returnUrl = route('portal.paypal.success', ['id' => $id, 'token' => $token, 'invoice_id' => $invoice['id']]);
            $cancelUrl = route('portal.dashboard', ['id' => $id, 'token' => $token]);
        } else {
            $returnUrl = route('portal.auth.paypal.success', ['invoice_id' => $invoice['id']]);
            $cancelUrl = route('portal.auth.dashboard');
        }

        $orderResponse = Http::withToken($accessToken)
            ->post($apiBase.'/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => (string) ($invoice['id'] ?? ''),
                    'description' => 'Factura #'.$numero.' - '.$empresa,
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => $amount,
                    ],
                ]],
                'application_context' => [
                    'brand_name' => $empresa,
                    'user_action' => 'PAY_NOW',
                    'return_url' => $returnUrl,
                    'cancel_url' => $cancelUrl,
                ],
            ]);

        if (!$orderResponse->successful()) {
            return back()->withErrors([
                'pago' => $this->paypalErrorMessage($orderResponse, 'No se pudo crear la orden en PayPal.')
            ]);
        }

        $approveLink = collect($orderResponse->json('links', []))
            ->firstWhere('rel', 'approve');
        $approveUrl = (string) ($approveLink['href'] ?? '');
        $orderId = (string) ($orderResponse->json('id') ?? '');

        // Normalizar URL de aprobación para evitar bloqueos CloudFront
        // cuando PayPal devuelve un host que no corresponde al modo actual.
        if ($mode === 'sandbox' && $orderId !== '') {
            $approveUrl = 'https://www.sandbox.paypal.com/checkoutnow?token=' . urlencode($orderId);
        } elseif ($mode === 'live' && $orderId !== '') {
            $approveUrl = 'https://www.paypal.com/checkoutnow?token=' . urlencode($orderId);
        }

        if ($approveUrl === '') {
            return back()->withErrors(['pago' => 'PayPal no devolvio URL de aprobacion.']);
        }

        return $this->gatewayRedirect('PayPal', $approveUrl, $invoice, $cancelUrl);
    }

    // ── Stripe Checkout ───────────────────────────────────────────
    public function stripeCheckout(Request $request, string $id, string $token, string $invoiceId)
    {
        $client   = $this->getClient($id, $token);
        $invoice  = $this->facturas->find($invoiceId);
        if (!$invoice || ($invoice['cliente_id'] ?? '') !== $id) abort(404);
        if (($invoice['estado'] ?? '') === 'Pagada') {
            return redirect()->route('portal.dashboard', compact('id','token'))
                ->with('msg_ok', 'Esta factura ya está pagada.');
        }
        return $this->buildStripeSession($invoice, $client, $id, $token);
    }

    public function stripeCheckoutAuth(Request $request, string $invoiceId)
    {
        $client  = $this->getClientFromAuth();
        $invoice = $this->facturas->find($invoiceId);
        if (!$invoice || ($invoice['cliente_id'] ?? '') !== ($client['id'] ?? '')) abort(404);
        if (($invoice['estado'] ?? '') === 'Pagada') {
            return redirect()->route('portal.auth.dashboard')->with('msg_ok', 'Esta factura ya está pagada.');
        }
        return $this->buildStripeSession($invoice, $client, null, null);
    }

    private function buildStripeSession(array $invoice, array $client, ?string $id, ?string $token, ?string $publicInvoiceId = null)
    {
        $settings      = $this->settings->find('settings') ?: [];
        $stripeSecret  = $this->decryptSetting($settings['stripe_secret'] ?? '');

        if (!$stripeSecret) {
            return back()->withErrors(['pago' => 'Stripe no está configurado. Contacta al equipo.']);
        }

        $amount  = (int) round(($invoice['total'] ?? 0) * 100); // centavos
        $currency = strtolower($invoice['moneda'] ?? $settings['stripe_currency'] ?? 'usd');
        $empresa = $settings['company_name'] ?? 'Mi Empresa';
        $numero  = $invoice['numero'] ?? $invoice['id'];

        if ($publicInvoiceId) {
            $successUrl = route('public.stripe.success', ['invoiceId' => $publicInvoiceId, 'invoice_id' => $invoice['id']]);
            $cancelUrl = route('facturas.public', $publicInvoiceId);
        } elseif ($id && $token) {
            $successUrl = route('portal.stripe.success', ['id'=>$id,'token'=>$token,'invoice_id'=>$invoice['id']]);
            $cancelUrl  = route('portal.dashboard', compact('id','token'));
        } else {
            $successUrl = route('portal.auth.stripe.success', ['invoice_id'=>$invoice['id']]);
            $cancelUrl  = route('portal.auth.dashboard');
        }

        $response = Http::withToken($stripeSecret)
            ->asForm()
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'payment_method_types[]'                       => 'card',
                'line_items[0][price_data][currency]'          => $currency,
                'line_items[0][price_data][unit_amount]'       => $amount,
                'line_items[0][price_data][product_data][name]'=> "Factura #{$numero} — {$empresa}",
                'line_items[0][quantity]'                      => 1,
                'mode'                                         => 'payment',
                'success_url'                                  => $successUrl.'&session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'                                   => $cancelUrl,
                'customer_email'                               => $client['contacto_email'] ?? null,
            ]);

        if (!$response->successful()) {
            return back()->withErrors(['pago' => 'Error al crear sesión de pago: '.$response->json('error.message','Error desconocido')]);
        }

        return redirect()->away($response->json('url'));
    }

    public function stripeSuccess(Request $request, string $id, string $token)
    {
        $client    = $this->getClient($id, $token);
        $invoiceId = $request->query('invoice_id');
        $sessionId = $request->query('session_id');
        $this->markInvoicePaidByStripe($invoiceId, $client, $sessionId);
        return redirect()->route('portal.dashboard', compact('id','token'))
            ->with('msg_ok', 'Pago recibido correctamente. ¡Gracias!');
    }

    public function stripeSuccessAuth(Request $request)
    {
        $client    = $this->getClientFromAuth();
        $invoiceId = $request->query('invoice_id');
        $sessionId = $request->query('session_id');
        $this->markInvoicePaidByStripe($invoiceId, $client, $sessionId);
        return redirect()->route('portal.auth.dashboard')
            ->with('msg_ok', 'Pago recibido correctamente. ¡Gracias!');
    }

    public function publicStripeSuccess(Request $request, string $invoiceId)
    {
        [$invoice, $client] = $this->getPublicInvoiceAndClient($invoiceId);
        $sessionId = $request->query('session_id');
        $this->markInvoicePaidByStripe((string) ($invoice['id'] ?? $invoiceId), $client, $sessionId);

        return redirect()->route('facturas.public', $invoiceId)
            ->with('msg_ok', 'Pago recibido correctamente. ¡Gracias!');
    }

    public function wompiSuccess(Request $request, string $id, string $token)
    {
        $client = $this->getClient($id, $token);
        $invoiceId = (string) $request->query('invoice_id');
        $reference = (string) $request->query('reference');
        $transactionId = trim((string) $request->query('id'));

        if ($transactionId === '' || !$this->confirmWompiRedirectPayment($invoiceId, $client, $transactionId, $reference)) {
            return redirect()->route('portal.dashboard', compact('id', 'token'))
                ->withErrors(['pago' => 'El pago en Wompi aun no ha podido ser confirmado.']);
        }

        return redirect()->route('portal.dashboard', compact('id', 'token'))
            ->with('msg_ok', 'Pago recibido correctamente por Wompi. ¡Gracias!');
    }

    public function wompiSuccessAuth(Request $request)
    {
        $client = $this->getClientFromAuth();
        $invoiceId = (string) $request->query('invoice_id');
        $reference = (string) $request->query('reference');
        $transactionId = trim((string) $request->query('id'));

        if ($transactionId === '' || !$this->confirmWompiRedirectPayment($invoiceId, $client, $transactionId, $reference)) {
            return redirect()->route('portal.auth.dashboard')
                ->withErrors(['pago' => 'El pago en Wompi aun no ha podido ser confirmado.']);
        }

        return redirect()->route('portal.auth.dashboard')
            ->with('msg_ok', 'Pago recibido correctamente por Wompi. ¡Gracias!');
    }

    public function publicWompiSuccess(Request $request, string $invoiceId)
    {
        [$invoice, $client] = $this->getPublicInvoiceAndClient($invoiceId);
        $reference = (string) $request->query('reference');
        $transactionId = trim((string) $request->query('id'));

        if ($transactionId === '' || !$this->confirmWompiRedirectPayment((string) ($invoice['id'] ?? $invoiceId), $client, $transactionId, $reference)) {
            return redirect()->route('facturas.public', $invoiceId)
                ->withErrors(['pago' => 'El pago en Wompi aun no ha podido ser confirmado.']);
        }

        return redirect()->route('facturas.public', $invoiceId)
            ->with('msg_ok', 'Pago recibido correctamente por Wompi. ¡Gracias!');
    }

    public function paypalSuccess(Request $request, string $id, string $token)
    {
        $client = $this->getClient($id, $token);
        $invoiceId = (string) $request->query('invoice_id');
        $orderId = (string) $request->query('token');

        if ($orderId === '') {
            return redirect()->route('portal.dashboard', compact('id', 'token'))
                ->withErrors(['pago' => 'No se recibio identificador de orden PayPal.']);
        }

        $captureError = $this->capturePaypalOrder($orderId);
        if ($captureError !== null) {
            return redirect()->route('portal.dashboard', compact('id', 'token'))
                ->withErrors(['pago' => $captureError]);
        }

        $this->markInvoicePaidByPaypal($invoiceId, $client, $orderId);

        return redirect()->route('portal.dashboard', compact('id', 'token'))
            ->with('msg_ok', 'Pago recibido correctamente por PayPal. ¡Gracias!');
    }

    public function paypalSuccessAuth(Request $request)
    {
        $client = $this->getClientFromAuth();
        $invoiceId = (string) $request->query('invoice_id');
        $orderId = (string) $request->query('token');

        if ($orderId === '') {
            return redirect()->route('portal.auth.dashboard')
                ->withErrors(['pago' => 'No se recibio identificador de orden PayPal.']);
        }

        $captureError = $this->capturePaypalOrder($orderId);
        if ($captureError !== null) {
            return redirect()->route('portal.auth.dashboard')
                ->withErrors(['pago' => $captureError]);
        }

        $this->markInvoicePaidByPaypal($invoiceId, $client, $orderId);

        return redirect()->route('portal.auth.dashboard')
            ->with('msg_ok', 'Pago recibido correctamente por PayPal. ¡Gracias!');
    }

    public function publicPaypalSuccess(Request $request, string $invoiceId)
    {
        [$invoice, $client] = $this->getPublicInvoiceAndClient($invoiceId);
        $orderId = (string) $request->query('token');

        if ($orderId === '') {
            return redirect()->route('facturas.public', $invoiceId)
                ->withErrors(['pago' => 'No se recibio identificador de orden PayPal.']);
        }

        $captureError = $this->capturePaypalOrder($orderId);
        if ($captureError !== null) {
            return redirect()->route('facturas.public', $invoiceId)
                ->withErrors(['pago' => $captureError]);
        }

        $this->markInvoicePaidByPaypal((string) ($invoice['id'] ?? $invoiceId), $client, $orderId);

        return redirect()->route('facturas.public', $invoiceId)
            ->with('msg_ok', 'Pago recibido correctamente por PayPal. ¡Gracias!');
    }

    private function capturePaypalOrder(string $orderId): ?string
    {
        $settings = $this->settings->find('settings') ?: [];
        $paypalClientId = trim((string) ($settings['paypal_client_id'] ?? ''));
        $paypalSecret = $this->decryptSetting($settings['paypal_secret'] ?? '');
        if ($paypalClientId === '' || $paypalSecret === '') {
            return 'PayPal no esta configurado.';
        }

        $mode = strtolower((string) ($settings['paypal_mode'] ?? 'sandbox'));
        $apiBase = $mode === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

        $tokenResponse = Http::withBasicAuth($paypalClientId, $paypalSecret)
            ->asForm()
            ->post($apiBase.'/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if (!$tokenResponse->successful()) {
            return 'No se pudo validar el pago con PayPal.';
        }

        $accessToken = (string) $tokenResponse->json('access_token', '');
        if ($accessToken === '') {
            return 'PayPal no devolvio token de validacion.';
        }

        $captureResponse = Http::withToken($accessToken)
            ->post($apiBase.'/v2/checkout/orders/'.$orderId.'/capture');

        if (!$captureResponse->successful()) {
            return 'No se pudo capturar la orden en PayPal.';
        }

        $status = strtoupper((string) $captureResponse->json('status', ''));
        if ($status !== 'COMPLETED') {
            return 'La orden de PayPal no quedo completada.';
        }

        return null;
    }

    private function markInvoicePaidByStripe(string $invoiceId, array $client, ?string $sessionId): void
    {
        $invoice = $this->facturas->find($invoiceId);
        if (!$invoice || ($invoice['cliente_id'] ?? '') !== ($client['id'] ?? '')) return;
        if (($invoice['estado'] ?? '') === 'Pagada') return;

        $pago = [
            'id'         => (string) Str::ulid(),
            'fecha'      => now()->toDateString(),
            'monto'      => $invoice['total'] ?? 0,
            'metodo'     => 'Stripe',
            'nota'       => $sessionId ? 'Session: '.$sessionId : '',
            'created_at' => now()->toISOString(),
        ];
        $pagos   = $invoice['pagos'] ?? [];
        $pagos[] = $pago;
        $updated = $this->facturas->update($invoiceId, ['estado' => 'Pagada', 'pagos' => $pagos]);
        $this->timeline->add($client['id'], 'pago', ['monto' => $pago['monto'], 'metodo' => 'Stripe']);
        $this->sendInvoicePaidEmail($updated, $client, $pago);
    }

    private function markInvoicePaidByWompi(string $invoiceId, array $client, ?string $reference, ?string $transactionId = null): void
    {
        $invoice = $this->facturas->find($invoiceId);
        if (!$invoice || ($invoice['cliente_id'] ?? '') !== ($client['id'] ?? '')) return;
        if (($invoice['estado'] ?? '') === 'Pagada') return;

        $pago = [
            'id' => (string) Str::ulid(),
            'fecha' => now()->toDateString(),
            'monto' => $invoice['total'] ?? 0,
            'metodo' => 'Wompi',
            'nota' => implode(' | ', array_filter([
                $reference ? 'Referencia: '.$reference : null,
                $transactionId ? 'Transaccion: '.$transactionId : null,
            ])),
            'created_at' => now()->toISOString(),
        ];

        $pagos = $invoice['pagos'] ?? [];
        $pagos[] = $pago;
        $updated = $this->facturas->update($invoiceId, ['estado' => 'Pagada', 'pagos' => $pagos]);
        $this->timeline->add($client['id'], 'pago', ['monto' => $pago['monto'], 'metodo' => 'Wompi']);
        $this->sendInvoicePaidEmail($updated, $client, $pago);
    }

    private function markInvoicePaidByPaypal(string $invoiceId, array $client, ?string $orderId): void
    {
        $invoice = $this->facturas->find($invoiceId);
        if (!$invoice || ($invoice['cliente_id'] ?? '') !== ($client['id'] ?? '')) return;
        if (($invoice['estado'] ?? '') === 'Pagada') return;

        $pago = [
            'id' => (string) Str::ulid(),
            'fecha' => now()->toDateString(),
            'monto' => $invoice['total'] ?? 0,
            'metodo' => 'PayPal',
            'nota' => $orderId ? 'Orden: '.$orderId : '',
            'created_at' => now()->toISOString(),
        ];

        $pagos = $invoice['pagos'] ?? [];
        $pagos[] = $pago;
        $updated = $this->facturas->update($invoiceId, ['estado' => 'Pagada', 'pagos' => $pagos]);
        $this->timeline->add($client['id'], 'pago', ['monto' => $pago['monto'], 'metodo' => 'PayPal']);
        $this->sendInvoicePaidEmail($updated, $client, $pago);
    }

    private function sendInvoicePaidEmail(array $invoice, array $client, array $payment = []): void
    {
        try {
            $to = $client['contacto_email'] ?? $client['email'] ?? null;
            if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
                return;
            }

            $settingsTpl = TemplateMail::settings();
            $linkView = route('facturas.public', (string) ($invoice['id'] ?? ''));
            $linkPdf = route('facturas.public.pdf', (string) ($invoice['id'] ?? ''));

            [$subject, $body] = TemplateMail::render(
                $settingsTpl,
                'template_invoice_paid_subject',
                'template_invoice_paid_body',
                'Factura {folio} pagada',
                "Hola {cliente},\n\nConfirmamos que la factura {folio} fue pagada exitosamente.\n\nTotal: {total}\nFecha de pago: {fecha_pago}\nMetodo de pago: {metodo_pago}\n\nGracias por tu pago.\n{empresa}",
                [
                    'cliente' => (string) (($invoice['cliente'] ?? '') !== '' ? $invoice['cliente'] : ($client['nombre'] ?? 'Cliente')),
                    'folio' => (string) ($invoice['numero'] ?? '---'),
                    'total' => (string) ($invoice['total'] ?? 0),
                    'fecha_pago' => (string) ($payment['fecha'] ?? date('Y-m-d')),
                    'metodo_pago' => (string) ($payment['metodo'] ?? 'Pago confirmado'),
                    'empresa' => (string) ($settingsTpl['company_name'] ?? config('app.name', 'Infocus CRM')),
                ],
                [
                    ['label' => 'Ver factura', 'url' => $linkView, 'kind' => 'secondary'],
                    ['label' => 'Descargar factura', 'url' => $linkPdf, 'kind' => 'primary'],
                ]
            );

            TemplateMail::send((string) $to, $subject, $body);
            $this->notifyOwnerInvoicePaidFromPortal($invoice, $client, $payment);
        } catch (\Throwable $e) {
            // No interrumpir el flujo de pago por un fallo de correo.
        }
    }

    private function notifyOwnerInvoicePaidFromPortal(array $invoice, array $client, array $payment = []): void
    {
        try {
            $settings = TemplateMail::settings();
            $ownerEmail = $settings['recurring_notify_email']
                ?? $settings['mail_from_address']
                ?? $settings['email_from']
                ?? $settings['smtp_username']
                ?? null;

            if (!$ownerEmail || !filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) {
                return;
            }

            $folio = (string) ($invoice['numero'] ?? '---');
            $clientName = (string) (($invoice['cliente'] ?? '') !== '' ? $invoice['cliente'] : ($client['nombre'] ?? 'Cliente'));
            $total = (string) ($invoice['total'] ?? 0);
            $fecha = (string) ($payment['fecha'] ?? date('Y-m-d'));
            $metodo = (string) ($payment['metodo'] ?? 'Checkout cliente');
            $linkView = route('facturas.public', (string) ($invoice['id'] ?? ''));
            $linkPdf = route('facturas.public.pdf', (string) ($invoice['id'] ?? ''));

            $subject = 'Pago confirmado por cliente · Factura ' . $folio;
            $body = '<h3 style="margin:0 0 10px;color:#0f172a;">Pago recibido desde portal del cliente</h3>'
                . '<table style="width:100%;border-collapse:collapse;margin:8px 0 14px;">'
                . '<tr><td style="padding:5px 0;color:#64748b;">Factura</td><td style="padding:5px 0;text-align:right;color:#0f172a;">' . e($folio) . '</td></tr>'
                . '<tr><td style="padding:5px 0;color:#64748b;">Cliente</td><td style="padding:5px 0;text-align:right;color:#0f172a;">' . e($clientName) . '</td></tr>'
                . '<tr><td style="padding:5px 0;color:#64748b;">Monto</td><td style="padding:5px 0;text-align:right;color:#0f172a;">' . e($total) . '</td></tr>'
                . '<tr><td style="padding:5px 0;color:#64748b;">Fecha pago</td><td style="padding:5px 0;text-align:right;color:#0f172a;">' . e($fecha) . '</td></tr>'
                . '<tr><td style="padding:5px 0;color:#64748b;">Método</td><td style="padding:5px 0;text-align:right;color:#0f172a;">' . e($metodo) . '</td></tr>'
                . '</table>'
                . '<div style="text-align:center;margin-top:10px;">'
                . '<a href="' . e($linkView) . '" style="display:inline-block;background:#f1f5f9;color:#0f172a;font-weight:700;font-size:14px;padding:11px 24px;border-radius:999px;text-decoration:none;border:1px solid #e2e8f0;margin-right:8px;">Ver factura</a>'
                . '<a href="' . e($linkPdf) . '" style="display:inline-block;background:#1e293b;color:#f8fafc;font-weight:700;font-size:14px;padding:11px 24px;border-radius:999px;text-decoration:none;">Descargar factura</a>'
                . '</div>';

            TemplateMail::send((string) $ownerEmail, $subject, $body);
        } catch (\Throwable $e) {
            // Evitar afectar flujo principal por fallo en notificación interna.
        }
    }

    public function wompiWebhook(Request $request)
    {
        $payload = $request->all();
        $settings = $this->settings->find('settings') ?: [];
        $eventSecret = trim($this->decryptSetting($settings['wompi_event_secret'] ?? ''));

        if ($eventSecret === '') {
            return response()->json(['ok' => false, 'error' => 'webhook_not_configured'], 503);
        }

        if (!$this->hasValidWompiEventSignature($payload, $eventSecret, (string) $request->header('X-Event-Checksum', ''))) {
            return response()->json(['ok' => false, 'error' => 'invalid_signature'], 401);
        }

        $expectedEnvironment = strtolower((string) ($settings['wompi_mode'] ?? 'test')) === 'live' ? 'prod' : 'test';
        if (strtolower((string) ($payload['environment'] ?? '')) !== $expectedEnvironment) {
            return response()->json(['ok' => true, 'ignored' => 'environment']);
        }

        $event = (string) ($payload['event'] ?? '');
        $data = $payload['data'] ?? [];
        $transaction = is_array($data) ? ($data['transaction'] ?? $data) : [];

        // Process only transaction status updates; ignore other signed events gracefully.
        if ($event !== 'transaction.updated') {
            return response()->json(['ok' => true, 'ignored' => 'event']);
        }

        $status = strtoupper((string) ($transaction['status'] ?? ''));
        if ($status !== 'APPROVED') {
            return response()->json(['ok' => true, 'ignored' => 'status']);
        }

        $reference = (string) ($transaction['reference'] ?? '');
        $invoiceId = $this->extractInvoiceIdFromWompiReference($reference);
        if ($invoiceId === '') {
            return response()->json(['ok' => true, 'ignored' => 'reference']);
        }

        $invoice = $this->facturas->find($invoiceId);
        if (!$invoice) {
            return response()->json(['ok' => true, 'ignored' => 'invoice_not_found']);
        }

        if (!$this->wompiTransactionMatchesInvoice($transaction, $invoice, $reference)) {
            return response()->json(['ok' => false, 'error' => 'transaction_mismatch'], 422);
        }

        $clientId = (string) ($invoice['cliente_id'] ?? '');
        if ($clientId === '') {
            return response()->json(['ok' => true, 'ignored' => 'client_missing']);
        }

        $client = $this->clientes->find($clientId);
        if (!$client) {
            return response()->json(['ok' => true, 'ignored' => 'client_not_found']);
        }

        $this->markInvoicePaidByWompi(
            $invoiceId,
            $client,
            $reference,
            trim((string) ($transaction['id'] ?? ''))
        );

        return response()->json(['ok' => true, 'processed' => true]);
    }

    private function hasValidWompiEventSignature(array $payload, string $eventSecret, string $headerChecksum): bool
    {
        $signature = $payload['signature'] ?? null;
        $properties = is_array($signature) ? ($signature['properties'] ?? null) : null;
        $bodyChecksum = is_array($signature) ? trim((string) ($signature['checksum'] ?? '')) : '';
        $timestamp = $payload['timestamp'] ?? null;
        $data = $payload['data'] ?? null;

        if (!is_array($properties) || $properties === [] || count($properties) > 50 || !is_array($data)) {
            return false;
        }

        $timestamp = is_int($timestamp) || is_string($timestamp) ? (string) $timestamp : '';
        if ($timestamp === '' || preg_match('/^\d{1,20}$/', $timestamp) !== 1) {
            return false;
        }

        $signedValues = '';
        foreach ($properties as $property) {
            if (!is_string($property) || preg_match('/^[A-Za-z0-9_]+(?:\.[A-Za-z0-9_]+)*$/', $property) !== 1) {
                return false;
            }

            $value = $this->wompiEventPropertyValue($data, $property);
            if ($value === null) {
                return false;
            }
            $signedValues .= $value;
        }

        $expectedChecksum = hash('sha256', $signedValues.$timestamp.$eventSecret);
        $providedChecksums = array_values(array_filter([
            strtolower(trim($headerChecksum)),
            strtolower($bodyChecksum),
        ], fn (string $checksum) => $checksum !== ''));

        if ($providedChecksums === []) {
            return false;
        }

        foreach ($providedChecksums as $providedChecksum) {
            if (!hash_equals($expectedChecksum, $providedChecksum)) {
                return false;
            }
        }

        return true;
    }

    private function wompiEventPropertyValue(array $data, string $property): ?string
    {
        $value = $data;
        foreach (explode('.', $property) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }

        return is_scalar($value) ? (string) $value : null;
    }

    private function confirmWompiRedirectPayment(string $invoiceId, array $client, string $transactionId, string $reference): bool
    {
        $invoice = $this->facturas->find($invoiceId);
        if (!$invoice || ($invoice['cliente_id'] ?? '') !== ($client['id'] ?? '')) {
            return false;
        }
        if (($invoice['estado'] ?? '') === 'Pagada') {
            return true;
        }

        $transaction = $this->fetchWompiTransaction($transactionId);
        if (!$transaction || !$this->wompiTransactionMatchesInvoice($transaction, $invoice, $reference)) {
            return false;
        }

        $this->markInvoicePaidByWompi($invoiceId, $client, $reference, $transactionId);

        return true;
    }

    private function fetchWompiTransaction(string $transactionId): ?array
    {
        if (preg_match('/^[A-Za-z0-9._-]{1,255}$/', $transactionId) !== 1) {
            return null;
        }

        $settings = $this->settings->find('settings') ?: [];
        $publicKey = trim((string) ($settings['wompi_public_key'] ?? ''));
        if ($publicKey === '') {
            return null;
        }

        $mode = strtolower((string) ($settings['wompi_mode'] ?? 'test'));
        $apiBase = $mode === 'live' ? 'https://production.wompi.co/v1' : 'https://sandbox.wompi.co/v1';

        try {
            $response = Http::withToken($publicKey)
                ->acceptJson()
                ->timeout(10)
                ->get($apiBase.'/transactions/'.rawurlencode($transactionId));
        } catch (\Throwable) {
            return null;
        }

        $transaction = $response->successful() ? $response->json('data') : null;

        return is_array($transaction) && (string) ($transaction['id'] ?? '') === $transactionId
            ? $transaction
            : null;
    }

    private function wompiTransactionMatchesInvoice(array $transaction, array $invoice, string $reference): bool
    {
        $transactionId = trim((string) ($transaction['id'] ?? ''));
        $transactionReference = trim((string) ($transaction['reference'] ?? ''));
        $transactionStatus = strtoupper((string) ($transaction['status'] ?? ''));
        $transactionCurrency = strtoupper((string) ($transaction['currency'] ?? ''));
        $amountInCents = filter_var($transaction['amount_in_cents'] ?? null, FILTER_VALIDATE_INT);
        $expectedAmountInCents = (int) round((float) ($invoice['total'] ?? 0) * 100);
        $invoiceCurrency = strtoupper((string) ($invoice['moneda'] ?? 'COP'));

        return $transactionId !== ''
            && $transactionStatus === 'APPROVED'
            && $transactionReference !== ''
            && ($reference === '' || hash_equals($reference, $transactionReference))
            && hash_equals((string) ($invoice['id'] ?? ''), $this->extractInvoiceIdFromWompiReference($transactionReference))
            && $amountInCents !== false
            && $amountInCents === $expectedAmountInCents
            && $invoiceCurrency === 'COP'
            && $transactionCurrency === 'COP';
    }

    private function extractInvoiceIdFromWompiReference(string $reference): string
    {
        $reference = trim($reference);
        if ($reference === '') return '';

        if (preg_match('/^INV-([A-Za-z0-9_-]+)-[A-Za-z0-9]{6}$/', $reference, $matches) === 1) {
            return (string) ($matches[1] ?? '');
        }

        return '';
    }

    // ── Magic Link ───────────────────────────────────────────────
    public function showMagicLink()
    {
        $settings = $this->settings->find('settings') ?: [];
        return view('portal.magic-link', compact('settings'));
    }

    public function sendMagicLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $email = strtolower(trim($request->input('email')));

        $client = collect($this->clientes->all())
            ->first(fn($c) => strtolower(trim((string)($c['portal_email'] ?? ''))) === $email
                || strtolower(trim((string)($c['contacto_email'] ?? ''))) === $email
                || strtolower(trim((string)($c['email'] ?? ''))) === $email);

        if ($client) {
            $id    = $client['id'];
            $token = $this->portalTokenForClient((string) $id);
            $url   = route('portal.dashboard', compact('id','token'));
            $settings = $this->settings->find('settings') ?: [];
            $empresa  = $settings['company_name'] ?? 'Mi Empresa';

            try {
                Mail::to($email)->send(new GenericMail(
                    "Tu enlace de acceso al portal - {$empresa}",
                    "<h2 style=\"margin:0 0 14px;font-size:28px;color:#0f172a;\">Tu portal ya te está esperando</h2>".
                    "<p>Hola,</p>".
                    "<p>Preparamos este acceso directo para que puedas entrar a tu <strong>portal de cliente</strong>, revisar tus facturas, consultar documentos y dar seguimiento a tus proyectos en un solo lugar.</p>".
                    "<p style=\"margin:24px 0;\"><a href=\"{$url}\" style=\"display:inline-block;background:#a3e635;color:#0f172a;padding:14px 24px;border-radius:14px;font-weight:800;text-decoration:none;\">Acceder al Portal</a></p>".
                    "<p style=\"margin:0 0 16px;color:#475569;\">Si el botón no se abre directamente, puedes copiar este enlace en tu navegador:</p>".
                    "<p style=\"margin:0 0 20px;padding:14px 16px;border-radius:14px;background:#f8fafc;border:1px solid #e2e8f0;word-break:break-all;color:#0f172a;font-size:14px;\">{$url}</p>".
                    "<p style=\"margin:0;color:#64748b;font-size:13px;\">Por seguridad, este acceso fue solicitado para el portal de {$empresa}. Si no reconoces esta solicitud, puedes ignorar este correo.</p>"
                ));
            } catch (\Throwable $e) {
                // Keep neutral response to avoid email enumeration and SMTP leak details.
            }
        }

        // Always return same message to prevent email enumeration
        return back()->with('magic_sent', 'Si tu correo está registrado, recibirás el enlace en unos momentos.');
    }

    public function redirectToGoogleForPortal(Request $request)
    {
        $clientId = config('services.google.client_id');
        $redirectUri = config('services.google.redirect');

        if (!$clientId || !$redirectUri) {
            return redirect()->route('portal.magic-link');
        }

        $state = Str::random(40);
        $request->session()->put('google_oauth_state', $state);
        $request->session()->put('google_oauth_intent', 'portal');

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?' . $query);
    }

    public function handleGooglePortalCallback(Request $request)
    {
        if ($request->filled('error')) {
            return redirect()->route('portal.magic-link');
        }

        $state = $request->input('state');
        $expectedState = $request->session()->pull('portal_google_oauth_state')
            ?? $request->session()->pull('google_oauth_state');
        if (!$state || $state !== $expectedState) {
            return redirect()->route('portal.magic-link');
        }

        $code = $request->input('code');
        if (!$code) {
            return redirect()->route('portal.magic-link');
        }

        $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => route('portal.magic-link.google.callback'),
            'grant_type' => 'authorization_code',
        ]);

        if (!$tokenResponse->successful()) {
            return redirect()->route('portal.magic-link');
        }

        $accessToken = $tokenResponse->json('access_token');
        $profileResponse = Http::withToken($accessToken)
            ->get('https://openidconnect.googleapis.com/v1/userinfo');

        if (!$profileResponse->successful()) {
            return redirect()->route('portal.magic-link');
        }

        $profile = $profileResponse->json();
        $email = strtolower(trim((string) ($profile['email'] ?? '')));
        $name = (string) ($profile['name'] ?? 'Cliente');
        $verified = (bool) ($profile['email_verified'] ?? false);

        if ($email === '' || !$verified) {
            return redirect()->route('portal.magic-link');
        }

        $client = collect($this->clientes->all())
            ->first(fn($c) => strtolower(trim((string)($c['portal_email'] ?? ''))) === $email
                || strtolower(trim((string)($c['contacto_email'] ?? ''))) === $email
                || strtolower(trim((string)($c['email'] ?? ''))) === $email);

        if (!$client) {
            return redirect()->route('portal.magic-link');
        }

        $user = User::query()->get()->first(function (User $candidate) use ($email) {
            return strtolower(trim((string) $candidate->email)) === $email;
        });

        if (!$user) {
            $user = User::create([
                'name' => $name !== '' ? $name : (string) ($client['nombre'] ?? 'Cliente'),
                'email' => $email,
                'password' => \Illuminate\Support\Facades\Hash::make(Str::random(32)),
                'role' => 'client',
                'cliente_id' => (string) ($client['id'] ?? ''),
            ]);
        } elseif (($user->role ?? '') === 'client' && empty($user->cliente_id)) {
            $user->cliente_id = (string) ($client['id'] ?? '');
            $user->save();
        }

        if (($user->role ?? '') !== 'client') {
            return redirect()->route('portal.magic-link');
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->route('portal.auth.dashboard');
    }
}
