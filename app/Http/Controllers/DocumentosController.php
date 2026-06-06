<?php

namespace App\Http\Controllers;

use App\Repositories\FileStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use ZipArchive;

class DocumentosController extends Controller
{
    protected const DEFAULT_FOLDER_COLOR = '#0ea5e9';

    protected FileStore $documents;
    protected FileStore $folders;
    protected FileStore $clientes;
    protected FileStore $facturas;

    public function __construct()
    {
        $this->documents = new FileStore('documentos.json');
        $this->folders = new FileStore('document_folders.json');
        $this->clientes = new FileStore('clientes.json');
        $this->facturas = new FileStore('facturas.json');
    }

    protected function ensureBillingFolder(string $clienteId): void
    {
        $exists = collect($this->folders->all())
            ->first(fn($f) => (string) ($f['cliente_id'] ?? '') === $clienteId && Str::lower((string) ($f['name'] ?? '')) === 'facturas');

        if (!$exists) {
            $this->folders->create([
                'cliente_id' => $clienteId,
                'name' => 'Facturas',
                'color' => '#0ea5e9',
                'client_visible' => true,
            ]);
        }
    }

    protected function normalizeFolderName(string $name): string
    {
        $clean = trim((string) $name);
        return Str::lower($clean) === 'facturar' ? 'Facturas' : $clean;
    }

    protected function folderParts(string $folder): array
    {
        return collect(explode('/', str_replace('\\', '/', $folder)))
            ->map(fn($p) => trim((string) $p))
            ->filter(fn($p) => $p !== '')
            ->values()
            ->all();
    }

    protected function folderLabel(string $folder): string
    {
        $parts = $this->folderParts($folder);
        return !empty($parts) ? end($parts) : trim((string) $folder);
    }

    protected function folderDisplayLabel(string $folder): string
    {
        $parts = $this->folderParts($folder);
        $label = !empty($parts) ? (string) end($parts) : trim((string) $folder);

        if (
            count($parts) >= 3
            && Str::lower((string) $parts[0]) === 'facturas'
            && preg_match('/^\d{4}$/', (string) $parts[1])
            && preg_match('/^(0?[1-9]|1[0-2])$/', $label)
        ) {
            $months = [
                1 => 'Enero',
                2 => 'Febrero',
                3 => 'Marzo',
                4 => 'Abril',
                5 => 'Mayo',
                6 => 'Junio',
                7 => 'Julio',
                8 => 'Agosto',
                9 => 'Septiembre',
                10 => 'Octubre',
                11 => 'Noviembre',
                12 => 'Diciembre',
            ];
            return $months[(int) $label] ?? $label;
        }

        return $label;
    }

    protected function isProtectedFolder(string $folder): bool
    {
        $parts = $this->folderParts($folder);
        if (count($parts) !== 1) {
            return false;
        }

        $root = Str::lower(Str::ascii((string) ($parts[0] ?? '')));
        return in_array($root, ['facturas', 'proyectos', 'clientes'], true);
    }

    protected function normalizeFolderPath(string $folder): string
    {
        return implode(' / ', $this->folderParts($folder));
    }

    protected function immediateChildPath(string $baseFolder, string $candidate): ?string
    {
        $base = $this->folderParts($baseFolder);
        $cand = $this->folderParts($candidate);
        if (empty($cand)) {
            return null;
        }

        if (empty($base)) {
            return $cand[0] ?? null;
        }

        if (count($cand) <= count($base)) {
            return null;
        }

        foreach ($base as $idx => $seg) {
            if (($cand[$idx] ?? null) !== $seg) {
                return null;
            }
        }

        $child = array_slice($cand, 0, count($base) + 1);
        return implode(' / ', $child);
    }

    protected function normalizeFolderColor(?string $color): string
    {
        $value = trim((string) $color);
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
            return Str::lower($value);
        }

        return self::DEFAULT_FOLDER_COLOR;
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

    public function index(Request $request)
    {
        $clientes = collect($this->clientes->all())->sortBy('empresa')->values();

        $space = trim((string) $request->query('space', 'personal'));
        if (!in_array($space, ['client', 'personal', 'clientes'], true)) {
            $space = 'personal';
        }

        $clienteId = trim((string) $request->query('cliente_id', ''));
        $folder = $this->normalizeFolderPath($this->normalizeFolderName((string) $request->query('folder', '')));
        $q = trim((string) $request->query('q', ''));
        $sort = $request->query('sort', 'recent');
        
        // Detectar si estamos viendo lista de clientes
        $showingClientes = $space === 'clientes';
        // Detectar si estamos en vista inicial (mis carpetas)
        $showingInitial = $space === 'personal' && $folder === '';

        if ($space === 'client' && $clienteId !== '') {
            $this->ensureBillingFolder($clienteId);
        }

        $documentsBase = collect($this->documents->all())
            ->when($space === 'personal', fn($col) => $col->filter(fn($d) => empty($d['cliente_id'] ?? null)))
            ->when($space === 'client', fn($col) => $col->filter(fn($d) => !empty($d['cliente_id'] ?? null)))
            ->when($space === 'clientes', fn($col) => collect()) // Sin documentos en vista de clientes
            ->when($space === 'client' && $clienteId !== '', fn($col) => $col->where('cliente_id', $clienteId))
            ->map(function ($d) {
                $d['folder'] = $this->normalizeFolderPath($this->normalizeFolderName((string) ($d['folder'] ?? '')));
                return $d;
            });

        $invoiceDocs = collect();
        if ($space === 'client' && $clienteId !== '') {
            $invoiceDocs = collect($this->facturas->all())
                ->filter(fn($f) => (string) ($f['cliente_id'] ?? '') === $clienteId)
                ->map(function ($f) use ($clienteId) {
                    $numero = (string) ($f['numero'] ?? $f['id'] ?? 'Factura');
                    $estado = (string) ($f['estado'] ?? 'Pendiente');
                    $vencimiento = (string) ($f['vencimiento'] ?? '');
                    $estadoVisual = $estado;
                    $estadoLower = Str::lower($estado);
                    if (
                        !str_contains($estadoLower, 'pagad')
                        && !str_contains($estadoLower, 'parcial')
                        && $vencimiento !== ''
                    ) {
                        try {
                            if (Carbon::parse($vencimiento)->isPast()) {
                                $estadoVisual = 'Vencida';
                            }
                        } catch (\Throwable) {
                            $estadoVisual = $estado;
                        }
                    }

                    return [
                        'id' => 'factura-'.$f['id'],
                        'source_id' => (string) ($f['id'] ?? ''),
                        'cliente_id' => $clienteId,
                        'folder' => $this->invoiceFolderPath($f),
                        'name' => 'Factura '.$numero,
                        'original_name' => 'Factura '.$numero.'.pdf',
                        'invoice_number' => $numero,
                        'storage' => 'factura',
                        'estado_factura' => $estado,
                        'estado_factura_visual' => $estadoVisual,
                        'vencimiento_factura' => $vencimiento,
                        'uploaded_by' => 'Sistema',
                        'uploaded_at' => (string) ($f['updated_at'] ?? $f['fecha'] ?? now()->toIso8601String()),
                        'size' => null,
                        'mime' => 'application/pdf',
                        'ext' => 'pdf',
                        'created_at' => (string) ($f['updated_at'] ?? $f['fecha'] ?? now()->toIso8601String()),
                    ];
                });
        }

        $documentsBase = $documentsBase->merge($invoiceDocs);

        $documents = $documentsBase
            ->filter(fn($d) => trim((string) ($d['folder'] ?? '')) === $folder)
            ->when($q !== '', fn($col) => $col->filter(function ($d) use ($q) {
                $needle = Str::lower($q);
                $name = Str::lower((string) ($d['name'] ?? ''));
                $orig = Str::lower((string) ($d['original_name'] ?? ''));
                return str_contains($name, $needle) || str_contains($orig, $needle);
            }));

        if ($sort === 'name') {
            $documents = $documents->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE);
        } else {
            $documents = $documents->sortByDesc('created_at');
        }

        $documents = $documents->values();

        $allFolders = collect($this->folders->all());

        $folders = $allFolders
            ->when($space === 'personal', fn($col) => $col->filter(fn($f) => empty($f['cliente_id'] ?? null)))
            ->when($space === 'client', fn($col) => $col->filter(fn($f) => !empty($f['cliente_id'] ?? null)))
            ->when($space === 'client' && $clienteId !== '', fn($col) => $col->where('cliente_id', $clienteId))
            ->when($space === 'clientes', fn($col) => collect()) // Sin carpetas en vista de clientes
            ->map(function ($f) {
                $f['name'] = $this->normalizeFolderPath($this->normalizeFolderName((string) ($f['name'] ?? '')));
                return $f;
            })
            ->unique(fn($f) => Str::lower((string) ($f['name'] ?? '')))
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $personalFolders = $allFolders
            ->filter(fn($f) => empty($f['cliente_id'] ?? null))
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $docGrouped = $documentsBase
            ->groupBy(fn($d) => trim((string) ($d['folder'] ?? '')))
            ->reject(fn($items, $folderName) => $folderName === '');

        $allFolderNames = $folders
            ->pluck('name')
            ->map(fn($n) => trim((string) $n))
            ->filter(fn($n) => $n !== '')
            ->merge($docGrouped->keys())
            ->unique(fn($name) => Str::lower((string) $name))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $visibleFolderPaths = $allFolderNames
            ->map(fn($name) => $this->immediateChildPath($folder, (string) $name))
            ->filter(fn($name) => !empty($name))
            ->unique(fn($name) => Str::lower((string) $name))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $folderMeta = $folders
            ->keyBy(fn($f) => Str::lower((string) ($f['name'] ?? '')));

        $folderStats = $visibleFolderPaths
            ->map(function ($folderPath) use ($docGrouped, $folderMeta) {
                $items = $docGrouped
                    ->filter(function ($rows, $docFolder) use ($folderPath) {
                        $docFolder = (string) $docFolder;
                        return $docFolder === $folderPath || Str::startsWith($docFolder, $folderPath.' / ');
                    })
                    ->flatten(1);
                $count = $items->count();
                $size = $items->sum(function ($d) {
                    return is_numeric($d['size'] ?? null) ? (int) $d['size'] : 0;
                });
                $meta = $folderMeta->get(Str::lower((string) $folderPath), []);

                return [
                    'name' => $this->folderDisplayLabel($folderPath),
                    'path' => $folderPath,
                    'count' => $count,
                    'size' => $size,
                    'color' => $this->normalizeFolderColor($meta['color'] ?? null),
                    'client_visible' => (bool) ($meta['client_visible'] ?? true),
                    'updated_at' => (string) ($meta['updated_at'] ?? $meta['created_at'] ?? now()->toIso8601String()),
                ];
            })
            ->values();

        $dropTargetFolders = $allFolderNames
            ->when($folder !== '', fn($c) => $c->prepend($folder))
            ->unique(fn($name) => Str::lower((string) $name))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->map(fn($path) => ['path' => (string) $path, 'name' => $this->folderDisplayLabel((string) $path)])
            ->values();

        $clienteLookup = $clientes->keyBy('id');

        // Calcular estadísticas por cliente si estamos mostrando lista de clientes
        $clienteStats = collect();
        if ($showingClientes) {
            $allClientDocs = collect($this->documents->all())
                ->filter(fn($d) => !empty($d['cliente_id'] ?? null));
            $allClientFolders = $allFolders
                ->filter(fn($f) => !empty($f['cliente_id'] ?? null));
            
            $clienteStats = $clientes->map(function ($cliente) use ($allClientDocs, $allClientFolders) {
                $clientId = (string) ($cliente['id'] ?? '');
                $clientDocs = $allClientDocs->filter(fn($d) => (string) ($d['cliente_id'] ?? '') === $clientId);
                $clientFolders = $allClientFolders->filter(fn($f) => (string) ($f['cliente_id'] ?? '') === $clientId);
                
                // Contar documentos + carpetas como "elementos"
                $count = $clientDocs->count() + $clientFolders->count();
                $size = $clientDocs->sum(function ($d) {
                    return is_numeric($d['size'] ?? null) ? (int) $d['size'] : 0;
                });
                
                return [
                    'id' => $clientId,
                    'empresa' => (string) ($cliente['empresa'] ?? 'Cliente'),
                    'count' => $count,
                    'size' => $size,
                    'color' => '#0ea5e9',
                ];
            });
        }

        return view('documentos.index', compact('clientes', 'documents', 'folders', 'personalFolders', 'folderStats', 'dropTargetFolders', 'clienteId', 'folder', 'q', 'sort', 'space', 'clienteLookup', 'showingClientes', 'showingInitial', 'clienteStats'));
    }

    public function storeFolder(Request $request)
    {
        $data = $request->validate([
            'scope' => 'required|in:client,personal',
            'cliente_id' => 'nullable|string',
            'name' => 'required|string|max:120',
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $scope = $data['scope'];
        $clienteId = $scope === 'client' ? trim((string) ($data['cliente_id'] ?? '')) : '';

        if ($scope === 'client') {
            abort_if($clienteId === '', 422);
            $cliente = $this->clientes->find($clienteId);
            abort_if(!$cliente, 404);
        }

        $name = $this->normalizeFolderName($data['name']);
        $color = $this->normalizeFolderColor($data['color'] ?? null);

        $exists = collect($this->folders->all())
            ->first(fn($f) => (string) ($f['cliente_id'] ?? '') === (string) $clienteId && Str::lower((string) ($f['name'] ?? '')) === Str::lower($name));

        if (!$exists) {
            $this->folders->create([
                'cliente_id' => $clienteId !== '' ? $clienteId : null,
                'name' => $name,
                'color' => $color,
                'client_visible' => $scope === 'client',
            ]);
        }

        $redirectQuery = ['space' => $scope];
        if ($scope === 'client' && $clienteId !== '') {
            $redirectQuery['cliente_id'] = $clienteId;
        }

        return redirect()->route('documentos.index', $redirectQuery)->with('success', 'Carpeta creada correctamente.');
    }

    public function upload(Request $request)
    {
        $data = $request->validate([
            'scope' => 'required|in:client,personal',
            'cliente_id' => 'nullable|string',
            'folder' => 'required|string|max:120',
            'storage_mode' => 'required|in:local,drive',
            'archivo' => 'nullable|file|max:204800',
            'drive_url' => 'nullable|url|max:2000',
            'name' => 'nullable|string|max:255',
            'folder_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $scope = $data['scope'];
        $clienteId = $scope === 'client' ? trim((string) ($data['cliente_id'] ?? '')) : '';
        $cliente = null;

        if ($scope === 'client') {
            if ($clienteId === '') {
                return back()->withErrors(['cliente_id' => 'Debes seleccionar un cliente.'])->withInput();
            }

            $cliente = $this->clientes->find($clienteId);
            abort_if(!$cliente, 404);
        }

        $folderName = $this->normalizeFolderName($data['folder']);
        $folderColor = $this->normalizeFolderColor($data['folder_color'] ?? null);
        $mode = $data['storage_mode'];
        $customName = trim((string) ($data['name'] ?? ''));
        $uploadedBy = Auth::user()->name ?? 'Equipo';
        $uploadedAt = now()->toIso8601String();

        $folderExists = collect($this->folders->all())
            ->first(fn($f) => (string) ($f['cliente_id'] ?? '') === (string) $clienteId && Str::lower((string) ($f['name'] ?? '')) === Str::lower($folderName));
        if (!$folderExists) {
            $this->folders->create([
                'cliente_id' => $clienteId !== '' ? $clienteId : null,
                'name' => $folderName,
                'color' => $folderColor,
                'client_visible' => $scope === 'client',
            ]);
        }

        if ($mode === 'drive') {
            if (empty($data['drive_url'])) {
                return back()->withErrors(['drive_url' => 'Debes ingresar el enlace del archivo en Google Drive.'])->withInput();
            }

            $this->documents->create([
                'cliente_id' => $clienteId !== '' ? $clienteId : null,
                'folder' => $folderName,
                'name' => $customName !== '' ? $customName : ('Drive - '.now()->format('YmdHis')),
                'original_name' => $customName !== '' ? $customName : 'Archivo en Drive',
                'storage' => 'drive',
                'drive_url' => $data['drive_url'],
                'uploaded_by' => $uploadedBy,
                'uploaded_at' => $uploadedAt,
                'size' => null,
                'mime' => null,
                'ext' => null,
            ]);

            $redirectQuery = ['space' => $scope];
            if ($scope === 'client' && $clienteId !== '') {
                $redirectQuery['cliente_id'] = $clienteId;
            }
            $redirectQuery['folder'] = $folderName;

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'ok' => true,
                    'redirect' => route('documentos.index', $redirectQuery),
                    'message' => 'Documento vinculado desde Google Drive.',
                ]);
            }

            return redirect()->route('documentos.index', $redirectQuery)->with('success', 'Documento vinculado desde Google Drive.');
        }

        if (!$request->hasFile('archivo')) {
            return back()->withErrors(['archivo' => 'Debes seleccionar un archivo para subir.'])->withInput();
        }

        $file = $request->file('archivo');
        $origName = $file->getClientOriginalName();
        $ext = strtolower((string) $file->getClientOriginalExtension());
        $safeClient = $scope === 'client'
            ? 'infocus-'.Str::slug((string) ($cliente['empresa'] ?? 'cliente'))
            : 'workspace-personal';
        $safeFolder = Str::slug($folderName);
        $base = Str::slug(pathinfo($origName, PATHINFO_FILENAME));
        $finalName = now()->format('YmdHis').'_'.Str::ulid().'_'.$base.($ext ? '.'.$ext : '');
        $path = 'documentos/'.$safeClient.'/'.$safeFolder.'/'.$finalName;

        Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));

        $this->documents->create([
            'cliente_id' => $clienteId !== '' ? $clienteId : null,
            'folder' => $folderName,
            'name' => $customName !== '' ? $customName : pathinfo($origName, PATHINFO_FILENAME),
            'original_name' => $origName,
            'storage' => 'local',
            'path' => $path,
            'uploaded_by' => $uploadedBy,
            'uploaded_at' => $uploadedAt,
            'size' => $file->getSize(),
            'mime' => $file->getMimeType(),
            'ext' => $ext,
        ]);

        $redirectQuery = ['space' => $scope];
        if ($scope === 'client' && $clienteId !== '') {
            $redirectQuery['cliente_id'] = $clienteId;
        }
        $redirectQuery['folder'] = $folderName;

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'redirect' => route('documentos.index', $redirectQuery),
                'message' => 'Documento subido correctamente.',
            ]);
        }

        return redirect()->route('documentos.index', $redirectQuery)->with('success', 'Documento subido correctamente.');
    }

    public function download(string $id)
    {
        $doc = $this->documents->find($id);
        abort_if(!$doc, 404);

        if (($doc['storage'] ?? 'local') === 'drive') {
            $url = $doc['drive_url'] ?? '';
            abort_if($url === '', 404);
            return redirect()->away($url);
        }

        $path = $doc['path'] ?? '';
        abort_if($path === '' || !Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->download($path, $doc['original_name'] ?? basename($path));
    }

    public function preview(string $id)
    {
        $doc = $this->documents->find($id);
        abort_if(!$doc, 404);
        abort_if(($doc['storage'] ?? 'local') !== 'local', 404);

        $path = (string) ($doc['path'] ?? '');
        abort_if($path === '' || !Storage::disk('public')->exists($path), 404);

        $absolutePath = Storage::disk('public')->path($path);
        $mime = (string) ($doc['mime'] ?? mime_content_type($absolutePath) ?: 'application/octet-stream');

        return response()->file($absolutePath, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.addslashes((string) ($doc['original_name'] ?? basename($path))).'"',
        ]);
    }

    public function downloadFolder(Request $request)
    {
        $data = $request->validate([
            'scope' => 'required|in:client,personal',
            'cliente_id' => 'nullable|string',
            'name' => 'required|string|max:120',
        ]);

        $scope = $data['scope'];
        $clienteId = $scope === 'client' ? trim((string) ($data['cliente_id'] ?? '')) : '';
        $folderName = $this->normalizeFolderName($data['name']);

        $rows = collect($this->documents->all())
            ->filter(function ($d) use ($scope, $clienteId, $folderName) {
                $sameScope = $scope === 'client'
                    ? (string) ($d['cliente_id'] ?? '') === (string) $clienteId
                    : empty($d['cliente_id'] ?? null);
                $sameFolder = Str::lower((string) ($d['folder'] ?? '')) === Str::lower($folderName);
                return $sameScope && $sameFolder;
            })
            ->values();

        abort_if($rows->isEmpty(), 404);

        $tmpDir = storage_path('app/tmp');
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }

        $zipName = Str::slug($folderName).'-'.now()->format('YmdHis').'.zip';
        $zipPath = $tmpDir.'/'.$zipName;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'No se pudo generar el archivo ZIP.');
        }

        foreach ($rows as $doc) {
            $display = (string) ($doc['original_name'] ?? ($doc['name'] ?? 'archivo'));
            if (($doc['storage'] ?? 'local') === 'local') {
                $path = (string) ($doc['path'] ?? '');
                if ($path !== '' && Storage::disk('public')->exists($path)) {
                    $zip->addFile(Storage::disk('public')->path($path), $display);
                }
            }

            if (($doc['storage'] ?? '') === 'drive') {
                $url = (string) ($doc['drive_url'] ?? '');
                if ($url !== '') {
                    $zip->addFromString(pathinfo($display, PATHINFO_FILENAME).'.url.txt', $url);
                }
            }
        }

        $zip->close();

        return response()->download($zipPath, Str::slug($folderName).'.zip')->deleteFileAfterSend(true);
    }

    public function update(Request $request, string $id)
    {
        $doc = $this->documents->find($id);
        abort_if(!$doc, 404);
        abort_if(($doc['storage'] ?? '') === 'factura', 422, 'Las facturas del sistema no se editan aqui.');

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'folder' => 'nullable|string|max:240',
        ]);

        $updates = [
            'name' => trim($data['name']),
        ];

        if ($request->filled('folder')) {
            $updates['folder'] = $this->normalizeFolderPath($this->normalizeFolderName((string) $data['folder']));
        }

        $this->documents->update($id, $updates);

        return back()->with('success', 'Archivo actualizado.');
    }

    public function updateFolder(Request $request)
    {
        $data = $request->validate([
            'scope' => 'required|in:client,personal',
            'cliente_id' => 'nullable|string',
            'current_name' => 'required|string|max:120',
            'name' => 'required|string|max:120',
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $scope = $data['scope'];
        $clienteId = $scope === 'client' ? trim((string) ($data['cliente_id'] ?? '')) : '';
        $currentName = $this->normalizeFolderName($data['current_name']);
        $newName = $this->normalizeFolderName($data['name']);
        $newColor = $this->normalizeFolderColor($data['color'] ?? null);

        abort_if($currentName === '' || $newName === '', 422);

        $foldersAll = collect($this->folders->all());
        $foldersAll = $foldersAll->map(function ($f) use ($currentName, $newName, $newColor, $clienteId) {
            $sameScope = (string) ($f['cliente_id'] ?? '') === (string) $clienteId;
            $sameName = Str::lower((string) ($f['name'] ?? '')) === Str::lower($currentName);
            if ($sameScope && $sameName) {
                $f['name'] = $newName;
                $f['color'] = $newColor;
            }
            return $f;
        });
        $this->folders->save($foldersAll->values()->all());

        $docsAll = collect($this->documents->all());
        $docsAll = $docsAll->map(function ($d) use ($currentName, $newName, $clienteId) {
            $sameScope = (string) ($d['cliente_id'] ?? '') === (string) $clienteId;
            $sameFolder = Str::lower((string) ($d['folder'] ?? '')) === Str::lower($currentName);
            if ($sameScope && $sameFolder) {
                $d['folder'] = $newName;
                $d['updated_at'] = now()->toISOString();
            }
            return $d;
        });
        $this->documents->save($docsAll->values()->all());

        return back()->with('success', 'Carpeta actualizada.');
    }

    public function updateFolderVisibility(Request $request)
    {
        $data = $request->validate([
            'scope' => 'required|in:client,personal',
            'cliente_id' => 'nullable|string',
            'name' => 'required|string|max:240',
            'client_visible' => 'required|boolean',
        ]);

        $scope = $data['scope'];
        $clienteId = $scope === 'client' ? trim((string) ($data['cliente_id'] ?? '')) : '';
        $folderName = $this->normalizeFolderPath($this->normalizeFolderName((string) $data['name']));
        $visible = (bool) $data['client_visible'];

        abort_if($folderName === '', 422, 'Carpeta invalida.');
        if ($scope === 'client') {
            abort_if($clienteId === '', 422, 'Cliente invalido.');
        }

        $foldersAll = collect($this->folders->all());
        $updated = false;

        $foldersAll = $foldersAll->map(function ($f) use ($clienteId, $folderName, $visible, &$updated) {
            $sameScope = (string) ($f['cliente_id'] ?? '') === (string) $clienteId;
            $sameName = Str::lower((string) ($f['name'] ?? '')) === Str::lower($folderName);
            if ($sameScope && $sameName) {
                $f['client_visible'] = $visible;
                $updated = true;
            }
            return $f;
        });

        if (!$updated) {
            $foldersAll->push([
                'cliente_id' => $clienteId !== '' ? $clienteId : null,
                'name' => $folderName,
                'color' => '#0ea5e9',
                'client_visible' => $visible,
            ]);
        }

        $this->folders->save($foldersAll->values()->all());

        return response()->json(['ok' => true, 'client_visible' => $visible]);
    }

    public function destroyFolder(Request $request)
    {
        $data = $request->validate([
            'scope' => 'required|in:client,personal',
            'cliente_id' => 'nullable|string',
            'name' => 'required|string|max:120',
        ]);

        $scope = $data['scope'];
        $clienteId = $scope === 'client' ? trim((string) ($data['cliente_id'] ?? '')) : '';
        $folderName = $this->normalizeFolderPath($this->normalizeFolderName($data['name']));

        abort_if($this->isProtectedFolder($folderName), 422, 'Esta carpeta del sistema no se puede eliminar.');

        $folderRows = collect($this->folders->all());
        $folderRows = $folderRows->reject(function ($f) use ($clienteId, $folderName) {
            $sameScope = (string) ($f['cliente_id'] ?? '') === (string) $clienteId;
            $sameName = Str::lower((string) ($f['name'] ?? '')) === Str::lower($folderName);
            return $sameScope && $sameName;
        });
        $this->folders->save($folderRows->values()->all());

        $docsAll = collect($this->documents->all());
        $toDelete = $docsAll->filter(function ($d) use ($clienteId, $folderName) {
            $sameScope = (string) ($d['cliente_id'] ?? '') === (string) $clienteId;
            $sameFolder = Str::lower((string) ($d['folder'] ?? '')) === Str::lower($folderName);
            return $sameScope && $sameFolder;
        });

        foreach ($toDelete as $doc) {
            if (($doc['storage'] ?? 'local') === 'local') {
                $path = $doc['path'] ?? '';
                if ($path !== '' && Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
        }

        $docsAll = $docsAll->reject(function ($d) use ($clienteId, $folderName) {
            $sameScope = (string) ($d['cliente_id'] ?? '') === (string) $clienteId;
            $sameFolder = Str::lower((string) ($d['folder'] ?? '')) === Str::lower($folderName);
            return $sameScope && $sameFolder;
        });
        $this->documents->save($docsAll->values()->all());

        return back()->with('success', 'Carpeta eliminada.');
    }

    public function destroy(string $id)
    {
        $doc = $this->documents->find($id);
        abort_if(!$doc, 404);
        abort_if(($doc['storage'] ?? 'local') === 'factura', 422, 'Las facturas del sistema no se eliminan aqui.');

        if (($doc['storage'] ?? 'local') === 'local') {
            $path = $doc['path'] ?? '';
            if ($path !== '' && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        $this->documents->delete($id);

        return redirect()->back()->with('success', 'Documento eliminado.');
    }

    public function move(Request $request)
    {
        $data = $request->validate([
            'scope' => 'required|in:client,personal',
            'cliente_id' => 'nullable|string',
            'item_type' => 'required|in:document,folder',
            'item_id' => 'nullable|string',
            'current_name' => 'nullable|string|max:240',
            'target_folder' => 'required|string|max:240',
        ]);

        $scope = $data['scope'];
        $clienteId = $scope === 'client' ? trim((string) ($data['cliente_id'] ?? '')) : '';
        $targetFolder = $this->normalizeFolderPath($this->normalizeFolderName((string) $data['target_folder']));
        abort_if($targetFolder === '', 422, 'Carpeta destino invalida.');

        if ($data['item_type'] === 'document') {
            $docId = trim((string) ($data['item_id'] ?? ''));
            abort_if($docId === '', 422, 'Documento invalido.');
            $doc = $this->documents->find($docId);
            abort_if(!$doc, 404);

            $sameScope = $scope === 'client'
                ? (string) ($doc['cliente_id'] ?? '') === $clienteId
                : empty($doc['cliente_id'] ?? null);
            abort_if(!$sameScope, 422, 'Documento fuera de alcance.');

            $this->documents->update($docId, ['folder' => $targetFolder]);
            return response()->json(['ok' => true]);
        }

        $currentName = $this->normalizeFolderPath($this->normalizeFolderName((string) ($data['current_name'] ?? '')));
        abort_if($currentName === '', 422, 'Carpeta invalida.');
        abort_if(Str::lower($currentName) === Str::lower($targetFolder), 422, 'La carpeta ya esta en ese destino.');
        abort_if(Str::startsWith(Str::lower($targetFolder), Str::lower($currentName).' / '), 422, 'No puedes mover una carpeta dentro de si misma.');

        $baseName = $this->folderLabel($currentName);
        $newFolder = $this->normalizeFolderPath($targetFolder.' / '.$baseName);

        $foldersAll = collect($this->folders->all());
        $foldersAll = $foldersAll->map(function ($f) use ($clienteId, $currentName, $newFolder) {
            if ((string) ($f['cliente_id'] ?? '') !== (string) $clienteId) {
                return $f;
            }
            $name = $this->normalizeFolderPath((string) ($f['name'] ?? ''));
            if ($name === $currentName || Str::startsWith($name, $currentName.' / ')) {
                $suffix = Str::after($name, $currentName);
                $f['name'] = $newFolder.ltrim($suffix, ' ');
            }
            return $f;
        })->values()->all();
        $this->folders->save($foldersAll);

        $docsAll = collect($this->documents->all());
        $docsAll = $docsAll->map(function ($d) use ($clienteId, $currentName, $newFolder) {
            if ((string) ($d['cliente_id'] ?? '') !== (string) $clienteId) {
                return $d;
            }
            $docFolder = $this->normalizeFolderPath((string) ($d['folder'] ?? ''));
            if ($docFolder === $currentName || Str::startsWith($docFolder, $currentName.' / ')) {
                $suffix = Str::after($docFolder, $currentName);
                $d['folder'] = $newFolder.ltrim($suffix, ' ');
                $d['updated_at'] = now()->toISOString();
            }
            return $d;
        })->values()->all();
        $this->documents->save($docsAll);

        return response()->json(['ok' => true]);
    }
}
