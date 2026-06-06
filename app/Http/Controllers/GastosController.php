<?php

namespace App\Http\Controllers;

use App\Repositories\FileStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class GastosController extends Controller
{
    protected $store;
    protected $proveedores;
    protected $config;

    public function __construct()
    {
        $this->store = new FileStore("gastos.json");
        $this->proveedores = new FileStore("proveedores.json");
        $this->config = new FileStore("gastos_config.json");
    }

    public function index(Request $request)
    {
        $allGastos = collect($this->store->all());
        $gastos = $allGastos->sortByDesc("fecha")->values();
        $proveedores = collect($this->proveedores->all())->sortBy("nombre")->values();

        $now = Carbon::now();
        $proveedorMes = (string) $request->query("proveedor_mes", "all");
        if ($proveedorMes !== "all" && !preg_match("/^\d{4}-\d{2}$/", $proveedorMes)) {
            $proveedorMes = "all";
        }

        // Sugerencia: Mover esto a un helper o método privado
        $normalizeProviderName = fn($value) => $this->normalizeName($value);

        // Enriquecer gastos con nombre del proveedor
        $provMap = $proveedores->keyBy("id");
        $providerNameToId = $proveedores
            ->mapWithKeys(fn($prov) => [
                $normalizeProviderName($prov["nombre"] ?? "") => (string) ($prov["id"] ?? ""),
            ])
            ->filter(fn($id, $normalized) => $normalized !== "" && $id !== "");

        $gastos = $gastos->map(function($g) use ($provMap, $providerNameToId, $normalizeProviderName) {
            $providerId = (string) ($g["proveedor_id"] ?? "");
            $providerName = null;

            if ($providerId !== "" && isset($provMap[$providerId])) {
                $providerName = $provMap[$providerId]["nombre"] ?? null;
            } else {
                $legacyName = (string) ($g["proveedor_nombre"] ?? ($g["proveedor"] ?? ""));
                $normalizedLegacyName = $normalizeProviderName($legacyName);
                if ($normalizedLegacyName !== "" && isset($providerNameToId[$normalizedLegacyName])) {
                    $matchedId = (string) $providerNameToId[$normalizedLegacyName];
                    $g["proveedor_id"] = $matchedId;
                    $providerName = $provMap[$matchedId]["nombre"] ?? $legacyName;
                } elseif ($legacyName !== "") {
                    $providerName = $legacyName;
                }
            }

            $g["proveedor_nombre"] = $providerName;
            return $g;
        });

        // Resumen y detalle por proveedor (para tab Proveedores)
        $mesesConGastos = $allGastos
            ->pluck("fecha")
            ->filter()
            ->map(fn($f) => substr((string) $f, 0, 7))
            ->filter(fn($m) => preg_match("/^\d{4}-\d{2}$/", $m))
            ->unique()
            ->values();
        if (!$mesesConGastos->contains($now->format("Y-m"))) {
            $mesesConGastos->push($now->format("Y-m"));
        }
        $mesesConGastos = $mesesConGastos->sortDesc()->values();

        $mesesProveedor = $mesesConGastos->map(function ($m) {
            try {
                $dt = Carbon::createFromFormat("Y-m", $m);
                return [
                    "value" => $m,
                    "label" => ucfirst($dt->locale("es")->translatedFormat("F Y")),
                ];
            } catch (\Throwable $e) {
                return ["value" => $m, "label" => $m];
            }
        })->values();

        $gastosProveedorBase = $allGastos->map(function ($g) use ($provMap, $providerNameToId, $normalizeProviderName) {
            $providerId = (string) ($g["proveedor_id"] ?? "");
            $providerName = null;
            if ($providerId !== "" && isset($provMap[$providerId])) {
                $providerName = $provMap[$providerId]["nombre"] ?? null;
            } else {
                $legacyName = (string) ($g["proveedor_nombre"] ?? ($g["proveedor"] ?? ""));
                $normalizedLegacyName = $normalizeProviderName($legacyName);
                if ($normalizedLegacyName !== "" && isset($providerNameToId[$normalizedLegacyName])) {
                    $matchedId = (string) $providerNameToId[$normalizedLegacyName];
                    $g["proveedor_id"] = $matchedId;
                    $providerName = $provMap[$matchedId]["nombre"] ?? $legacyName;
                } elseif ($legacyName !== "") {
                    $providerName = $legacyName;
                }
            }
            $g["proveedor_nombre"] = $providerName;
            $g["cliente_nombre"] = (string) ($g["cliente"] ?? "");
            $g["proyecto_nombre"] = (string) ($g["proyecto"] ?? ($g["proyecto_nombre"] ?? ""));
            $g["provider_norm"] = $normalizeProviderName($g["proveedor_nombre"] ?? "");
            $g["matched_provider_id"] = (string) ($g["proveedor_id"] ?? "");
            if ($g["matched_provider_id"] === "" && $g["provider_norm"] !== "" && isset($providerNameToId[$g["provider_norm"]])) {
                $g["matched_provider_id"] = (string) $providerNameToId[$g["provider_norm"]];
            }
            return $g;
        });

        $supplierStats = $proveedores->map(function ($prov) use ($gastosProveedorBase, $proveedorMes) {
            $items = $gastosProveedorBase->filter(function ($g) use ($prov, $proveedorMes) {
                $providerId = (string) ($prov["id"] ?? "");
                if ((string) ($g["matched_provider_id"] ?? "") !== $providerId) {
                    return false;
                }
                if ($proveedorMes === "all") {
                    return true;
                }
                return substr((string) ($g["fecha"] ?? ""), 0, 7) === $proveedorMes;
            })->sortByDesc("fecha")->values();

            $totalesPorMoneda = $items
                ->groupBy(fn($g) => strtoupper((string) ($g["moneda"] ?? "COP")))
                ->map(fn($rows) => (float) $rows->sum(fn($x) => (float) ($x["monto"] ?? 0)))
                ->toArray();

            return [
                "id" => $prov["id"] ?? "",
                "nombre" => $prov["nombre"] ?? "Proveedor",
                "contacto" => "-",
                "rfc" => "-",
                "email" => "-",
                "items_count" => $items->count(),
                "total" => (float) $items->sum(fn($x) => (float) ($x["monto"] ?? 0)),
                "totales_por_moneda" => $totalesPorMoneda,
                "expenses" => $items->map(function ($g) {
                    return [
                        "id" => $g["id"] ?? "",
                        "fecha" => $g["fecha"] ?? null,
                        "concepto" => $g["concepto"] ?? "-",
                        "cliente" => trim((string) ($g["cliente_nombre"] ?? "")) ?: "Sin cliente",
                        "proyecto" => trim((string) ($g["proyecto_nombre"] ?? "")) ?: "Sin proyecto",
                        "categoria" => $g["categoria"] ?? null,
                        "monto" => (float) ($g["monto"] ?? 0),
                        "moneda" => strtoupper((string) ($g["moneda"] ?? "COP")),
                        "notas" => $g["notas"] ?? null,
                    ];
                })->values()->all(),
            ];
        })->sortByDesc("total")->values();

        $unassignedItems = $gastosProveedorBase
            ->filter(function ($g) use ($proveedorMes) {
                if ($proveedorMes !== "all" && substr((string) ($g["fecha"] ?? ""), 0, 7) !== $proveedorMes) {
                    return false;
                }
                return trim((string) ($g["matched_provider_id"] ?? "")) === "";
            })
            ->sortByDesc("fecha")
            ->values();

        if ($unassignedItems->isNotEmpty()) {
            $supplierStats->prepend([
                "id" => "__unassigned__",
                "nombre" => "Sin proveedor asignado",
                "contacto" => "-",
                "rfc" => "-",
                "email" => "-",
                "items_count" => $unassignedItems->count(),
                "total" => (float) $unassignedItems->sum(fn($x) => (float) ($x["monto"] ?? 0)),
                "totales_por_moneda" => $unassignedItems
                    ->groupBy(fn($g) => strtoupper((string) ($g["moneda"] ?? "COP")))
                    ->map(fn($rows) => (float) $rows->sum(fn($x) => (float) ($x["monto"] ?? 0)))
                    ->toArray(),
                "is_unassigned" => true,
                "expenses" => $unassignedItems->map(function ($g) {
                    return [
                        "id" => $g["id"] ?? "",
                        "fecha" => $g["fecha"] ?? null,
                        "concepto" => $g["concepto"] ?? "-",
                        "cliente" => trim((string) ($g["cliente_nombre"] ?? "")) ?: "Sin cliente",
                        "proyecto" => trim((string) ($g["proyecto_nombre"] ?? "")) ?: "Sin proyecto",
                        "categoria" => $g["categoria"] ?? null,
                        "monto" => (float) ($g["monto"] ?? 0),
                        "moneda" => strtoupper((string) ($g["moneda"] ?? "COP")),
                        "notas" => $g["notas"] ?? null,
                    ];
                })->all(),
            ]);
        }

        $providerCategoryOptions = collect(($this->config->find("proveedor_categorias")["items"] ?? []))
            ->merge($proveedores->pluck("categoria"))
            ->filter(fn($v) => trim((string) $v) !== "")
            ->map(fn($v) => trim((string) $v))
            ->unique()
            ->sort()
            ->values()
            ->all();

        // --- Dashboard Logic ---
        $currentMonth = $now->format("Y-m");
        $lastMonth = $now->copy()->subMonth()->format("Y-m");

        $currentMonthGastos = $allGastos->filter(fn($g) => substr($g["fecha"], 0, 7) === $currentMonth);
        $lastMonthGastos = $allGastos->filter(fn($g) => substr($g["fecha"], 0, 7) === $lastMonth);

        $totalCurrent = $currentMonthGastos->sum("monto");
        $totalLast = $lastMonthGastos->sum("monto");
        
        $diffPercent = 0;
        if ($totalLast > 0) {
            $diffPercent = (($totalCurrent - $totalLast) / $totalLast) * 100;
        } elseif ($totalCurrent > 0) {
            $diffPercent = 100;
        }

        // Top Category
        $topCategory = $currentMonthGastos->groupBy("categoria")
            ->map(fn($group) => $group->sum("monto"))
            ->sortDesc()
            ->keys()
            ->first();
        
        $topCategoryAmount = $currentMonthGastos->where("categoria", $topCategory)->sum("monto");

        // Budgets (Presupuestos)
        $budgets = $this->config->find("presupuestos") ?? [];
        $categoryProgress = [];
        
        // Calculate progress for each budget
        foreach ($budgets as $cat => $limit) {
            $spent = $currentMonthGastos->where("categoria", $cat)->sum("monto");
            $percent = $limit > 0 ? ($spent / $limit) * 100 : 0;
            $categoryProgress[$cat] = [
                "limit" => $limit,
                "spent" => $spent,
                "percent" => min($percent, 100),
                "over_budget" => $spent > $limit
            ];
        }

        $stats = [
            "total_mes" => $totalCurrent,
            "diff_percent" => round($diffPercent, 1),
            "top_categoria" => $topCategory ?? "N/A",
            "top_categoria_monto" => $topCategoryAmount,
            "budgets" => $categoryProgress
        ];

        return view("gastos.index", compact(
            "gastos",
            "proveedores",
            "stats",
            "budgets",
            "supplierStats",
            "proveedorMes",
            "mesesProveedor",
            "providerCategoryOptions"
        ));
    }

    public function create()
    {
        $proveedores = collect($this->proveedores->all())->sortBy("nombre")->values();
        $clientes = collect((new FileStore("clientes.json"))->all())->sortBy("empresa")->values();
        $catRecord = $this->config->find("categorias") ?? [];
        $categories = $catRecord["items"] ?? [];
        return view("gastos.create", compact("proveedores", "clientes", "categories"));
    }

    public function store(Request $request)
    {
        $allowedCurrencies = ["USD","EUR","MXN","COP","ARS","CLP","PEN","GBP","CAD","JPY","AUD","CNY","CHF","HKD","NZD","SEK","KRW","SGD","INR","BRL","RUB","ZAR","TRY"];
        $data = $request->validate([
            "concepto" => "required|string|max:255",
            "fecha" => "required|date",
            "monto" => "required|numeric",
            "moneda" => "required|in:" . implode(",", $allowedCurrencies),
            "cliente_id" => "nullable|string",
            "proveedor_id" => "nullable|string",
            "comprobante" => "nullable|file|mimes:jpg,jpeg,png,gif,pdf|max:10240",
            "comprobante_camera" => "nullable|file|image|max:10240",
            "categoria" => "nullable|string",
            "notas" => "nullable|string",
            "es_recurrente" => "nullable|boolean",
            "frecuencia" => "nullable|in:Mensual,Anual",
        ]);

        $file = $request->file("comprobante_camera") ?: $request->file("comprobante");
        if ($file) {
            $path = $file->store("comprobantes", "public");
            $data["comprobante_path"] = $path;
        }

        $data["id"] = (string) Str::uuid();
        $data["created_at"] = now()->toIso8601String();
        // Checkbox returns "1" or true, ensure boolean or null
        $data["es_recurrente"] = $request->boolean("es_recurrente");

        $this->upsertCategoria($data["categoria"] ?? null);
        
        $this->store->create($data);

        return redirect()->route("gastos.index")->with("success", "Gasto registrado correctamente.");
    }

    public function edit($id)
    {
        $gasto = $this->store->find($id);
        if (!$gasto) abort(404);
        
        $proveedores = collect($this->proveedores->all())->sortBy("nombre")->values();
        $clientes = collect((new FileStore("clientes.json"))->all())->sortBy("empresa")->values();
        $catRecord = $this->config->find("categorias") ?? [];
        $categories = $catRecord["items"] ?? [];
        return view("gastos.edit", compact("gasto", "proveedores", "clientes", "categories"));
    }

    public function update(Request $request, $id)
    {
        $gasto = $this->store->find($id);
        if (!$gasto) abort(404);

        $allowedCurrencies = ["USD","EUR","MXN","COP","ARS","CLP","PEN","GBP","CAD","JPY","AUD","CNY","CHF","HKD","NZD","SEK","KRW","SGD","INR","BRL","RUB","ZAR","TRY"];
        $data = $request->validate([
            "concepto" => "required|string|max:255",
            "fecha" => "required|date",
            "monto" => "required|numeric",
            "moneda" => "required|in:" . implode(",", $allowedCurrencies),
            "cliente_id" => "nullable|string",
            "proveedor_id" => "nullable|string",
            "comprobante" => "nullable|file|mimes:jpg,jpeg,png,gif,pdf|max:10240",
            "comprobante_camera" => "nullable|file|image|max:10240",
            "categoria" => "nullable|string",
            "notas" => "nullable|string",
            "es_recurrente" => "nullable|boolean",
            "frecuencia" => "nullable|in:Mensual,Anual",
        ]);

        $file = $request->file("comprobante_camera") ?: $request->file("comprobante");
        if ($file) {
            if (!empty($gasto["comprobante_path"])) {
                Storage::disk("public")->delete($gasto["comprobante_path"]);
            }
            $path = $file->store("comprobantes", "public");
            $data["comprobante_path"] = $path;
        }

        $data["updated_at"] = now()->toIso8601String();
        $data["es_recurrente"] = $request->boolean("es_recurrente");

        $this->upsertCategoria($data["categoria"] ?? null);
        
        $this->store->update($id, $data);

        return redirect()->route("gastos.index")->with("success", "Gasto actualizado.");
    }

    public function destroy($id)
    {
        $gasto = $this->store->find($id);
        if ($gasto && !empty($gasto["comprobante_path"])) {
            Storage::disk("public")->delete($gasto["comprobante_path"]);
        }
        $this->store->delete($id);
        return redirect()->route("gastos.index")->with("success", "Gasto eliminado.");
    }

    public function export()
    {
        $gastos = collect($this->store->all())->sortByDesc("fecha");
        $proveedores = collect($this->proveedores->all())->keyBy("id");

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=gastos_" . date("Y-m-d") . ".csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $columns = ["ID", "Fecha", "Concepto", "Proveedor", "Categoría", "Monto", "Moneda", "Recurrente", "Notas"];

        $callback = function() use ($gastos, $columns, $proveedores) {
            $file = fopen("php://output", "w");
            fputcsv($file, $columns);

            foreach ($gastos as $g) {
                $provName = isset($g["proveedor_id"]) ? ($proveedores[$g["proveedor_id"]]["nombre"] ?? "") : "";
                
                fputcsv($file, [
                    $g["id"],
                    $g["fecha"],
                    $g["concepto"],
                    $provName,
                    $g["categoria"] ?? "",
                    $g["monto"],
                    $g["moneda"],
                    ($g["es_recurrente"] ?? false) ? "Sí" : "No",
                    $g["notas"] ?? ""
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function normalizeName($value): string
    {
        $value = (string) $value;
        // Normalizar: trim, lowercase, reemplazar múltiples espacios por uno solo, eliminar tildes
        $value = mb_strtolower(trim($value), "UTF-8");
        $value = preg_replace("/\s+/", " ", $value);
        // Remover tildes
        $unwanted = ["á" => "a", "é" => "e", "í" => "i", "ó" => "o", "ú" => "u", "ü" => "u", "ñ" => "n"];
        $value = strtr($value, $unwanted);
        return $value;
    }

    protected function upsertCategoria(?string $categoria): void
    {
        $categoria = trim((string) $categoria);
        if ($categoria === "") {
            return;
        }

        $catRecord = $this->config->find("categorias");
        $items = $catRecord["items"] ?? [];

        if (!in_array($categoria, $items, true)) {
            $items[] = $categoria;
            sort($items);
        }

        if ($catRecord) {
            $this->config->update("categorias", ["items" => $items]);
        } else {
            $this->config->create(["id" => "categorias", "items" => $items]);
        }
    }

    public function updateBudgets(Request $request)
    {
        $data = $request->validate([
            "budgets" => "required|array",
            "budgets.*.categoria" => "required|string",
            "budgets.*.limite" => "nullable|numeric|min:0",
        ]);

        $budgets = [];
        foreach ($data["budgets"] as $item) {
            if (!empty($item["categoria"])) {
                // Si el límite es nulo o 0, decidimos si guardarlo o no. 
                // Para mantener la categoría "viva" aunque no tenga límite (para mostrarla en la lista), guardamos 0 o null.
                // Pero el código anterior solo guardaba si > 0 o existía.
                // Aquí guardaremos todo lo que venga del formulario.
                $budgets[$item["categoria"]] = !empty($item["limite"]) ? (float) $item["limite"] : 0;
            }
        }
        
        // Reemplazamos completamente el objeto de presupuestos para reflejar eliminaciones
        $existing = $this->config->find("presupuestos");
        if ($existing) {
             $this->config->update("presupuestos", array_merge(["id" => "presupuestos"], $budgets));
             // Nota: FileStore update fusiona por defecto? 
             // Si FileStore::update hace merge, entonces no podemos borrar claves así.
             // Tendríamos que sobreescribir. 
             // Como FileStore es JSON simple, update busca ID y reemplaza datos.
             // Pero cuidado, si el método update hace array_merge interno...
             // Asumimos que update(id, data) reemplaza o fusiona. 
             // Para garantizar "borrado" de categorías, mejor recreamos si es posible, o guardamos un array "items" dentro.
             // Dado que la estructura actual es plana (cat => limit), voy a intentar borrar primero si es necesario o asumir que update reemplaza.
             // Revisando FileStore (no tengo el código a mano pero asumo comportamiento estándar):
             // Si quiero borrar categorías que ya no están, debería guardar un array limpio.
             
             // WORKAROUND: Guardar como una propiedad "items" dentro del objeto "presupuestos" sería más limpio, 
             // pero para no romper la compatibilidad actual (plana), voy a confiar en que update actualiza el registro.
             // Si FileStore hace merge, las viejas seguirán ahí.
             // Para asegurar, podríamos borrar y crear de nuevo.
             $this->config->delete("presupuestos");
             $this->config->create(array_merge(["id" => "presupuestos"], $budgets));

        } else {
             $this->config->create(array_merge(["id" => "presupuestos"], $budgets));
        }

        return redirect()->route("gastos.index")->with("success", "Presupuestos actualizados.");
    }
}