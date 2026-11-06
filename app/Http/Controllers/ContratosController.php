<?php

namespace App\Http\Controllers;

use App\Repositories\FileStore;
use Illuminate\Http\Request;
use App\Support\ContractDocuments;
use Illuminate\Support\Str;

class ContratosController extends Controller
{
    public const CURRENCIES = [
        'COP' => 'Pesos colombianos (COP)',
        'USD' => 'Dólares estadounidenses (USD)',
        'EUR' => 'Euros (EUR)',
        'MXN' => 'Pesos mexicanos (MXN)',
    ];

    protected $store;
    protected $clientes;
    protected $proyectos;

    public function __construct()
    {
        $this->store = new FileStore('contratos.json');
        $this->clientes = new FileStore('clientes.json');
        $this->proyectos = new FileStore('proyectos.json');
    }

    public function index(Request $request)
    {
        $q = trim((string)$request->query('q', ''));
        $all = collect($this->store->all());

        if ($q) {
            $all = $all->filter(function ($c) use ($q) {
                return str_contains(strtolower($c['titulo'] ?? ''), strtolower($q)) ||
                       str_contains(strtolower($c['cliente_nombre'] ?? ''), strtolower($q));
            });
        }

        $contratos = $all->sortByDesc('created_at')->values()->all();

        return view('contratos.index', compact('contratos', 'q'));
    }

    public function create()
    {
        $clientes = collect($this->clientes->all())->sortBy(fn($c) => $c['empresa'] ?? ($c['nombre'] ?? ''))->values()->all();
        $proyectos = collect($this->proyectos->all())->sortBy(fn($p) => $p['titulo'] ?? ($p['nombre'] ?? ''))->values()->all();
        
        $settings = (new FileStore('settings.json'))->find('settings') ?: [];
        $templates = \App\Support\ContractTemplates::all($settings);
        $baseCurrency = $this->baseCurrency();
        $currencies = self::CURRENCIES;
        $clientesData = collect($clientes)->mapWithKeys(fn ($c) => [$c['id'] => [
            'nombre' => $c['empresa'] ?? ($c['nombre'] ?? ($c['contacto_nombre'] ?? '')),
            'nit' => $c['nit'] ?? '',
            'representante' => $c['contacto_nombre'] ?? '',
            'ciudad' => $c['ciudad'] ?? '',
            'email' => $c['contacto_email'] ?? ($c['email'] ?? ''),
        ]])->all();

        return view('contratos.create', compact('clientes', 'proyectos', 'templates', 'baseCurrency', 'currencies', 'clientesData'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'titulo' => 'required|string|max:255',
            'cliente_id' => 'required|string',
            'proyecto_id' => 'nullable|string',
            'contenido' => 'required|string',
            'monto' => 'nullable|numeric',
            'moneda' => 'nullable|string|in:' . implode(',', array_keys(self::CURRENCIES)),
            'estado' => 'required|in:Borrador,Enviado,Firmado,Rechazado',
        ]);

        // Fetch names for easier display
        $client = $this->clientes->find($data['cliente_id']);
        $project = !empty($data['proyecto_id']) ? $this->proyectos->find($data['proyecto_id']) : null;

        $data['id'] = (string) Str::uuid();
        $data['cliente_nombre'] = $client['empresa'] ?? ($client['nombre'] ?? ($client['contacto_nombre'] ?? 'Cliente Desconocido'));
        $data['proyecto_nombre'] = $project['titulo'] ?? ($project['nombre'] ?? null);
        $data['created_at'] = now()->toIso8601String();
        $data['updated_at'] = now()->toIso8601String();
        $data['firmas'] = []; // Array of { 'rol': 'Cliente', 'fecha': '...', 'ip': '...' }

        $data['estado'] = 'Borrador';
        $created = $this->store->create($data);

        return $this->afterSave($created, $request->input('estado') === 'Enviado', 'Contrato guardado como borrador.');
    }

    public function show($id)
    {
        $contrato = $this->store->find($id);
        if (!$contrato) abort(404);
        return view('contratos.show', compact('contrato'));
    }

    public function edit($id)
    {
        $contrato = $this->store->find($id);
        if (!$contrato) abort(404);

        $clientes = collect($this->clientes->all())->sortBy(fn($c) => $c['empresa'] ?? ($c['nombre'] ?? ''))->values()->all();
        $proyectos = collect($this->proyectos->all())->sortBy(fn($p) => $p['titulo'] ?? ($p['nombre'] ?? ''))->values()->all();

        $baseCurrency = $this->baseCurrency();
        $currencies = self::CURRENCIES;

        return view('contratos.edit', compact('contrato', 'clientes', 'proyectos', 'baseCurrency', 'currencies'));
    }

    public function update(Request $request, $id)
    {
        $current = $this->store->find($id);
        abort_if(!$current, 404);
        if (($current['estado'] ?? '') === 'Firmado') {
            return redirect()->route('contratos.show', $id)->with('error', 'Este contrato ya fue firmado y no se puede modificar. Crea uno nuevo si necesitas cambios.');
        }

        $data = $request->validate([
            'titulo' => 'required|string|max:255',
            'cliente_id' => 'required|string',
            'proyecto_id' => 'nullable|string',
            'contenido' => 'required|string',
            'monto' => 'nullable|numeric',
            'moneda' => 'nullable|string|in:' . implode(',', array_keys(self::CURRENCIES)),
            'estado' => 'required|in:Borrador,Enviado,Firmado,Rechazado',
        ]);

        $client = $this->clientes->find($data['cliente_id']);
        $project = !empty($data['proyecto_id']) ? $this->proyectos->find($data['proyecto_id']) : null;

        $data['cliente_nombre'] = $client['empresa'] ?? ($client['nombre'] ?? ($client['contacto_nombre'] ?? 'Cliente Desconocido'));
        $data['proyecto_nombre'] = $project['titulo'] ?? ($project['nombre'] ?? null);
        $data['updated_at'] = now()->toIso8601String();

        $data['estado'] = ($current['estado'] ?? 'Borrador') === 'Borrador' ? 'Borrador' : $current['estado'];
        $updated = $this->store->update($id, $data);

        return $this->afterSave($updated, $request->input('estado') === 'Enviado', 'Contrato actualizado.');
    }

    public function destroy($id)
    {
        $this->store->delete($id);
        return redirect()->route('contratos.index')->with('success', 'Contrato eliminado.');
    }

    public function firmar(Request $request, $id)
    {
        $contrato = $this->store->find($id);
        if (!$contrato) abort(404);

        $rol = 'Empresa'; // El cliente firma desde su enlace privado.
        
        $firma = [
            'rol' => $rol,
            'fecha' => now()->toIso8601String(),
            'ip' => $request->ip(),
            'nombre' => (string) (auth()->user()->name ?? 'Administrador'),
            'firma' => $request->input('firma')
        ];

        if (!isset($contrato['firmas'])) {
            $contrato['firmas'] = [];
        }
        
        $contrato['firmas'][] = $firma;
        
        // If signed by client, update status
        if ($rol === 'Cliente') {
            $contrato['estado'] = 'Firmado';
        }

        $this->store->update($id, $contrato);

        return back()->with('success', 'Contrato firmado exitosamente.');
    }
    
    public function pdf(Request $request, $id)
    {
        $contrato = $this->store->find($id);
        abort_if(!$contrato, 404);
        $pdf = (new ContractDocuments())->pdf($contrato);

        return $request->boolean('ver') ? $pdf->stream(ContractDocuments::fileName($contrato)) : $pdf->download(ContractDocuments::fileName($contrato));
    }

    public function enviar($id)
    {
        $contrato = $this->store->find($id);
        abort_if(!$contrato, 404);

        return $this->afterSave($contrato, true, '');
    }

    /** Guarda y, si se pidió, publica y envía al cliente. */
    private function afterSave(array $contrato, bool $send, string $savedMessage)
    {
        $redirect = redirect()->route('contratos.show', $contrato['id']);
        if (!$send) {
            return $redirect->with('success', $savedMessage);
        }
        try {
            $email = (new ContractDocuments())->sendToClient($contrato);
        } catch (\RuntimeException $e) {
            return $redirect->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            return $redirect->with('error', 'El contrato quedó guardado, pero no se pudo enviar el correo. Revisa la configuración de correo e inténtalo de nuevo.');
        }

        return $redirect->with('success', "Contrato publicado y enviado a {$email}. Le llegó con el PDF adjunto y el enlace para firmar.");
    }

    // ----- Enlace privado para el cliente -----

    private function publicContract(string $id, string $token): array
    {
        $contrato = $this->store->find($id);
        abort_if(!$contrato || empty($contrato['public_token']) || !hash_equals((string) $contrato['public_token'], $token), 404);
        abort_if(($contrato['estado'] ?? 'Borrador') === 'Borrador', 404);

        return $contrato;
    }

    public function publicShow(string $id, string $token)
    {
        $contrato = $this->publicContract($id, $token);
        $settings = ContractDocuments::settings();
        $logoPath = $settings['invoice_logo'] ?? ($settings['logo_large'] ?? ($settings['logo'] ?? null));

        return response()->view('contratos.public', [
            'contrato' => $contrato,
            'settings' => $settings,
            'logo' => $logoPath ? app_public_asset_url($logoPath) : null,
            'numero' => ContractDocuments::number($contrato),
            'valor' => ContractDocuments::money($contrato),
            'firmaCliente' => ContractDocuments::signature($contrato, 'Cliente'),
            'token' => $token,
        ])->header('X-Robots-Tag', 'noindex, nofollow')->header('Referrer-Policy', 'no-referrer');
    }

    public function publicPdf(string $id, string $token)
    {
        $contrato = $this->publicContract($id, $token);

        return (new ContractDocuments())->pdf($contrato)->download(ContractDocuments::fileName($contrato));
    }

    public function publicSign(Request $request, string $id, string $token)
    {
        $contrato = $this->publicContract($id, $token);
        if (ContractDocuments::signature($contrato, 'Cliente')) {
            return redirect()->route('contratos.public.show', ['id' => $id, 'token' => $token]);
        }
        $data = $request->validate([
            'nombre' => 'required|string|min:5|max:120',
            'documento' => 'required|string|min:5|max:30',
            'acepto' => 'accepted',
        ], [
            'nombre.required' => 'Escribe tu nombre completo.',
            'documento.required' => 'Escribe tu número de documento.',
            'acepto.accepted' => 'Debes aceptar los términos para firmar.',
        ]);

        $firmas = $contrato['firmas'] ?? [];
        $firmas[] = [
            'rol' => 'Cliente',
            'nombre' => trim($data['nombre']),
            'documento' => trim($data['documento']),
            'fecha' => now()->toIso8601String(),
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 300),
            'metodo' => 'Enlace privado enviado por correo',
            'hash_contenido' => hash('sha256', (string) ($contrato['contenido'] ?? '')),
        ];
        $signed = $this->store->update($id, ['firmas' => $firmas, 'estado' => 'Firmado', 'firmado_at' => now()->toIso8601String()]);

        try {
            (new ContractDocuments())->notifySigned($signed);
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('contratos.public.show', ['id' => $id, 'token' => $token])->with('signed', true);
    }

    private function baseCurrency(): string
    {
        $settings = (new FileStore('settings.json'))->find('settings') ?: [];
        $base = strtoupper((string) ($settings['base_currency'] ?? 'COP'));

        return array_key_exists($base, self::CURRENCIES) ? $base : 'COP';
    }
}
