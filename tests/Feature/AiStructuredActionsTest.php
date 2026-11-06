<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\FileStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AiStructuredActionsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.disks.local.root' => storage_path('framework/testing/disks/local')]);
        foreach (['ai_action_logs.json', 'ai_undo_actions.json', 'ai_chats.json', 'clientes.json', 'users.json', 'timeline.json', 'settings.json'] as $file) {
            Storage::disk('local')->put($file, '[]');
        }
        Storage::disk('local')->put('proyectos.json', json_encode([[
            'id' => 'p1', 'titulo' => 'Somos Domus', 'task_stages' => ['Por hacer', 'En proceso', 'Revisión', 'Terminado'],
            'tareas' => [
                ['id' => 't0', 'texto' => 'Brief', 'board_stage' => 'Por hacer', 'board_order' => 0, 'descripcion' => '', 'notes' => [], 'subtasks' => []],
                ['id' => 't1', 'texto' => 'Definir arquitectura de información', 'board_stage' => 'En proceso', 'board_order' => 0, 'descripcion' => '', 'notes' => [], 'subtasks' => []],
                ['id' => 't2', 'texto' => 'Wireframes de Hero', 'board_stage' => 'En proceso', 'board_order' => 1, 'descripcion' => '', 'notes' => [], 'subtasks' => []],
            ],
        ]]));
        Storage::disk('local')->put('leads.json', json_encode([['id' => 'l1', 'nombre' => 'Pedro Ruiz', 'email' => 'pedro@ruiz.test', 'etapa' => 'Posible cliente', 'notas_lista' => []]]));
        $this->admin = User::factory()->create(['role' => 'admin', 'name' => 'Héctor']);
    }

    private function execute(string $type, array $fields, array $context = [])
    {
        return $this->actingAs($this->admin)->postJson(route('api.ai.actions.execute'), [
            'proposal' => 'Propuesta de la IA',
            'context' => $context + ['structured_action' => ['type' => $type, 'fields' => $fields]],
        ]);
    }

    private function task(string $id): array
    {
        return collect((new FileStore('proyectos.json'))->find('p1')['tareas'])->firstWhere('id', $id);
    }

    public function test_adds_description_to_task_instead_of_a_note(): void
    {
        $this->execute('update_project_task', ['project_id' => 'p1', 'task_id' => 't1', 'description' => "## Objetivo\nDefinir la estructura de navegación.\n\n## Entregables\n- Mapa del sitio\n- Menú principal"])
            ->assertOk()->assertJsonPath('project_action.type', 'task_updated');

        $task = $this->task('t1');
        $this->assertStringContainsString('Mapa del sitio', $task['descripcion']);
        $this->assertStringContainsString('Objetivo', $task['descripcion']);
        $this->assertSame([], $task['notes']);
    }

    public function test_task_by_name_moves_column_and_appends_description(): void
    {
        $this->execute('update_project_task', ['project_id' => 'p1', 'task_id' => 't2', 'description' => 'Primera versión'])->assertOk();
        $this->execute('update_project_task', ['project' => 'Somos Domus', 'task' => 'Wireframes de Hero', 'description' => 'Ajustes del cliente', 'description_mode' => 'append', 'column' => 'Revisión'])->assertOk();

        $task = $this->task('t2');
        $this->assertSame('Revisión', $task['board_stage']);
        $this->assertStringContainsString('Primera versión', $task['descripcion']);
        $this->assertStringContainsString('Ajustes del cliente', $task['descripcion']);
    }

    public function test_unknown_task_is_not_guessed(): void
    {
        $this->execute('update_project_task', ['project_id' => 'p1', 'task' => 'Tarea que no existe', 'description' => 'x'])->assertStatus(422);
        $this->assertSame('', $this->task('t0')['descripcion']);
    }

    public function test_adds_tasks_to_existing_project_without_creating_another(): void
    {
        $this->execute('add_project_task', ['project_id' => 'p1', 'tasks' => [
            ['title' => 'Paleta de color', 'column' => 'Por hacer', 'description' => 'Definir colores', 'subtasks' => ['Primarios', 'Secundarios']],
            ['title' => 'Tipografías', 'column' => 'En proceso'],
        ]])->assertOk();

        $projects = (new FileStore('proyectos.json'))->all();
        $this->assertCount(1, $projects);
        $titles = array_column($projects[0]['tareas'], 'texto');
        $this->assertContains('Paleta de color', $titles);
        $this->assertCount(2, collect($projects[0]['tareas'])->firstWhere('texto', 'Paleta de color')['subtasks']);
        $this->assertSame('En proceso', collect($projects[0]['tareas'])->firstWhere('texto', 'Tipografías')['board_stage']);
    }

    public function test_subtasks_and_notes_go_to_the_right_task(): void
    {
        $this->execute('add_project_subtask', ['project_id' => 'p1', 'task_id' => 't2', 'subtasks' => ['Versión móvil']])->assertOk();
        $this->execute('add_project_note', ['project_id' => 'p1', 'task_id' => 't2', 'note' => 'El cliente aprobó el hero'])->assertOk();

        $task = $this->task('t2');
        $this->assertSame('Versión móvil', $task['subtasks'][0]['texto']);
        $this->assertSame('El cliente aprobó el hero', $task['notes'][0]['texto']);
        $this->assertSame([], $this->task('t0')['notes']);
    }

    public function test_lead_actions(): void
    {
        $this->execute('update_lead', ['stage' => 'Contactado', 'note' => 'Le envié la propuesta'], ['current_lead' => ['id' => 'l1']])->assertOk();
        $lead = (new FileStore('leads.json'))->find('l1');
        $this->assertSame('Contactado', $lead['etapa']);
        $this->assertSame('Le envié la propuesta', $lead['notas_lista'][0]['texto']);

        $this->execute('update_lead', ['lead_id' => 'l1', 'stage' => 'Inventada'])->assertStatus(422);
        $this->execute('create_lead', ['name' => 'Pedro Ruiz'])->assertStatus(422);
        $this->execute('create_lead', ['name' => 'Marta Díaz', 'email' => 'marta@diaz.test', 'budget' => 3000000])->assertOk();
        $this->assertCount(2, (new FileStore('leads.json'))->all());
    }

    public function test_project_context_lists_tasks_with_ids_in_board_order(): void
    {
        $summary = (new \App\Support\Ai\ProjectAiContext())->summarize((new FileStore('proyectos.json'))->find('p1'));

        $this->assertStringContainsString('[En proceso #1] Definir arquitectura de información (task_id: t1)', $summary);
        $this->assertStringContainsString('[En proceso #2] Wireframes de Hero (task_id: t2)', $summary);
        $this->assertStringContainsString('project_id: p1', $summary);
    }

    public function test_screen_context_knows_what_is_open(): void
    {
        Storage::disk('local')->put('facturas.json', json_encode([['id' => 'f1', 'numero' => 'INV-0068', 'cliente_nombre' => 'Café Laurel', 'estado' => 'Pendiente', 'total' => 1500000, 'moneda' => 'COP']]));
        $this->actingAs($this->admin);
        $service = app(\App\Support\Ai\AiService::class);
        $method = new \ReflectionMethod($service, 'screenContext');

        $invoice = $method->invoke($service, '/facturas/f1', []);
        $this->assertStringContainsString('Factura INV-0068 (factura_id: f1)', $invoice);
        $this->assertStringContainsString('sección Facturas', $invoice);

        $lead = $method->invoke($service, '/leads', ['current_lead' => ['id' => 'l1']]);
        $this->assertStringContainsString('Lead abierto en el panel lateral: Pedro Ruiz (lead_id: l1)', $lead);
    }
}
