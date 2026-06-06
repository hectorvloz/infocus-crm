<?php

namespace App\Http\Controllers;

use App\Repositories\FileStore;
use Illuminate\Http\Request;

class CotizacionesController extends Controller
{
    protected FileStore $store;
    protected FileStore $leads;

    public function __construct()
    {
        $this->store = new FileStore('cotizaciones.json');
        $this->leads = new FileStore('leads.json');
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q',''));
        $all = collect($this->store->all());
        
        $cotizaciones = $all
            ->when($q !== '', fn($c)=>$c->filter(fn($f)=> str_contains(strtolower($f['cliente'] ?? ''), strtolower($q)) || str_contains(strtolower($f['numero'] ?? ''), strtolower($q))))
            ->sortByDesc('fecha')
            ->values()
            ->all();
            
        return view('ventas.cotizaciones.index', compact('cotizaciones', 'q'));
    }

    public function create()
    {
        $all = collect($this->store->all());
        $max = 0;
        foreach ($all as $f) {
            if (!empty($f['numero']) && preg_match('/(\d+)/', $f['numero'], $m)) {
                $n = intval($m[1]);
                if ($n > $max) $max = $n;
            }
        }
        $next = 'COT-'.str_pad((string)($max+1), 4, '0', STR_PAD_LEFT);
        return view('ventas.cotizaciones.create', ['nextNumber'=>$next]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'numero' => 'required|string',
            'cliente' => 'required|string', // Manual entry
            'fecha' => 'required|date',
            'vencimiento' => 'nullable|date',
            'moneda' => 'nullable|string',
            'items' => 'required|array',
            'items.*.descripcion' => 'required|string',
            'items.*.cantidad' => 'required|numeric',
            'items.*.precio' => 'required|numeric',
            'estado' => 'required|string',
        ]);

        $subtotal = collect($data['items'])->sum(fn($i) => $i['cantidad'] * $i['precio']);
        $data['subtotal'] = round($subtotal, 2);
        $data['impuestos'] = round($subtotal * 0.16, 2); // Default 16% like invoices
        $data['total'] = round($data['subtotal'] + $data['impuestos'], 2);
        
        // Save Quote
        $this->store->create($data);

        // Generate Lead if "Publicada" (or equivalent intent)
        if ($data['estado'] === 'Publicada' || $data['estado'] === 'Enviada') {
            $this->createLeadFromQuote($data);
        }

        return redirect()->route('cotizaciones.index')->with('success', 'Cotización creada correctamente.');
    }

    public function show($id)
    {
        $cotizacion = $this->store->find($id);
        abort_if(!$cotizacion, 404);
        return view('ventas.cotizaciones.show', compact('cotizacion'));
    }

    public function edit($id)
    {
        $cotizacion = $this->store->find($id);
        abort_if(!$cotizacion, 404);
        return view('ventas.cotizaciones.edit', compact('cotizacion'));
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'numero' => 'required|string',
            'cliente' => 'required|string',
            'fecha' => 'required|date',
            'vencimiento' => 'nullable|date',
            'moneda' => 'nullable|string',
            'items' => 'required|array',
            'items.*.descripcion' => 'required|string',
            'items.*.cantidad' => 'required|numeric',
            'items.*.precio' => 'required|numeric',
            'estado' => 'required|string',
        ]);

        $subtotal = collect($data['items'])->sum(fn($i) => $i['cantidad'] * $i['precio']);
        $data['subtotal'] = round($subtotal, 2);
        $data['impuestos'] = round($subtotal * 0.16, 2);
        $data['total'] = round($data['subtotal'] + $data['impuestos'], 2);

        $this->store->update($id, $data);

        // Check lead generation again on update
        if ($data['estado'] === 'Publicada' || $data['estado'] === 'Enviada') {
            $this->createLeadFromQuote($data);
        }

        return redirect()->route('cotizaciones.index');
    }

    public function destroy($id)
    {
        $this->store->delete($id);
        return redirect()->route('cotizaciones.index');
    }

    public function imprimir($id)
    {
        $cotizacion = $this->store->find($id);
        abort_if(!$cotizacion, 404);
        return view('ventas.cotizaciones.print', compact('cotizacion'));
    }

    protected function createLeadFromQuote($data)
    {
        // Check if lead already exists by name
        $leads = collect($this->leads->all());
        $exists = $leads->first(fn($l) => strtolower($l['nombre'] ?? '') === strtolower($data['cliente']));

        if (!$exists) {
            $this->leads->create([
                'nombre' => $data['cliente'],
                'etapa' => 'Posible cliente',
                'valor' => $data['total'],
                'presupuesto_estimado' => $data['total'],
                'origen' => 'Cotización ' . $data['numero'],
                'notas' => 'Lead generado automáticamente desde cotización ' . $data['numero'],
                'email' => '', // Manual entry doesn't have email yet
                'telefono' => ''
            ]);
        }
    }
}
