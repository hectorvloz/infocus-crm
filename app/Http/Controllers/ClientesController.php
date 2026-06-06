<?php

namespace App\Http\Controllers;

use App\Repositories\FileStore;
use App\Repositories\TimelineStore;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File as Fs;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ClientesController extends Controller
{
    protected FileStore $store;
    protected FileStore $facturas;
    protected FileStore $gastos;
    protected TimelineStore $timeline;

    public function __construct()
    {
        $this->store = new FileStore('clientes.json');
        $this->facturas = new FileStore('facturas.json');
        $this->gastos = new FileStore('gastos.json');
        $this->timeline = new TimelineStore();
    }

    protected function saveAvatar(UploadedFile $file): array
    {
        $dir = public_path('uploads/clientes');
        Fs::ensureDirectoryExists($dir);
        $base = (string) Str::ulid();
        $name = $base.'.jpg';
        $thumb = $base.'_64.jpg';
        $path = $dir.DIRECTORY_SEPARATOR.$name;
        $pathThumb = $dir.DIRECTORY_SEPARATOR.$thumb;
        if (extension_loaded('gd')) {
            $data = file_get_contents($file->getRealPath());
            $src = @imagecreatefromstring($data);
            if ($src !== false) {
                $w = imagesx($src); $h = imagesy($src);
                $size = min($w, $h);
                $x = (int) max(0, floor(($w - $size) / 2));
                $y = (int) max(0, floor(($h - $size) / 2));
                if (function_exists('imagecrop')) {
                    $crop = imagecrop($src, ['x'=>$x,'y'=>$y,'width'=>$size,'height'=>$size]) ?: $src;
                } else {
                    $crop = imagecreatetruecolor($size, $size);
                    imagecopy($crop, $src, 0, 0, $x, $y, $size, $size);
                }
                // 512
                $dst = imagecreatetruecolor(512, 512);
                imagecopyresampled($dst, $crop, 0, 0, 0, 0, 512, 512, $size, $size);
                imagejpeg($dst, $path, 82);
                imagedestroy($dst);
                // 64
                $dst2 = imagecreatetruecolor(64, 64);
                imagecopyresampled($dst2, $crop, 0, 0, 0, 0, 64, 64, $size, $size);
                imagejpeg($dst2, $pathThumb, 82);
                imagedestroy($dst2);
                imagedestroy($src);
                if (isset($crop) && $crop !== $src) @imagedestroy($crop);
            } else {
                $file->move($dir, $name);
                copy($dir.DIRECTORY_SEPARATOR.$name, $pathThumb);
            }
        } else {
            $file->move($dir, $name);
            copy($dir.DIRECTORY_SEPARATOR.$name, $pathThumb);
        }
        return [
            'avatar' => '/uploads/clientes/'.$name,
            'avatar_thumb' => '/uploads/clientes/'.$thumb,
        ];
    }

    protected function initialClientPasswordFromNit(?string $nit): string
    {
        $raw = trim((string) $nit);
        $digits = preg_replace('/\D+/', '', $raw) ?: '';
        return $digits !== '' ? $digits : $raw;
    }

    protected function allowedCurrencies(): array
    {
        return ['USD','EUR','MXN','COP','ARS','CLP','PEN','GBP','CAD','JPY','AUD','CNY','CHF','HKD','NZD','SEK','KRW','SGD','INR','BRL','RUB','ZAR','TRY'];
    }

    protected function isPlaceholderClient(array $cliente): bool
    {
        return Str::lower(trim((string) ($cliente['empresa'] ?? ''))) === 'sin cliente';
    }

    protected function syncPortalUserForClient(array $cliente): void
    {
        $email = strtolower(trim((string) ($cliente['contacto_email'] ?? '')));
        $clientUser = User::where('role', 'client')->where('cliente_id', $cliente['id'])->first();

        if ($email === '') {
            // Si el cliente ya no tiene correo, removemos acceso de portal.
            if ($clientUser) {
                $clientUser->delete();
            }
            return;
        }

        $password = $this->initialClientPasswordFromNit($cliente['nit'] ?? null);
        if ($password === '') {
            throw ValidationException::withMessages([
                'nit' => 'Para habilitar acceso al portal, este cliente debe tener NIT.',
            ]);
        }

        $existingByEmail = User::where('email', $email)->first();

        if ($existingByEmail && (!$clientUser || $existingByEmail->id !== $clientUser->id)) {
            if (($existingByEmail->role ?? null) !== 'client' || ($existingByEmail->cliente_id ?? null) !== ($cliente['id'] ?? null)) {
                throw ValidationException::withMessages([
                    'contacto_email' => 'El correo ya está en uso por otro usuario del sistema.',
                ]);
            }
        }

        if ($clientUser) {
            $clientUser->name = $cliente['contacto_nombre'] ?: ($cliente['empresa'] ?? 'Cliente');
            $clientUser->email = $email;
            $clientUser->role = 'client';
            $clientUser->cliente_id = $cliente['id'];
            $clientUser->save();
            return;
        }

        if ($existingByEmail) {
            $existingByEmail->name = $cliente['contacto_nombre'] ?: ($cliente['empresa'] ?? 'Cliente');
            $existingByEmail->role = 'client';
            $existingByEmail->cliente_id = $cliente['id'];
            $existingByEmail->save();
            return;
        }

        User::create([
            'name' => $cliente['contacto_nombre'] ?: ($cliente['empresa'] ?? 'Cliente'),
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'client',
            'cliente_id' => $cliente['id'],
            'must_change_password' => true,
        ]);
    }

    protected function withAggregates(array $clientes): array
    {
        $facturas = collect($this->facturas->all());
        $settings = (new FileStore('settings.json'))->find('settings') ?: [];
        $baseCurrency = $settings['base_currency'] ?? 'USD';
        return collect($clientes)->map(function ($c) use ($facturas, $baseCurrency) {
            $cFacturas = $facturas->where('cliente', $c['empresa'] ?? '');
            $c['facturas_total_base'] = round($cFacturas->sum(fn($f) => (float)($f['total_base'] ?? $f['total'] ?? 0)), 2);
            $c['facturas_total'] = $c['facturas_total_base'];
            $c['facturas_desglose'] = $cFacturas
                ->filter(fn($f) => !empty($f['moneda']) && $f['moneda'] !== $baseCurrency)
                ->groupBy('moneda')
                ->map(fn($g) => round($g->sum('total'), 2))
                ->filter(fn($v) => $v > 0)
                ->all();
            $c['proyectos'] = $c['proyectos'] ?? 0;
            return $c;
        })->all();
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $sort = $request->query('sort', 'empresa');
        $dir = strtolower($request->query('dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $estado = $request->query('estado');
        $nit = trim((string) $request->query('nit', ''));
        $categoria = trim((string) $request->query('categoria', ''));

        $clientes = collect($this->store->all())
            ->reject(fn ($cliente) => $this->isPlaceholderClient($cliente))
            ->values()
            ->all();
        $clientes = $this->withAggregates($clientes);
        // enrich with recent invoices
        $facturas = collect($this->facturas->all());
        $clientes = collect($clientes)->map(function($c) use ($facturas) {
            $recent = $facturas->where('cliente', $c['empresa'] ?? '')
                ->sortByDesc('fecha')->take(3)->values()->all();
            $c['recent'] = $recent;
            $c['pendientes'] = $facturas->where('cliente', $c['empresa'] ?? '')
                ->whereIn('estado', ['Pendiente','Enviada','Vencida'])->count();
            return $c;
        })->all();
        $clientes = collect($clientes)
            ->when($q !== '', fn($col) => $col->filter(fn($i) =>
                str_contains(Str::lower($i['empresa'] ?? ''), Str::lower($q)) ||
                str_contains(Str::lower($i['propietario'] ?? ''), Str::lower($q))
            ))
            ->when($estado, fn($col) => $col->where('estado', $estado))
            ->when($nit !== '', fn($col) => $col->filter(fn($i) => str_contains(Str::lower($i['nit'] ?? ''), Str::lower($nit))))
            ->when($categoria !== '', fn($col) => $col->filter(fn($i) => str_contains(Str::lower($i['categoria'] ?? ''), Str::lower($categoria))))
            ->sortBy($sort, SORT_REGULAR, $dir === 'desc')
            ->values()
            ->all();

        $viewMode = $request->query('vista');
        $settings = (new FileStore('settings.json'))->find('settings') ?: [];
        $baseCurrency = $settings['base_currency'] ?? 'USD';
        return view('clientes.index', compact('clientes', 'q', 'sort', 'dir', 'estado', 'viewMode', 'nit', 'categoria', 'baseCurrency'));
    }
    public function create()
    {
        $settings = (new FileStore('settings.json'))->find('settings') ?: [];
        $baseCurrency = strtoupper((string) ($settings['base_currency'] ?? 'USD'));
        $currencies = $this->allowedCurrencies();
        return view('clientes.create', compact('baseCurrency', 'currencies'));
    }

    public function show(string $id)
    {
        $cliente = $this->store->find($id);
        abort_if(!$cliente, 404);

        $settings = (new FileStore('settings.json'))->find('settings') ?: [];
        $baseCurrency = $settings['base_currency'] ?? 'USD';

        $facturas = collect($this->facturas->all())->where('cliente', $cliente['empresa'] ?? '');

        // Total en moneda base (igual que FacturasController)
        $effTotal = fn($f) => (float) ($f['total_base'] ?? $f['total'] ?? 0);
        $total = round($facturas->sum(fn($f) => $effTotal($f)), 2);

        // Desglose por moneda extranjera
        $totalesPorMoneda = $facturas
            ->filter(fn($f) => !empty($f['moneda']) && $f['moneda'] !== $baseCurrency)
            ->groupBy('moneda')
            ->map(fn($grupo) => round($grupo->sum('total'), 2))
            ->filter(fn($v) => $v > 0)
            ->all();

        $pagadas = $facturas->where('estado', 'Pagada')->count();
        $pendientes = $facturas->whereIn('estado', ['Pendiente', 'Enviada', 'Vencida'])->count();
        $gastos = collect($this->gastos->all())->where('cliente_id', $id);
        $totalGastos = round($gastos->sum('monto'), 2);
        $timeline = $this->timeline->for($id);
        return view('clientes.show', compact('cliente', 'total', 'totalesPorMoneda', 'baseCurrency', 'pagadas', 'pendientes', 'gastos', 'totalGastos', 'facturas', 'timeline'));
    }

    public function addNota(Request $request, string $id)
    {
        $request->validate(['nota' => 'required|string']);
        $cliente = $this->store->find($id);
        abort_if(!$cliente, 404);
        $this->timeline->add($id, 'nota', ['texto' => $request->input('nota')]);
        return redirect()->route('clientes.show', $id);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'empresa' => 'required|string',
            'propietario' => 'nullable|string',
            'nit' => 'nullable|string',
            'categoria' => 'nullable|string',
            'etiquetas' => 'nullable|string',
            'estado' => 'required|string',
            'contacto_nombre' => 'nullable|string',
            'contacto_email' => 'nullable|email',
            'contacto_telefono' => 'nullable|string',
            'direccion' => 'nullable|string',
            'ciudad' => 'nullable|string',
            'pais' => 'nullable|string',
            'codigo_postal' => 'nullable|string',
            'website' => 'nullable|url',
            'moneda' => 'nullable|in:' . implode(',', $this->allowedCurrencies()),
            'avatar' => 'nullable|image|max:2048',
            'invoice_fields' => 'nullable|array',
            'invoice_fields.nit' => 'nullable|boolean',
            'invoice_fields.direccion' => 'nullable|boolean',
            'invoice_fields.telefono' => 'nullable|boolean',
            'invoice_fields.email' => 'nullable|boolean',
        ]);
        $settings = (new FileStore('settings.json'))->find('settings') ?: [];
        $baseCurrency = strtoupper((string) ($settings['base_currency'] ?? 'USD'));
        $data['proyectos'] = 0;
        $data['moneda'] = strtoupper((string) ($data['moneda'] ?? $baseCurrency));
        $data['invoice_fields'] = array_merge(
            ['nit'=>true,'direccion'=>true,'telefono'=>true,'email'=>true],
            $data['invoice_fields'] ?? []
        );
        $keys = $request->input('custom_keys', []);
        $vals = $request->input('custom_values', []);
        $custom = [];
        if (is_array($keys) && is_array($vals)) {
            foreach ($keys as $i => $k) {
                $k = trim((string) $k);
                $v = trim((string) ($vals[$i] ?? ''));
                if ($k !== '' && $v !== '') {
                    $custom[$k] = $v;
                }
            }
        }
        if ($custom !== []) $data['custom_fields'] = $custom;
        if ($request->hasFile('avatar')) {
            $saved = $this->saveAvatar($request->file('avatar'));
            $data = array_merge($data, $saved);
        }
        $created = $this->store->create($data);
        $this->syncPortalUserForClient($created);
        return redirect()->route('clientes.index')->with('success', 'Cliente creado correctamente. Acceso portal: correo del cliente, clave inicial = NIT.');
    }

    public function edit(string $id)
    {
        $cliente = $this->store->find($id);
        abort_if(!$cliente, 404);
        $settings = (new FileStore('settings.json'))->find('settings') ?: [];
        $baseCurrency = strtoupper((string) ($settings['base_currency'] ?? 'USD'));
        $currencies = $this->allowedCurrencies();
        return view('clientes.edit', compact('cliente', 'baseCurrency', 'currencies'));
    }

    public function update(Request $request, string $id)
    {
        $current = $this->store->find($id);
        $data = $request->validate([
            'empresa' => 'required|string',
            'propietario' => 'nullable|string',
            'nit' => 'nullable|string',
            'categoria' => 'nullable|string',
            'etiquetas' => 'nullable|string',
            'estado' => 'required|string',
            'contacto_nombre' => 'nullable|string',
            'contacto_email' => 'nullable|email',
            'contacto_telefono' => 'nullable|string',
            'direccion' => 'nullable|string',
            'ciudad' => 'nullable|string',
            'pais' => 'nullable|string',
            'codigo_postal' => 'nullable|string',
            'website' => 'nullable|url',
            'moneda' => 'nullable|in:' . implode(',', $this->allowedCurrencies()),
            'avatar' => 'nullable|image|max:2048',
            'remove_avatar' => 'nullable|boolean',
            'invoice_fields' => 'nullable|array',
            'invoice_fields.nit' => 'nullable|boolean',
            'invoice_fields.direccion' => 'nullable|boolean',
            'invoice_fields.telefono' => 'nullable|boolean',
            'invoice_fields.email' => 'nullable|boolean',
        ]);
        $settings = (new FileStore('settings.json'))->find('settings') ?: [];
        $baseCurrency = strtoupper((string) ($settings['base_currency'] ?? 'USD'));
        $data['invoice_fields'] = array_merge(
            $current['invoice_fields'] ?? ['nit'=>true,'direccion'=>true,'telefono'=>true,'email'=>true],
            $data['invoice_fields'] ?? []
        );
        $data['moneda'] = strtoupper((string) ($data['moneda'] ?? ($current['moneda'] ?? $baseCurrency)));
        $keys = $request->input('custom_keys', []);
        $vals = $request->input('custom_values', []);
        $custom = [];
        if (is_array($keys) && is_array($vals)) {
            foreach ($keys as $i => $k) {
                $k = trim((string) $k);
                $v = trim((string) ($vals[$i] ?? ''));
                if ($k !== '' && $v !== '') {
                    $custom[$k] = $v;
                }
            }
        }
        $data['custom_fields'] = $custom;
        $remove = $request->boolean('remove_avatar');
        if ($remove && !empty($current['avatar'])) {
            $old = public_path(ltrim($current['avatar'], '/'));
            if (is_file($old)) @unlink($old);
            if (!empty($current['avatar_thumb'])) {
                $oldt = public_path(ltrim($current['avatar_thumb'], '/'));
                if (is_file($oldt)) @unlink($oldt);
            }
            $data['avatar'] = null;
            $data['avatar_thumb'] = null;
        }
        if ($request->hasFile('avatar')) {
            if (!empty($current['avatar'])) {
                $old = public_path(ltrim($current['avatar'], '/'));
                if (is_file($old)) @unlink($old);
            }
            if (!empty($current['avatar_thumb'])) {
                $oldt = public_path(ltrim($current['avatar_thumb'], '/'));
                if (is_file($oldt)) @unlink($oldt);
            }
            $saved = $this->saveAvatar($request->file('avatar'));
            $data = array_merge($data, $saved);
        }
        $updated = $this->store->update($id, $data);
        if ($updated) {
            $this->syncPortalUserForClient($updated);
        }
        return redirect()->route('clientes.index')->with('success', 'Cliente actualizado correctamente.');
    }

    public function export(Request $request)
    {
        $request->merge([
            'sort' => $request->query('sort', 'empresa'),
            'dir' => $request->query('dir', 'asc'),
        ]);
        $clientes = $this->index($request)->getData()['clientes'] ?? [];
        $headers = ['ID','Empresa','NIT','Propietario','Categoría','Estado','Facturas','Proyectos'];
        $rows = [];
        foreach ($clientes as $c) {
            $rows[] = [
                $c['id'] ?? '',
                $c['empresa'] ?? '',
                $c['nit'] ?? '',
                $c['propietario'] ?? '',
                $c['categoria'] ?? '',
                $c['estado'] ?? '',
                $c['facturas_total'] ?? 0,
                $c['proyectos'] ?? 0,
            ];
        }
        $filename = 'clientes_'.date('Ymd_His').'.csv';
        return response()->streamDownload(function() use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($rows as $r) fputcsv($out, $r);
            fclose($out);
        }, $filename, ['Content-Type'=>'text/csv']);
    }

    public function apiIndex(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $clientes = collect($this->store->all())
            ->reject(fn ($c) => $this->isPlaceholderClient($c))
            ->map(function ($c) {
                return [
                    'id' => (string) ($c['id'] ?? ''),
                    'empresa' => trim((string) ($c['empresa'] ?? '')),
                    'moneda' => strtoupper((string) ($c['moneda'] ?? '')),
                ];
            })
            ->filter(fn($c) => $c['empresa'] !== '')
            ->when($q !== '', fn($col) => $col->filter(fn($i) => str_contains(Str::lower($i['empresa']), Str::lower($q))))
            ->sortBy('empresa', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();

        return response()->json(['data' => $clientes]);
    }

    public function apiQuickStore(Request $request)
    {
        $data = $request->validate([
            'empresa' => 'required|string|max:255',
            'contacto_nombre' => 'nullable|string|max:255',
            'contacto_email' => 'nullable|email|max:255',
            'contacto_telefono' => 'nullable|string|max:255',
            'nit' => 'nullable|string|max:255',
            'direccion' => 'nullable|string|max:500',
            'ciudad' => 'nullable|string|max:255',
            'pais' => 'nullable|string|max:255',
            'moneda' => 'nullable|in:' . implode(',', $this->allowedCurrencies()),
        ]);

        $settings = (new FileStore('settings.json'))->find('settings') ?: [];
        $baseCurrency = strtoupper((string) ($settings['base_currency'] ?? 'USD'));

        $payload = array_merge($data, [
            'estado' => 'Activo',
            'categoria' => 'Default',
            'proyectos' => 0,
            'moneda' => strtoupper((string) ($data['moneda'] ?? $baseCurrency)),
            'invoice_fields' => ['nit' => true, 'direccion' => true, 'telefono' => true, 'email' => true],
        ]);

        $created = $this->store->create($payload);
        $this->syncPortalUserForClient($created);

        return response()->json([
            'ok' => true,
            'cliente' => [
                'id' => (string) ($created['id'] ?? ''),
                'empresa' => (string) ($created['empresa'] ?? ''),
                'contacto_nombre' => (string) ($created['contacto_nombre'] ?? ''),
                'contacto_email' => (string) ($created['contacto_email'] ?? ''),
                'contacto_telefono' => (string) ($created['contacto_telefono'] ?? ''),
                'direccion' => (string) ($created['direccion'] ?? ''),
                'ciudad' => (string) ($created['ciudad'] ?? ''),
                'pais' => (string) ($created['pais'] ?? ''),
                'moneda' => strtoupper((string) ($created['moneda'] ?? $baseCurrency)),
            ],
        ]);
    }

    public function destroy(string $id)
    {
        User::where('role', 'client')->where('cliente_id', $id)->delete();
        $this->store->delete($id);
        return redirect()->route('clientes.index');
    }
}
