<?php

namespace App\Http\Controllers;

use App\Repositories\FileStore;
use Illuminate\Http\Request;

class ProductosController extends Controller
{
    protected FileStore $store;
    protected FileStore $facturasStore;

    public function __construct()
    {
        $this->store = new FileStore('productos.json');
        $this->facturasStore = new FileStore('facturas.json');
    }

    protected function normalizeText(?string $value): string
    {
        $text = mb_strtolower(trim((string) $value), 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        return (string) $text;
    }

    protected function getBase(): string
    {
        $settings = (new FileStore('settings.json'))->find('settings') ?: [];
        return strtoupper($settings['base_currency'] ?? 'USD');
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q',''));
        $all = collect($this->store->all());
        $facturas = collect($this->facturasStore->all());
        
        $productosCollection = $all
            ->when($q !== '', fn($c)=>$c->filter(fn($p)=> str_contains(strtolower($p['nombre'] ?? ''), strtolower($q)) || str_contains(strtolower($p['descripcion'] ?? ''), strtolower($q))))
            ->sortBy('nombre')
            ->values();

        $productos = $productosCollection->map(function ($p) use ($facturas) {
            $productId = (string) ($p['id'] ?? '');
            $productName = $this->normalizeText($p['nombre'] ?? '');
            $hasReminder = !empty($p['service_expiry_reminder_enabled']);
            $activeReminderCount = 0;
            $soldCount = 0.0;

            foreach ($facturas as $f) {
                $items = collect((array) ($f['items'] ?? []));
                $hasProductInInvoice = $items->contains(function ($it) use ($productId, $productName) {
                    $itemProductId = trim((string) ($it['producto_id'] ?? ''));
                    if ($productId !== '' && $itemProductId !== '') {
                        return $itemProductId === $productId;
                    }
                    $desc = $this->normalizeText($it['descripcion'] ?? '');
                    return $desc !== '' && $desc === $productName;
                });

                if (!$hasProductInInvoice) {
                    continue;
                }

                $rec = (array) ($f['recurrencia'] ?? []);
                $isRecurringActive = !empty($rec['enabled']);
                if ($hasReminder && $isRecurringActive) {
                    $activeReminderCount++;
                }

                if (!$hasReminder && (string) ($f['estado'] ?? '') === 'Pagada') {
                    $soldQty = $items
                        ->filter(function ($it) use ($productId, $productName) {
                            $itemProductId = trim((string) ($it['producto_id'] ?? ''));
                            if ($productId !== '' && $itemProductId !== '') {
                                return $itemProductId === $productId;
                            }
                            return $this->normalizeText($it['descripcion'] ?? '') === $productName;
                        })
                        ->sum(fn($it) => (float) ($it['cantidad'] ?? 0));
                    $soldCount += (float) $soldQty;
                }
            }

            $p['_active_reminder_count'] = $activeReminderCount;
            $p['_sold_count'] = $soldCount;
            return $p;
        })->all();

        $totalItems = count($productos);
        $base = $this->getBase();
            
        return view('ventas.productos.index', compact('productos', 'q', 'totalItems', 'base'));
    }

    public function create()
    {
        $base = $this->getBase();
        return view('ventas.productos.create', compact('base'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'    => 'required|string',
            'descripcion' => 'nullable|string',
            'precio'    => 'required|numeric|min:0',
            'sku'       => 'nullable|string',
            'tipo'      => 'required|in:Producto,Servicio',
            'stock'     => 'nullable|numeric',
            'service_expiry_reminder_enabled' => 'nullable|boolean',
            'service_expiry_reminder_days_before' => 'nullable|integer|min:1|max:90',
            'precios'   => 'nullable|array',
            'precios.*' => 'nullable|numeric|min:0',
        ]);

        if (!empty($data['precios'])) {
            $data['precios'] = array_filter($data['precios'], fn($v) => $v !== null && $v !== '');
        } else {
            unset($data['precios']);
        }

        $data['service_expiry_reminder_enabled'] = $request->boolean('service_expiry_reminder_enabled');
        $data['service_expiry_reminder_days_before'] = $data['service_expiry_reminder_enabled']
            ? (int) ($data['service_expiry_reminder_days_before'] ?? 7)
            : null;

        $this->store->create($data);
        return redirect()->route('productos.index')->with('success', 'Producto creado correctamente.');
    }

    public function edit($id)
    {
        $producto = $this->store->find($id);
        abort_if(!$producto, 404);
        $base = $this->getBase();
        return view('ventas.productos.edit', compact('producto', 'base'));
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'nombre'    => 'required|string',
            'descripcion' => 'nullable|string',
            'precio'    => 'required|numeric|min:0',
            'sku'       => 'nullable|string',
            'tipo'      => 'required|in:Producto,Servicio',
            'stock'     => 'nullable|numeric',
            'service_expiry_reminder_enabled' => 'nullable|boolean',
            'service_expiry_reminder_days_before' => 'nullable|integer|min:1|max:90',
            'precios'   => 'nullable|array',
            'precios.*' => 'nullable|numeric|min:0',
        ]);

        if (!empty($data['precios'])) {
            $data['precios'] = array_filter($data['precios'], fn($v) => $v !== null && $v !== '');
        } else {
            unset($data['precios']);
        }

        $data['service_expiry_reminder_enabled'] = $request->boolean('service_expiry_reminder_enabled');
        $data['service_expiry_reminder_days_before'] = $data['service_expiry_reminder_enabled']
            ? (int) ($data['service_expiry_reminder_days_before'] ?? 7)
            : null;

        $this->store->update($id, $data);
        return redirect()->route('productos.index');
    }

    public function destroy($id)
    {
        $this->store->delete($id);
        return redirect()->route('productos.index');
    }

    // API endpoint for fetching products in frontend
    public function apiIndex(Request $request)
    {
        $q = trim((string) $request->query('q',''));
        $all = collect($this->store->all());
        
        $productos = $all
            ->when($q !== '', fn($c)=>$c->filter(fn($p)=> str_contains(strtolower($p['nombre'] ?? ''), strtolower($q))))
            ->values()
            ->all();

        return response()->json($productos);
    }
}
