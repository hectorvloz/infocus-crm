<?php

namespace App\Http\Controllers;

use App\Repositories\FileStore;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ContratosController extends Controller
{
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
        
        $templates = [
            'servicios' => [
                'titulo' => 'Contrato de Prestación de Servicios',
                'contenido' => '<h1>Contrato de Prestación de Servicios</h1><p>Entre <strong>[Nombre Cliente]</strong> y <strong>[Tu Empresa]</strong>...</p><h3>1. Objeto</h3><p>El objeto del presente contrato es...</p><h3>2. Honorarios</h3><p>El monto total es de [Monto]...</p>'
            ],
            'nda' => [
                'titulo' => 'Acuerdo de Confidencialidad (NDA)',
                'contenido' => '<h1>Acuerdo de Confidencialidad</h1><p>Este acuerdo se celebra con el fin de proteger la información confidencial...</p>'
            ]
        ];

        return view('contratos.create', compact('clientes', 'proyectos', 'templates'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'titulo' => 'required|string|max:255',
            'cliente_id' => 'required|string',
            'proyecto_id' => 'nullable|string',
            'contenido' => 'required|string',
            'monto' => 'nullable|numeric',
            'moneda' => 'nullable|string|in:MXN,USD,EUR',
            'estado' => 'required|in:Borrador,Enviado,Firmado,Rechazado',
        ]);

        // Fetch names for easier display
        $client = $this->clientes->find($data['cliente_id']);
        $project = $data['proyecto_id'] ? $this->proyectos->find($data['proyecto_id']) : null;

        $data['id'] = (string) Str::uuid();
        $data['cliente_nombre'] = $client['empresa'] ?? ($client['nombre'] ?? ($client['contacto_nombre'] ?? 'Cliente Desconocido'));
        $data['proyecto_nombre'] = $project['titulo'] ?? ($project['nombre'] ?? null);
        $data['created_at'] = now()->toIso8601String();
        $data['updated_at'] = now()->toIso8601String();
        $data['firmas'] = []; // Array of { 'rol': 'Cliente', 'fecha': '...', 'ip': '...' }

        $this->store->create($data);

        return redirect()->route('contratos.index')->with('success', 'Contrato creado exitosamente.');
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

        return view('contratos.edit', compact('contrato', 'clientes', 'proyectos'));
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'titulo' => 'required|string|max:255',
            'cliente_id' => 'required|string',
            'proyecto_id' => 'nullable|string',
            'contenido' => 'required|string',
            'monto' => 'nullable|numeric',
            'moneda' => 'nullable|string|in:MXN,USD,EUR',
            'estado' => 'required|in:Borrador,Enviado,Firmado,Rechazado',
        ]);

        $client = $this->clientes->find($data['cliente_id']);
        $project = $data['proyecto_id'] ? $this->proyectos->find($data['proyecto_id']) : null;

        $data['cliente_nombre'] = $client['empresa'] ?? ($client['nombre'] ?? ($client['contacto_nombre'] ?? 'Cliente Desconocido'));
        $data['proyecto_nombre'] = $project['titulo'] ?? ($project['nombre'] ?? null);
        $data['updated_at'] = now()->toIso8601String();

        $this->store->update($id, $data);

        return redirect()->route('contratos.index')->with('success', 'Contrato actualizado.');
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

        $rol = $request->input('rol', 'Cliente'); // 'Cliente' or 'Empresa'
        
        $firma = [
            'rol' => $rol,
            'fecha' => now()->toIso8601String(),
            'ip' => $request->ip(),
            'nombre' => $rol === 'Empresa' ? 'Administrador' : ($contrato['cliente_nombre'] ?? 'Cliente'),
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
    
    public function pdf($id)
    {
        $contrato = $this->store->find($id);
        if (!$contrato) abort(404);
        return view('contratos.print', compact('contrato'));
    }
}
