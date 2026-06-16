<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Repositories\FileStore;
use App\Repositories\TimelineStore;
use App\Support\Ai\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;

class ProyectosController extends Controller
{
    protected FileStore $store;
    protected FileStore $clientes;
    protected TimelineStore $timeline;

    protected FileStore $settings;
    protected FileStore $documents;
    protected FileStore $folders;
    protected AiService $ai;

    public function __construct()
    {
        $this->store = new FileStore('proyectos.json');
        $this->clientes = new FileStore('clientes.json');
        $this->timeline = new TimelineStore();
        $this->settings = new FileStore('settings.json');
        $this->documents = new FileStore('documentos.json');
        $this->folders = new FileStore('document_folders.json');
        $this->ai = app(AiService::class);
    }

    public function getStages() {
        $settings = $this->settings->find('settings') ?: [];
        $stages = $settings['project_stages'] ?? ['Prospecto', 'En curso', 'Revisión', 'Completado'];
        return response()->json(['stages' => $stages]);
    }

    public function updateStages(Request $request) {
        $data = $request->validate([
            'stages' => 'required|array',
            'stages.*' => 'required|string|distinct',
            'old_name' => 'sometimes|string',
            'new_name' => 'sometimes|string',
            'deleted_name' => 'sometimes|string',
            'archive_projects' => 'sometimes|boolean',
        ]);
        
        $settings = $this->settings->find('settings') ?: [];
        $settings['project_stages'] = $data['stages'];
        $this->settings->update('settings', $settings);
        
        // If a rename occurred, migrate projects
        if (!empty($data['old_name']) && !empty($data['new_name'])) {
            $projects = $this->store->all();
            foreach ($projects as $p) {
                if (($p['etapa'] ?? '') === $data['old_name']) {
                    $this->store->update($p['id'], ['etapa' => $data['new_name']]);
                }
            }
        }

        if (!empty($data['deleted_name']) && !empty($data['archive_projects'])) {
            $projects = $this->store->all();
            foreach ($projects as $p) {
                if (($p['etapa'] ?? '') !== $data['deleted_name']) {
                    continue;
                }

                $this->store->update($p['id'], [
                    'archived' => true,
                    'archived_at' => now()->toIso8601String(),
                ]);
            }
        }
        
        return response()->json(['ok' => true, 'stages' => $settings['project_stages']]);
    }


    public function timerAccion(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
            'action' => 'required|in:start,stop',
            'tarea_id' => 'nullable|string',
        ]);
        
        $project = $this->store->find($data['id']);
        if (!$project) abort(404);
        
        $logs = $project['time_logs'] ?? [];
        $current = end($logs);
        
        if ($data['action'] === 'start') {
            // Check if already running
            if ($current && empty($current['end'])) {
                return response()->json(['ok'=>true, 'item'=>$project]);
            }

            $taskId = trim((string) ($data['tarea_id'] ?? ''));
            $taskName = null;
            if ($taskId !== '') {
                $task = collect($project['tareas'] ?? [])->first(fn($t) => (string) ($t['id'] ?? '') === $taskId);
                if ($task) {
                    $taskName = (string) ($task['texto'] ?? 'Tarea');
                } else {
                    $taskId = '';
                }
            }

            $logs[] = [
                'start' => now()->timestamp,
                'end' => null,
                'user' => (string) (optional(auth()->user())->name ?? 'Sistema'),
                'task_id' => $taskId !== '' ? $taskId : null,
                'task_name' => $taskName,
            ];
        } else {
            // Stop
            if ($current && empty($current['end'])) {
                $keys = array_keys($logs);
                $lastParams = array_pop($keys);
                $endTs = now()->timestamp;
                $logs[$lastParams]['end'] = $endTs;

                $startTs = (int) ($logs[$lastParams]['start'] ?? $endTs);
                $duration = max(0, $endTs - $startTs);
                $taskId = (string) ($logs[$lastParams]['task_id'] ?? '');

                if ($taskId !== '' && $duration > 0) {
                    $tasks = collect($project['tareas'] ?? [])->map(function ($t) use ($taskId, $duration) {
                        if ((string) ($t['id'] ?? '') !== $taskId) {
                            return $t;
                        }

                        $total = (int) ($t['total_seconds'] ?? 0);
                        $t['total_seconds'] = $total + $duration;
                        return $t;
                    })->values()->all();

                    $project['tareas'] = $tasks;
                }
            }
        }

        $updated = $this->store->update($data['id'], [
            'time_logs' => $logs,
            'tareas' => $project['tareas'] ?? ($project['tareas'] ?? []),
        ]);
        return response()->json(['ok'=>true, 'item'=>$updated]);
    }

    public function timerEliminar(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
        ]);

        $project = $this->store->find($data['id']);
        if (!$project) {
            abort(404);
        }

        $logs = array_values($project['time_logs'] ?? []);
        if (empty($logs)) {
            return response()->json(['ok' => true, 'item' => $project]);
        }

        $removed = array_pop($logs);
        $taskId = trim((string) ($removed['task_id'] ?? ''));
        $startTs = (int) ($removed['start'] ?? 0);
        $endTs = (int) ($removed['end'] ?? 0);
        $duration = 0;

        if ($startTs > 0) {
            $duration = max(0, ($endTs > 0 ? $endTs : now()->timestamp) - $startTs);
        }

        $tasks = collect($project['tareas'] ?? [])->map(function ($task) use ($taskId, $duration, $endTs) {
            if ($taskId === '' || (string) ($task['id'] ?? '') !== $taskId || $duration <= 0 || $endTs <= 0) {
                return $task;
            }

            $current = (int) ($task['total_seconds'] ?? 0);
            $task['total_seconds'] = max(0, $current - $duration);
            return $task;
        })->values()->all();

        $updated = $this->store->update($data['id'], [
            'time_logs' => $logs,
            'tareas' => $tasks,
        ]);

        return response()->json(['ok' => true, 'item' => $updated]);
    }

    public function tiempoManual(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
            'tarea_id' => 'nullable|string',
            'horas' => 'nullable|integer|min:0|max:999',
            'minutos' => 'nullable|integer|min:0|max:59',
        ]);

        $project = $this->store->find($data['id']);
        if (!$project) {
            abort(404);
        }

        $hours = (int) ($data['horas'] ?? 0);
        $minutes = (int) ($data['minutos'] ?? 0);
        $seconds = ($hours * 3600) + ($minutes * 60);
        if ($seconds <= 0) {
            return response()->json(['ok' => false, 'message' => 'Tiempo invalido'], 422);
        }

        $taskId = trim((string) ($data['tarea_id'] ?? ''));
        $taskName = null;
        if ($taskId !== '') {
            $task = collect($project['tareas'] ?? [])->first(fn($t) => (string) ($t['id'] ?? '') === $taskId);
            if ($task) {
                $taskName = (string) ($task['texto'] ?? 'Tarea');
            } else {
                $taskId = '';
            }
        }

        $endTs = now()->timestamp;
        $startTs = max(0, $endTs - $seconds);
        $logs = $project['time_logs'] ?? [];
        $logs[] = [
            'start' => $startTs,
            'end' => $endTs,
            'user' => (string) (optional(auth()->user())->name ?? 'Sistema'),
            'task_id' => $taskId !== '' ? $taskId : null,
            'task_name' => $taskName,
            'manual' => true,
        ];

        $tasks = collect($project['tareas'] ?? [])->map(function ($t) use ($taskId, $seconds) {
            if ($taskId === '' || (string) ($t['id'] ?? '') !== $taskId) {
                return $t;
            }
            $t['total_seconds'] = (int) ($t['total_seconds'] ?? 0) + $seconds;
            return $t;
        })->values()->all();

        $updated = $this->store->update($data['id'], [
            'time_logs' => $logs,
            'tareas' => $tasks,
        ]);

        return response()->json(['ok' => true, 'item' => $updated]);
    }

    public function activeTimer(Request $request)
    {
        $currentUser = Str::lower(trim((string) (optional(auth()->user())->name ?? '')));
        $projects = collect($this->store->all())
            ->reject(fn ($project) => (bool) ($project['archived'] ?? false))
            ->values();

        $active = null;

        foreach ($projects as $project) {
            $logs = collect($project['time_logs'] ?? []);
            $running = $logs->last(function ($log) use ($currentUser) {
                $actor = Str::lower(trim((string) ($log['user'] ?? '')));
                return empty($log['end']) && $actor !== '' && ($currentUser === '' || $actor === $currentUser);
            });

            if (!$running) {
                continue;
            }

            $totalSeconds = $logs->reduce(function ($carry, $log) {
                $start = (int) ($log['start'] ?? 0);
                $end = (int) ($log['end'] ?? now()->timestamp);
                if ($start <= 0 || $end < $start) {
                    return $carry;
                }
                return $carry + ($end - $start);
            }, 0);

            $candidate = [
                'project_id' => (string) ($project['id'] ?? ''),
                'project_title' => (string) ($project['titulo'] ?? 'Proyecto'),
                'client' => (string) ($project['cliente'] ?? 'Sin Cliente'),
                'task_id' => (string) ($running['task_id'] ?? ''),
                'task_name' => (string) ($running['task_name'] ?? 'Temporizador activo'),
                'running' => true,
                'start' => (int) ($running['start'] ?? now()->timestamp),
                'current_seconds' => (int) $totalSeconds,
            ];

            if (!$active || $candidate['start'] > ($active['start'] ?? 0)) {
                $active = $candidate;
            }
        }

        return response()->json([
            'ok' => true,
            'item' => $active,
        ]);
    }

    public function uploadArchivo(Request $request)
    {
        $request->validate([
            'id' => 'required|string',
            'tarea_id' => 'nullable|string',
            'file' => 'required|file|max:10240', // 10MB
        ]);
        
        $project = $this->store->find($request->id);
        if (!$project) abort(404);

        $taskId = trim((string) $request->input('tarea_id', ''));
        if ($taskId !== '') {
            $taskExists = collect($project['tareas'] ?? [])
                ->contains(fn($task) => (string) ($task['id'] ?? '') === $taskId);
            abort_if(!$taskExists, 404);
        }

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $extension = strtolower((string) $file->getClientOriginalExtension());

        // Resolver cliente y crear "Sin Cliente" si hace falta
        $clienteId = trim((string) ($project['cliente_id'] ?? ''));
        $projectName = trim((string) ($project['titulo'] ?? 'Proyecto sin nombre'));
        if ($projectName === '') {
            $projectName = 'Proyecto sin nombre';
        }

        if ($clienteId === '') {
            $sinCliente = collect($this->clientes->all())
                ->first(fn($c) => Str::lower((string) ($c['empresa'] ?? '')) === 'sin cliente');

            if (!$sinCliente) {
                $sinCliente = $this->clientes->create([
                    'empresa' => 'Sin Cliente',
                    'tipo' => 'cliente',
                ]);
            }

            $clienteId = (string) $sinCliente['id'];
            $project = $this->store->update($request->id, [
                'cliente_id' => $clienteId,
                'cliente' => 'Sin Cliente',
            ]) ?? $project;
        }

        $cliente = $this->clientes->find($clienteId);
        $clienteEmpresa = trim((string) ($cliente['empresa'] ?? 'Sin Cliente'));

        // Crear carpetas lógicas en Documentos
        $rootFolderName = 'Proyectos';
        $projectFolderName = $rootFolderName.' / '.$projectName;

        // Migrar nombre antiguo "Proyecto" -> "Proyectos" para este cliente
        $foldersAll = collect($this->folders->all())->map(function ($f) use ($clienteId, $rootFolderName) {
            if ((string) ($f['cliente_id'] ?? '') !== $clienteId) {
                return $f;
            }
            if (Str::lower((string) ($f['name'] ?? '')) === 'proyecto') {
                $f['name'] = $rootFolderName;
            }
            return $f;
        })->values()->all();
        $this->folders->save($foldersAll);

        $docsAll = collect($this->documents->all())->map(function ($d) use ($clienteId, $rootFolderName) {
            if ((string) ($d['cliente_id'] ?? '') !== $clienteId) {
                return $d;
            }
            if (Str::lower((string) ($d['folder'] ?? '')) === 'proyecto') {
                $d['folder'] = $rootFolderName;
            }
            return $d;
        })->values()->all();
        $this->documents->save($docsAll);

        $existsRoot = collect($this->folders->all())
            ->first(fn($f) => (string) ($f['cliente_id'] ?? '') === $clienteId && Str::lower((string) ($f['name'] ?? '')) === Str::lower($rootFolderName));
        if (!$existsRoot) {
            $this->folders->create([
                'cliente_id' => $clienteId,
                'name' => $rootFolderName,
                'color' => '#0ea5e9',
            ]);
        }

        $existsProjectFolder = collect($this->folders->all())
            ->first(fn($f) => (string) ($f['cliente_id'] ?? '') === $clienteId && Str::lower((string) ($f['name'] ?? '')) === Str::lower($projectFolderName));
        if (!$existsProjectFolder) {
            $this->folders->create([
                'cliente_id' => $clienteId,
                'name' => $projectFolderName,
                'color' => '#84cc16',
            ]);
        }

        // Ruta física: storage/app/public/documentos/{cliente}/proyectos/{proyecto}/archivo
        $safeClient = 'infocus-'.Str::slug($clienteEmpresa);
        $safeProject = Str::slug($projectName);
        $baseName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
        $finalName = now()->format('YmdHis').'_'.Str::ulid().'_'.$baseName.($extension !== '' ? '.'.$extension : '');
        $path = 'documentos/'.$safeClient.'/proyectos/'.$safeProject.'/'.$finalName;

        Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));

        // Registro de documento para que aparezca en Documentos del cliente
        $doc = $this->documents->create([
            'cliente_id' => $clienteId,
            'folder' => $projectFolderName,
            'name' => pathinfo($originalName, PATHINFO_FILENAME),
            'original_name' => $originalName,
            'storage' => 'local',
            'path' => $path,
            'uploaded_by' => $taskId !== '' ? 'Tarea de proyecto' : 'Proyecto',
            'uploaded_at' => now()->toIso8601String(),
            'size' => $file->getSize(),
            'mime' => $file->getMimeType(),
            'ext' => $extension,
        ]);

        $filePayload = [
            'id' => $doc['id'],
            'name' => $originalName,
            'url' => route('documentos.download', ['id' => $doc['id']]),
            'download_url' => route('documentos.download', ['id' => $doc['id']]),
            'preview_url' => route('documentos.preview', ['id' => $doc['id']]),
            'date' => now()->toDateTimeString(),
            'folder' => $projectFolderName,
            'size' => $file->getSize(),
            'mime' => $file->getMimeType(),
            'ext' => $extension,
        ];

        if ($taskId !== '') {
            $tasks = array_values($project['tareas'] ?? []);
            foreach ($tasks as &$task) {
                if ((string) ($task['id'] ?? '') !== $taskId) {
                    continue;
                }
                $taskFiles = array_values($task['files'] ?? []);
                $taskFiles[] = $filePayload;
                $task['files'] = $taskFiles;
                if (empty($task['cover_file_id']) && str_starts_with((string) ($filePayload['mime'] ?? ''), 'image/')) {
                    $task['cover_file_id'] = $filePayload['id'];
                    $task['cover_url'] = $filePayload['preview_url'];
                }
                break;
            }
            unset($task);

            $updated = $this->store->update($request->id, ['tareas' => $tasks]);
            return response()->json(['ok' => true, 'item' => $updated]);
        }

        $files = $project['files'] ?? [];
        $files[] = $filePayload;
        $updated = $this->store->update($request->id, ['files' => $files]);
        return response()->json(['ok' => true, 'item' => $updated]);
    }

    public function eliminarArchivo(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
            'file_id' => 'required|string',
            'tarea_id' => 'nullable|string',
        ]);

        $project = $this->store->find($data['id']);
        abort_if(!$project, 404);

        $taskId = trim((string) ($data['tarea_id'] ?? ''));
        if ($taskId !== '') {
            $tasks = array_values($project['tareas'] ?? []);
            $removed = false;
            foreach ($tasks as &$task) {
                if ((string) ($task['id'] ?? '') !== $taskId) {
                    continue;
                }

                $taskFiles = collect($task['files'] ?? []);
                $target = $taskFiles->first(fn($f) => (string) ($f['id'] ?? '') === (string) $data['file_id']);
                if (!$target) {
                    return response()->json(['ok' => true, 'item' => $project]);
                }

                $task['files'] = $taskFiles
                    ->reject(fn($f) => (string) ($f['id'] ?? '') === (string) $data['file_id'])
                    ->values()
                    ->all();
                if ((string) ($task['cover_file_id'] ?? '') === (string) $data['file_id']) {
                    $nextCover = collect($task['files'] ?? [])->first(fn($f) => str_starts_with((string) ($f['mime'] ?? ''), 'image/'));
                    $task['cover_file_id'] = $nextCover['id'] ?? null;
                    $task['cover_url'] = $nextCover['preview_url'] ?? null;
                }
                $removed = true;
                break;
            }
            unset($task);

            if ($removed) {
                $doc = $this->documents->find((string) $data['file_id']);
                if ($doc) {
                    $storage = (string) ($doc['storage'] ?? '');
                    $path = trim((string) ($doc['path'] ?? ''));
                    if ($storage === 'local' && $path !== '' && Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }
                    $this->documents->delete((string) $data['file_id']);
                }
            }

            $updated = $this->store->update($data['id'], ['tareas' => $tasks]);
            return response()->json(['ok' => true, 'item' => $updated]);
        }

        $files = collect($project['files'] ?? []);
        $target = $files->first(fn($f) => (string) ($f['id'] ?? '') === (string) $data['file_id']);

        if (!$target) {
            return response()->json(['ok' => true, 'item' => $project]);
        }

        $doc = $this->documents->find((string) $data['file_id']);
        if ($doc) {
            $storage = (string) ($doc['storage'] ?? '');
            $path = trim((string) ($doc['path'] ?? ''));

            if ($storage === 'local' && $path !== '' && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            $this->documents->delete((string) $data['file_id']);
        }

        $updatedFiles = $files
            ->reject(fn($f) => (string) ($f['id'] ?? '') === (string) $data['file_id'])
            ->values()
            ->all();

        $updated = $this->store->update($data['id'], ['files' => $updatedFiles]);

        return response()->json(['ok' => true, 'item' => $updated]);
    }

    public function page(?string $boardSlug = null)
    {
        $clientes = collect($this->clientes->all())->sortBy('empresa')->values()->all();
        $settings = $this->settings->find('settings') ?: [];
        $stages = $settings['project_stages'] ?? ['Prospecto', 'En curso', 'Revisión', 'Completado'];
        return view('proyectos.index', compact('clientes', 'stages', 'settings', 'boardSlug'));
    }

    public function index(Request $request)
    {
        $clienteId = $request->query('cliente_id');
        $all = $this->store->all();
        $list = collect($all)
            ->reject(fn($project) => (bool) ($project['archived'] ?? false))
            ->when($clienteId, fn($c)=>$c->where('cliente_id', $clienteId))
            ->values();
        // enriquecer con nombre de cliente si existe
        $clientes = collect($this->clientes->all())->keyBy('id');
        $list = $list->map(function($p) use ($clientes){
            $p['cliente'] = $p['cliente'] ?? ($clientes[$p['cliente_id']]['empresa'] ?? null);
            return $p;
        })->values()->all();
        return response()->json(['data'=>$list]);
    }

    public function archivados(Request $request)
    {
        $clienteId = $request->query('cliente_id');
        $clientes = collect($this->clientes->all())->keyBy('id');

        $list = collect($this->store->all())
            ->filter(fn($project) => (bool) ($project['archived'] ?? false))
            ->when($clienteId, fn($c) => $c->where('cliente_id', $clienteId))
            ->map(function ($p) use ($clientes) {
                $p['cliente'] = $p['cliente'] ?? ($clientes[$p['cliente_id']]['empresa'] ?? null);
                return $p;
            })
            ->values()
            ->all();

        return response()->json(['data' => $list]);
    }

    public function show(string $id)
    {
        $item = $this->store->find($id);
        abort_if(!$item, 404);
        return response()->json(['data'=>$item]);
    }

    public function responsables(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $dbUsers = User::query()
            ->select(['id', 'name', 'email', 'role', 'profile_photo_path'])
            ->where(function ($w) {
                $w->whereNull('role')->orWhere('role', '!=', 'client');
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('name', 'like', '%'.$q.'%')
                        ->orWhere('email', 'like', '%'.$q.'%');
                });
            })
            ->limit(20)
            ->get()
            ->map(function ($u) {
                return [
                    'id' => 'db:'.$u->id,
                    'name' => (string) $u->name,
                    'email' => (string) $u->email,
                    'role' => (string) ($u->role ?? 'admin'),
                    'profile_photo' => trim((string) ($u->profile_photo_path ?? '')),
                ];
            });

        $teamUsers = collect((new FileStore('users.json'))->all())
            ->filter(fn($u) => ($u['active'] ?? true) && (($u['role'] ?? '') !== 'client'))
            ->when($q !== '', function ($col) use ($q) {
                return $col->filter(function ($u) use ($q) {
                    return str_contains(strtolower((string) ($u['name'] ?? '')), strtolower($q))
                        || str_contains(strtolower((string) ($u['email'] ?? '')), strtolower($q));
                });
            })
            ->take(20)
            ->map(function ($u) {
                return [
                    'id' => 'team:'.(string) ($u['id'] ?? ''),
                    'name' => (string) ($u['name'] ?? ''),
                    'email' => (string) ($u['email'] ?? ''),
                    'role' => (string) ($u['role'] ?? 'empleado'),
                    'profile_photo' => trim((string) ($u['profile_photo'] ?? ($u['profile_photo_path'] ?? ($u['avatar'] ?? '')))),
                ];
            });

        $data = $dbUsers
            ->merge($teamUsers)
            ->filter(fn($u) => ($u['name'] ?? '') !== '')
            ->unique(fn($u) => strtolower((string) ($u['email'] ?? $u['id'] ?? '')))
            ->values();

        return response()->json(['data' => $data]);
    }

    public function mover(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
            'etapa' => 'required|string',
        ]);
        $updated = $this->store->update($data['id'], ['etapa'=>$data['etapa']]);
        if (!empty($updated['cliente_id'])) {
            $this->timeline->add($updated['cliente_id'], 'proyecto', [
                'id'=>$updated['id'],
                'titulo'=>$updated['titulo'] ?? '',
                'etapa'=>$updated['etapa']
            ]);
        }
        return response()->json(['ok'=>true,'item'=>$updated]);
    }

    public function crear(Request $request)
    {
        $data = $request->validate([
            'cliente_id' => 'required|string',
            'titulo' => 'required|string',
            'prioridad' => 'nullable|string',
            'valor' => 'nullable|numeric',
            'progreso' => 'nullable|numeric',
            'planned_seconds' => 'nullable|integer|min:0',
            'vencimiento' => 'nullable|date',
            'inicio' => 'nullable|date',
            'miembro' => 'nullable|string',
            'responsables' => 'nullable|array',
            'responsables.*' => 'string',
            'responsable_ids' => 'nullable|array',
            'responsable_ids.*' => 'string',
            'siguiente' => 'nullable|string',
            'etapa' => 'nullable|string',
            'descripcion' => 'nullable|string',
            'cover_color' => 'nullable|string',
            'cover_image' => 'nullable|string',
        ]);
        $payload = [
            'cliente_id'=>$data['cliente_id'],
            'titulo'=>$data['titulo'],
            'etapa'=>$data['etapa'] ?? 'Prospecto',
            'prioridad'=>$data['prioridad'] ?? 'Atención',
            'valor'=>$data['valor'] ?? 0,
            'progreso'=>$data['progreso'] ?? 0,
            'planned_seconds'=>$data['planned_seconds'] ?? 0,
            'vencimiento'=>$data['vencimiento'] ?? null,
            'inicio'=>$data['inicio'] ?? null,
            'miembro'=>$data['miembro'] ?? null,
            'responsables'=>$data['responsables'] ?? [],
            'responsable_ids'=>$data['responsable_ids'] ?? [],
            'siguiente'=>$data['siguiente'] ?? null,
            'descripcion'=>trim((string) ($data['descripcion'] ?? '')) ?: null,
            'cover_color'=>trim((string) ($data['cover_color'] ?? '')) ?: null,
            'cover_image'=>trim((string) ($data['cover_image'] ?? '')) ?: null,
        ];
        $item = $this->store->create($payload);
        if (!empty($item['cliente_id']) && $item['cliente_id'] !== 'general') {
            $this->timeline->add($item['cliente_id'], 'proyecto', [
                'id'=>$item['id'],
                'titulo'=>$item['titulo'],
                'etapa'=>$item['etapa']
            ]);
        }
        $item = $this->syncGoogleCalendarEvent($item);
        return response()->json(['ok'=>true,'item'=>$item]);
    }

    public function actualizar(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
            'titulo' => 'sometimes|string',
            'prioridad' => 'sometimes|string',
            'progreso' => 'sometimes|numeric',
            'planned_seconds' => 'sometimes|integer|min:0',
            'vencimiento' => 'sometimes|date|nullable',
            'inicio' => 'sometimes|date|nullable',
            'cliente_id' => 'sometimes|nullable|string',
            'miembro' => 'sometimes|string|nullable',
            'responsables' => 'sometimes|array',
            'responsables.*' => 'string',
            'responsable_ids' => 'sometimes|array',
            'responsable_ids.*' => 'string',
            'siguiente' => 'sometimes|string|nullable',
            'valor' => 'sometimes|numeric',
            'etapa' => 'sometimes|string',
            'task_stages' => 'sometimes|array',
            'task_stages.*' => 'string',
            'descripcion' => 'sometimes|string|nullable',
            'archived' => 'sometimes|boolean',
            'archived_at' => 'sometimes|nullable|date',
        ]);
        $id = $data['id'];
        unset($data['id']);
        $updated = $this->store->update($id, $data);
        if (!empty($updated['cliente_id']) && array_key_exists('etapa', $data)) {
            $this->timeline->add($updated['cliente_id'], 'proyecto', [
                'id'=>$updated['id'],
                'titulo'=>$updated['titulo'] ?? '',
                'etapa'=>$updated['etapa']
            ]);
        }
        if (array_key_exists('vencimiento', $data) || array_key_exists('titulo', $data) || array_key_exists('inicio', $data)) {
            $updated = $this->syncGoogleCalendarEvent($updated);
        }
        return response()->json(['ok'=>true,'item'=>$updated]);
    }

    public function eliminar(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
        ]);

        $project = $this->store->find($data['id']);
        abort_if(!$project, 404);

        $this->store->delete($data['id']);

        return response()->json(['ok' => true]);
    }

    protected function syncGoogleCalendarEvent(array $project): array
    {
        $settings = $this->settings->find('settings') ?: [];
        if (empty($settings['google_calendar_enabled'])) return $project;
        $token = $this->getGoogleCalendarAccessToken($settings);
        if (!$token) return $project;
        if (empty($project['vencimiento'])) return $project;
        $calendarId = $settings['google_calendar_id'] ?? config('services.google_calendar.calendar_id', 'primary');
        $payload = $this->buildGoogleCalendarPayload($project);
        if (!$payload) return $project;
        $calendarPath = rawurlencode($calendarId);
        if (!empty($project['google_event_id'])) {
            $res = Http::withToken($token)->patch("https://www.googleapis.com/calendar/v3/calendars/{$calendarPath}/events/{$project['google_event_id']}", $payload);
            if ($res->ok()) return $project;
        }
        $res = Http::withToken($token)->post("https://www.googleapis.com/calendar/v3/calendars/{$calendarPath}/events", $payload);
        if ($res->ok()) {
            $data = $res->json();
            if (!empty($data['id'])) {
                $updated = $this->store->update($project['id'], ['google_event_id' => $data['id']]);
                return $updated ?: $project;
            }
        }
        return $project;
    }

    protected function getGoogleCalendarAccessToken(array $settings): ?string
    {
        $token = $settings['google_calendar_access_token'] ?? null;
        $expiresAt = $settings['google_calendar_expires_at'] ?? null;
        if ($token && $expiresAt) {
            $isValid = Carbon::parse($expiresAt)->subSeconds(60)->isFuture();
            if ($isValid) return $token;
        }
        $refreshToken = $settings['google_calendar_refresh_token'] ?? null;
        $clientId = config('services.google_calendar.client_id') ?: ($settings['google_calendar_client_id'] ?? null);
        $clientSecret = config('services.google_calendar.client_secret') ?: $this->decryptSetting($settings['google_calendar_client_secret'] ?? null);
        if (!$refreshToken || !$clientId || !$clientSecret) return $token;
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);
        if (!$response->ok()) return $token;
        $payload = $response->json();
        $settings['google_calendar_access_token'] = $payload['access_token'] ?? $token;
        $settings['google_calendar_expires_at'] = now()->addSeconds((int) ($payload['expires_in'] ?? 0))->toISOString();
        $this->settings->update('settings', $settings);
        return $settings['google_calendar_access_token'] ?? $token;
    }

    protected function decryptSetting(?string $value): string
    {
        if (!is_string($value) || $value === '') {
            return '';
        }
        if (str_starts_with($value, 'ENC:')) {
            try {
                return Crypt::decryptString(substr($value, 4));
            } catch (\Throwable) {
                return '';
            }
        }
        return $value;
    }

    protected function buildGoogleCalendarPayload(array $project): ?array
    {
        if (empty($project['vencimiento'])) return null;
        $start = Carbon::parse($project['vencimiento'])->toDateString();
        $end = Carbon::parse($project['vencimiento'])->addDay()->toDateString();
        $cliente = $project['cliente'] ?? null;
        $desc = $cliente ? "Cliente: {$cliente}" : null;
        return [
            'summary' => $project['titulo'] ?? 'Proyecto',
            'description' => $desc,
            'start' => ['date' => $start],
            'end' => ['date' => $end],
        ];
    }

    public function tareaAgregar(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
            'texto' => 'required|string',
            'descripcion' => 'nullable|string',
            'due_date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'priority' => 'nullable|string',
            'owners' => 'nullable|array',
            'owners.*' => 'string',
            'owner_ids' => 'nullable|array',
            'owner_ids.*' => 'string',
            'board_stage' => 'nullable|string',
        ]);
        $p = $this->store->find($data['id']);
        abort_if(!$p, 404);
        $tasks = $p['tareas'] ?? [];
        $tasks[] = [
            'id' => (string) Str::ulid(),
            'texto' => $data['texto'],
            'descripcion' => $data['descripcion'] ?? '',
            'done' => false,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? ($data['due_date'] ?? null),
            'due_date' => $data['due_date'] ?? ($data['end_date'] ?? null),
            'priority' => $data['priority'] ?? 'Atención',
            'owners' => array_values(array_filter($data['owners'] ?? [])),
            'owner_ids' => array_values(array_filter($data['owner_ids'] ?? [])),
            'board_stage' => trim((string) ($data['board_stage'] ?? '')) ?: 'Por hacer',
            'board_order' => count($tasks),
            'total_seconds' => 0,
            'subtasks' => [],
            'notes' => [],
        ];
        $updated = $this->store->update($data['id'], ['tareas'=>$tasks]);
        return response()->json(['ok'=>true,'item'=>$updated]);
    }

    public function tareaToggle(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
            'tarea_id' => 'required|string',
        ]);
        $p = $this->store->find($data['id']);
        abort_if(!$p, 404);
        $tasks = $p['tareas'] ?? [];
        foreach ($tasks as &$t) {
            if ($t['id'] === $data['tarea_id']) {
                $t['done'] = !($t['done'] ?? false);
                break;
            }
        }
        $updated = $this->store->update($data['id'], ['tareas'=>$tasks]);
        return response()->json(['ok'=>true,'item'=>$updated]);
    }

    public function tareaActualizar(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
            'tarea_id' => 'required|string',
            'texto' => 'required|string',
            'descripcion' => 'nullable|string',
            'due_date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'priority' => 'nullable|string',
            'owners' => 'nullable|array',
            'owners.*' => 'string',
            'owner_ids' => 'nullable|array',
            'owner_ids.*' => 'string',
            'board_stage' => 'nullable|string',
            'board_order' => 'nullable|integer|min:0',
        ]);

        $p = $this->store->find($data['id']);
        abort_if(!$p, 404);

        $tasks = $p['tareas'] ?? [];
        foreach ($tasks as &$t) {
            if (($t['id'] ?? null) !== $data['tarea_id']) {
                continue;
            }

            $t['texto'] = $data['texto'];
            $t['descripcion'] = $data['descripcion'] ?? ($t['descripcion'] ?? '');
            $t['start_date'] = $data['start_date'] ?? ($t['start_date'] ?? null);
            $t['end_date'] = $data['end_date'] ?? ($data['due_date'] ?? ($t['end_date'] ?? null));
            $t['due_date'] = $data['due_date'] ?? ($data['end_date'] ?? ($t['due_date'] ?? null));
            $t['priority'] = $data['priority'] ?? ($t['priority'] ?? 'Atención');
            $t['owners'] = array_values(array_filter($data['owners'] ?? ($t['owners'] ?? [])));
            $t['owner_ids'] = array_values(array_filter($data['owner_ids'] ?? ($t['owner_ids'] ?? [])));
            $t['board_stage'] = trim((string) ($data['board_stage'] ?? ($t['board_stage'] ?? 'Por hacer'))) ?: 'Por hacer';
            if (array_key_exists('board_order', $data)) {
                $t['board_order'] = (int) $data['board_order'];
            }
            $t['subtasks'] = array_values($t['subtasks'] ?? []);
            $t['notes'] = array_values($t['notes'] ?? []);
            break;
        }

        $updated = $this->store->update($data['id'], ['tareas' => $tasks]);
        return response()->json(['ok' => true, 'item' => $updated]);
    }

    public function tareaIaApoyo(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
            'tarea_id' => 'required|string',
            'message' => 'required|string|max:3000',
        ]);

        $project = $this->store->find($data['id']);
        abort_if(!$project, 404);

        $tasks = array_values($project['tareas'] ?? []);
        $targetIndex = null;
        foreach ($tasks as $index => $task) {
            if ((string) ($task['id'] ?? '') === (string) $data['tarea_id']) {
                $targetIndex = $index;
                break;
            }
        }
        abort_if($targetIndex === null, 404);

        $task = $tasks[$targetIndex];
        $prompt = $this->buildTaskAiSupportPrompt($project, $task, (string) $data['message']);
        $result = $this->ai->reply($prompt, [], [
            'current_project' => [
                'id' => $project['id'] ?? '',
                'title' => $project['titulo'] ?? '',
            ],
        ]);

        $rawContent = (string) ($result['content'] ?? '');
        $plan = $this->decodeTaskAiSupportPlan($rawContent);
        if (!$plan) {
            $friendlyMessage = trim($rawContent);
            if ($friendlyMessage === '' || str_contains($friendlyMessage, '{')) {
                $friendlyMessage = 'La IA no devolvió un plan aplicable. Intenta pedirlo con más detalle.';
            }
            return response()->json([
                'ok' => false,
                'message' => $friendlyMessage,
                'raw' => $rawContent,
            ], 422);
        }

        $summary = trim((string) ($plan['summary'] ?? 'Listo, ajusté el checklist.'));
        $currentSubtasks = array_values($task['subtasks'] ?? []);

        if (array_key_exists('replace_subtasks', $plan) && is_array($plan['replace_subtasks'])) {
            $currentSubtasks = $this->normalizeAiSubtasks($plan['replace_subtasks']);
        }

        if (!empty($plan['add_subtasks']) && is_array($plan['add_subtasks'])) {
            $currentSubtasks = array_values(array_merge(
                $currentSubtasks,
                $this->normalizeAiSubtasks($plan['add_subtasks'])
            ));
        }

        $tasks[$targetIndex]['subtasks'] = $currentSubtasks;

        if (!empty($plan['add_tasks']) && is_array($plan['add_tasks'])) {
            foreach ($plan['add_tasks'] as $item) {
                $title = trim(is_array($item) ? (string) ($item['texto'] ?? $item['title'] ?? '') : (string) $item);
                if ($title === '') {
                    continue;
                }
                $tasks[] = [
                    'id' => (string) Str::ulid(),
                    'texto' => Str::limit($title, 180, ''),
                    'descripcion' => '',
                    'done' => false,
                    'start_date' => null,
                    'end_date' => null,
                    'due_date' => null,
                    'priority' => 'Atención',
                    'owners' => [],
                    'owner_ids' => [],
                    'board_stage' => $task['board_stage'] ?? 'Por hacer',
                    'board_order' => count($tasks),
                    'total_seconds' => 0,
                    'subtasks' => [],
                    'notes' => [],
                ];
            }
        }

        $updated = $this->store->update($data['id'], ['tareas' => $tasks]);

        return response()->json([
            'ok' => true,
            'message' => $summary,
            'item' => $updated,
        ]);
    }

    public function tareaMover(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
            'tarea_id' => 'required|string',
            'board_stage' => 'required|string',
            'board_order' => 'nullable|integer|min:0',
        ]);

        $p = $this->store->find($data['id']);
        abort_if(!$p, 404);

        $targetStage = trim((string) $data['board_stage']);
        $targetOrder = max(0, (int) ($data['board_order'] ?? 0));
        $tasks = array_values($p['tareas'] ?? []);
        $moving = null;
        $remaining = [];

        foreach ($tasks as $task) {
            if ((string) ($task['id'] ?? '') === (string) $data['tarea_id']) {
                $moving = $task;
                continue;
            }
            $remaining[] = $task;
        }

        if (!$moving) {
            return response()->json(['ok' => true, 'item' => $p]);
        }

        $moving['board_stage'] = $targetStage;
        $before = [];
        $after = [];
        $stageIndex = 0;

        foreach ($remaining as $task) {
            $taskStage = trim((string) ($task['board_stage'] ?? 'Por hacer')) ?: 'Por hacer';
            if ($taskStage === $targetStage && $stageIndex++ >= $targetOrder) {
                $after[] = $task;
            } else {
                $before[] = $task;
            }
        }

        $updatedTasks = array_values(array_merge($before, [$moving], $after));
        $stageCounters = [];
        foreach ($updatedTasks as &$task) {
            $stage = trim((string) ($task['board_stage'] ?? 'Por hacer')) ?: 'Por hacer';
            $task['board_stage'] = $stage;
            $task['board_order'] = $stageCounters[$stage] ?? 0;
            $stageCounters[$stage] = ($stageCounters[$stage] ?? 0) + 1;
        }
        unset($task);

        $updated = $this->store->update($data['id'], ['tareas' => $updatedTasks]);
        return response()->json(['ok' => true, 'item' => $updated]);
    }

    public function tareaNotaAgregar(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
            'tarea_id' => 'required|string',
            'texto' => 'required|string',
        ]);

        $p = $this->store->find($data['id']);
        abort_if(!$p, 404);

        $actorName = trim((string) (optional($request->user())->name ?: $request->session()->get('user.name', 'Usuario')));
        if ($actorName === '') {
            $actorName = 'Usuario';
        }

        $tasks = $p['tareas'] ?? [];
        foreach ($tasks as &$t) {
            if ((string) ($t['id'] ?? '') !== (string) $data['tarea_id']) {
                continue;
            }

            $notes = array_values($t['notes'] ?? []);
            $notes[] = [
                'id' => (string) Str::ulid(),
                'texto' => trim((string) $data['texto']),
                'created_at' => now()->toIso8601String(),
                'updated_at' => null,
                'author_name' => $actorName,
                'author_id' => optional($request->user())->id,
            ];
            $t['notes'] = $notes;
            break;
        }

        $updated = $this->store->update($data['id'], ['tareas' => $tasks]);
        return response()->json(['ok' => true, 'item' => $updated]);
    }

    public function tareaNotaActualizar(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
            'tarea_id' => 'required|string',
            'nota_id' => 'required|string',
            'texto' => 'required|string',
        ]);

        $p = $this->store->find($data['id']);
        abort_if(!$p, 404);

        $tasks = $p['tareas'] ?? [];
        foreach ($tasks as &$t) {
            if ((string) ($t['id'] ?? '') !== (string) $data['tarea_id']) {
                continue;
            }

            $notes = array_values($t['notes'] ?? []);
            foreach ($notes as &$note) {
                if ((string) ($note['id'] ?? '') !== (string) $data['nota_id']) {
                    continue;
                }

                $note['texto'] = trim((string) $data['texto']);
                $note['updated_at'] = now()->toIso8601String();
                break;
            }
            $t['notes'] = $notes;
            break;
        }

        $updated = $this->store->update($data['id'], ['tareas' => $tasks]);
        return response()->json(['ok' => true, 'item' => $updated]);
    }

    public function tareaNotaEliminar(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
            'tarea_id' => 'required|string',
            'nota_id' => 'required|string',
        ]);

        $p = $this->store->find($data['id']);
        abort_if(!$p, 404);

        $tasks = $p['tareas'] ?? [];
        foreach ($tasks as &$t) {
            if ((string) ($t['id'] ?? '') !== (string) $data['tarea_id']) {
                continue;
            }

            $t['notes'] = array_values(array_filter(
                $t['notes'] ?? [],
                fn($note) => (string) ($note['id'] ?? '') !== (string) $data['nota_id']
            ));
            break;
        }

        $updated = $this->store->update($data['id'], ['tareas' => $tasks]);
        return response()->json(['ok' => true, 'item' => $updated]);
    }

    public function tareaEliminar(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
            'tarea_id' => 'required|string',
        ]);

        $p = $this->store->find($data['id']);
        abort_if(!$p, 404);

        $tasks = [];
        $archivedTasks = array_values($p['archived_tasks'] ?? []);
        foreach (($p['tareas'] ?? []) as $task) {
            if ((string) ($task['id'] ?? '') !== (string) $data['tarea_id']) {
                $tasks[] = $task;
                continue;
            }

            $task['archived_at'] = now()->toISOString();
            $task['archived_from_stage'] = $task['board_stage'] ?? null;
            $archivedTasks[] = $task;
        }

        $updated = $this->store->update($data['id'], [
            'tareas' => array_values($tasks),
            'archived_tasks' => $archivedTasks,
        ]);
        return response()->json(['ok' => true, 'item' => $updated]);
    }

    public function tareaRestaurar(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
            'tarea_id' => 'required|string',
        ]);

        $p = $this->store->find($data['id']);
        abort_if(!$p, 404);

        $tasks = array_values($p['tareas'] ?? []);
        $archivedTasks = [];
        foreach (($p['archived_tasks'] ?? []) as $task) {
            if ((string) ($task['id'] ?? '') !== (string) $data['tarea_id']) {
                $archivedTasks[] = $task;
                continue;
            }

            unset($task['archived_at'], $task['archived_from_stage']);
            $task['board_stage'] = trim((string) ($task['board_stage'] ?? 'Por hacer')) ?: 'Por hacer';
            $task['board_order'] = count($tasks);
            $tasks[] = $task;
        }

        $updated = $this->store->update($data['id'], [
            'tareas' => $tasks,
            'archived_tasks' => array_values($archivedTasks),
        ]);
        return response()->json(['ok' => true, 'item' => $updated]);
    }

    public function tareaEliminarArchivada(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
            'tarea_id' => 'required|string',
        ]);

        $p = $this->store->find($data['id']);
        abort_if(!$p, 404);

        $archivedTasks = array_values(array_filter(
            $p['archived_tasks'] ?? [],
            fn($task) => (string) ($task['id'] ?? '') !== (string) $data['tarea_id']
        ));

        $updated = $this->store->update($data['id'], ['archived_tasks' => $archivedTasks]);
        return response()->json(['ok' => true, 'item' => $updated]);
    }

    public function subtareaAgregar(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
            'tarea_id' => 'required|string',
            'texto' => 'required|string',
        ]);

        $p = $this->store->find($data['id']);
        abort_if(!$p, 404);

        $tasks = $p['tareas'] ?? [];
        foreach ($tasks as &$t) {
            if (($t['id'] ?? null) !== $data['tarea_id']) {
                continue;
            }

            $subtasks = $t['subtasks'] ?? [];
            $subtasks[] = [
                'id' => (string) Str::ulid(),
                'texto' => $data['texto'],
                'done' => false,
                'owners' => [],
                'owner_ids' => [],
                'due_date' => '',
                'priority' => 'Atención',
            ];
            $t['subtasks'] = $subtasks;
            break;
        }

        $updated = $this->store->update($data['id'], ['tareas' => $tasks]);
        return response()->json(['ok' => true, 'item' => $updated]);
    }

    public function subtareaActualizar(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
            'tarea_id' => 'required|string',
            'subtarea_id' => 'required|string',
            'texto' => 'nullable|string',
            'owners' => 'nullable|array',
            'owners.*' => 'nullable|string',
            'owner_ids' => 'nullable|array',
            'owner_ids.*' => 'nullable|string',
            'due_date' => 'nullable|string',
            'priority' => 'nullable|string',
        ]);

        $p = $this->store->find($data['id']);
        abort_if(!$p, 404);

        $tasks = $p['tareas'] ?? [];
        foreach ($tasks as &$t) {
            if (($t['id'] ?? null) !== $data['tarea_id']) {
                continue;
            }

            $subtasks = $t['subtasks'] ?? [];
            foreach ($subtasks as &$s) {
                if (($s['id'] ?? null) !== $data['subtarea_id']) {
                    continue;
                }

                if (array_key_exists('texto', $data)) {
                    $s['texto'] = trim((string) ($data['texto'] ?? ''));
                }
                if (array_key_exists('owners', $data)) {
                    $s['owners'] = array_values(array_filter($data['owners'] ?? []));
                }
                if (array_key_exists('owner_ids', $data)) {
                    $s['owner_ids'] = array_values(array_filter($data['owner_ids'] ?? []));
                }
                if (array_key_exists('due_date', $data)) {
                    $s['due_date'] = trim((string) ($data['due_date'] ?? ''));
                }
                if (array_key_exists('priority', $data)) {
                    $s['priority'] = trim((string) ($data['priority'] ?? '')) ?: 'Atención';
                }
                break;
            }
            $t['subtasks'] = $subtasks;
            break;
        }

        $updated = $this->store->update($data['id'], ['tareas' => $tasks]);
        return response()->json(['ok' => true, 'item' => $updated]);
    }

    public function subtareaToggle(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
            'tarea_id' => 'required|string',
            'subtarea_id' => 'required|string',
        ]);

        $p = $this->store->find($data['id']);
        abort_if(!$p, 404);

        $tasks = $p['tareas'] ?? [];
        foreach ($tasks as &$t) {
            if (($t['id'] ?? null) !== $data['tarea_id']) {
                continue;
            }

            $subtasks = $t['subtasks'] ?? [];
            foreach ($subtasks as &$s) {
                if (($s['id'] ?? null) !== $data['subtarea_id']) {
                    continue;
                }
                $s['done'] = !($s['done'] ?? false);
                break;
            }
            $t['subtasks'] = $subtasks;
            break;
        }

        $updated = $this->store->update($data['id'], ['tareas' => $tasks]);
        return response()->json(['ok' => true, 'item' => $updated]);
    }

    public function subtareaEliminar(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string',
            'tarea_id' => 'required|string',
            'subtarea_id' => 'required|string',
        ]);

        $p = $this->store->find($data['id']);
        abort_if(!$p, 404);

        $tasks = $p['tareas'] ?? [];
        foreach ($tasks as &$t) {
            if (($t['id'] ?? null) !== $data['tarea_id']) {
                continue;
            }

            $t['subtasks'] = array_values(array_filter(
                $t['subtasks'] ?? [],
                fn($s) => (string) ($s['id'] ?? '') !== (string) $data['subtarea_id']
            ));
            break;
        }

        $updated = $this->store->update($data['id'], ['tareas' => $tasks]);
        return response()->json(['ok' => true, 'item' => $updated]);
    }

    private function buildTaskAiSupportPrompt(array $project, array $task, string $message): string
    {
        $subtasks = collect($task['subtasks'] ?? [])
            ->map(fn ($item) => '- ' . (string) ($item['texto'] ?? ''))
            ->implode("\n");

        return "Actúa como asistente de gestión de proyectos. Responde solo con JSON válido, sin markdown.\n"
            . "Puedes agregar subtareas, reemplazar subtareas existentes o agregar tareas nuevas al mismo tablero.\n"
            . "Formato exacto:\n"
            . "{\"summary\":\"texto breve\",\"replace_subtasks\":[{\"texto\":\"...\",\"priority\":\"Atención\"}],\"add_subtasks\":[{\"texto\":\"...\",\"priority\":\"Atención\"}],\"add_tasks\":[{\"texto\":\"...\"}]}\n"
            . "Usa solo estas prioridades: Con calma, Atención, Urgente. Si no aplica, usa Atención.\n"
            . "Si el usuario pide reescribir, ordenar o mejorar el checklist actual, usa replace_subtasks. Si pide añadir, usa add_subtasks o add_tasks.\n\n"
            . "Proyecto: " . (string) ($project['titulo'] ?? 'Proyecto') . "\n"
            . "Tarea actual: " . (string) ($task['texto'] ?? 'Tarea') . "\n"
            . "Descripción: " . (string) ($task['descripcion'] ?? '') . "\n"
            . "Checklist actual:\n" . ($subtasks !== '' ? $subtasks : '- Sin subtareas') . "\n\n"
            . "Solicitud del usuario: {$message}";
    }

    private function decodeTaskAiSupportPlan(string $content): ?array
    {
        $text = trim($content);
        if ($text === '') {
            return null;
        }

        $text = preg_replace('/^```(?:json)?\s*|\s*```$/u', '', $text) ?? $text;
        if (!str_starts_with(trim($text), '{') && preg_match('/\{.*\}/su', $text, $match)) {
            $text = $match[0];
        }

        $decoded = json_decode($text, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function normalizeAiSubtasks(array $items): array
    {
        $out = [];
        foreach ($items as $item) {
            $text = trim(is_array($item) ? (string) ($item['texto'] ?? $item['title'] ?? '') : (string) $item);
            if ($text === '') {
                continue;
            }
            $priority = trim(is_array($item) ? (string) ($item['priority'] ?? '') : '');
            if (!in_array($priority, ['Con calma', 'Atención', 'Urgente'], true)) {
                $priority = 'Atención';
            }
            $out[] = [
                'id' => (string) Str::ulid(),
                'texto' => Str::limit($text, 180, ''),
                'done' => false,
                'owners' => [],
                'owner_ids' => [],
                'due_date' => '',
                'priority' => $priority,
            ];
        }
        return $out;
    }
}
