<?php

namespace App\Http\Controllers;

use App\Repositories\FileStore;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProveedoresController extends Controller
{
    protected $store;
    protected $config;

    public function __construct()
    {
        $this->store = new FileStore('proveedores.json');
        $this->config = new FileStore('gastos_config.json');
    }

    public function index()
    {
        // Usually handled in GastosController@index for the tabs, but kept here if needed separately
        return redirect()->route('gastos.index', ['tab' => 'proveedores']);
    }

    public function create()
    {
        $categoryOptions = $this->buildCategoryOptions();
        return view('proveedores.create', compact('categoryOptions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'rfc' => 'nullable|string|max:20', // Tax ID
            'contacto' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'telefono' => 'nullable|string',
            'direccion' => 'nullable|string',
            'categoria' => 'nullable|string',
        ]);

        $data['id'] = (string) Str::uuid();
        $data['created_at'] = now()->toIso8601String();
        
        $this->store->create($data);

        return redirect()->route('gastos.index', ['tab' => 'proveedores'])->with('success', 'Proveedor agregado.');
    }

    public function edit($id)
    {
        $proveedor = $this->store->find($id);
        if (!$proveedor) abort(404);
        $categoryOptions = $this->buildCategoryOptions();
        return view('proveedores.edit', compact('proveedor', 'categoryOptions'));
    }

    public function update(Request $request, $id)
    {
        $proveedor = $this->store->find($id);
        if (!$proveedor) abort(404);

        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'rfc' => 'nullable|string|max:20',
            'contacto' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'telefono' => 'nullable|string',
            'direccion' => 'nullable|string',
            'categoria' => 'nullable|string',
        ]);

        $data['updated_at'] = now()->toIso8601String();
        $this->store->update($id, $data);

        return redirect()->route('gastos.index', ['tab' => 'proveedores'])->with('success', 'Proveedor actualizado.');
    }

    public function destroy($id)
    {
        // Optional: Check if used in expenses before delete? 
        // For now, simple delete.
        $this->store->delete($id);
        return redirect()->route('gastos.index', ['tab' => 'proveedores'])->with('success', 'Proveedor eliminado.');
    }

    public function updateCategories(Request $request)
    {
        $data = $request->validate([
            'categories' => 'nullable|array',
            'categories.*' => 'nullable|string|max:80',
        ]);

        $items = collect($data['categories'] ?? [])
            ->map(fn($v) => trim((string) $v))
            ->filter(fn($v) => $v !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();

        $recordId = 'proveedor_categorias';
        $existing = $this->config->find($recordId);
        if ($existing) {
            $this->config->update($recordId, ['items' => $items]);
        } else {
            $this->config->create(['id' => $recordId, 'items' => $items]);
        }

        return redirect()->route('gastos.index', ['tab' => 'proveedores'])
            ->with('success', 'Categorias de proveedor actualizadas.');
    }

    protected function buildCategoryOptions(): array
    {
        $fromProviderConfig = (array) (($this->config->find('proveedor_categorias') ?? [])['items'] ?? []);
        $fromExpensesConfig = (array) (($this->config->find('categorias') ?? [])['items'] ?? []);
        $fromProviders = collect($this->store->all() ?: [])
            ->pluck('categoria')
            ->filter(fn($v) => trim((string) $v) !== '')
            ->map(fn($v) => trim((string) $v))
            ->all();

        $fallback = ['Tecnologia', 'Servicios Generales', 'Suministros', 'Consultoria'];

        $merged = array_values(array_unique(array_merge($fromProviderConfig, $fromExpensesConfig, $fromProviders, $fallback)));
        sort($merged);

        return $merged;
    }
}
