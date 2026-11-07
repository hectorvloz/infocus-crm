<?php

namespace Tests\Feature;

use App\Mcp\Support\McpContext;
use App\Models\User;
use App\Repositories\FileStore;
use App\Support\Ai\AiService;
use App\Support\Ai\AiToolBridge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AiToolModeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.disks.local.root' => storage_path('framework/testing/disks/local')]);
        foreach (['mcp_activity.json', 'clientes.json', 'leads.json', 'facturas.json', 'gastos.json', 'proyectos.json', 'reuniones.json',
            'contratos.json', 'settings.json', 'roles.json', 'mis_notas.json', 'header_reminders.json', 'ai_memories.json', 'ai_action_logs.json',
            'timeline.json', 'users.json', 'sales_documents.json', 'cotizaciones.json'] as $file) {
            Storage::disk('local')->put($file, '[]');
        }
        $this->admin = User::factory()->create(['role' => 'admin', 'name' => 'Héctor']);
        (new FileStore('clientes.json'))->create(['id' => 'c1', 'empresa' => 'Somos Domus', 'contacto_nombre' => 'Laura Navarro', 'contacto_email' => 'laura@domus.test', 'estado' => 'Activo']);
    }

    private function enableAi(bool $tools, string $provider = 'deepseek'): void
    {
        (new FileStore('settings.json'))->create([
            'id' => 'settings',
            'ai_enabled' => true,
            'ai_provider' => $provider,
            'ai_model' => 'auto',
            'ai_api_keys' => [$provider => 'ENC:' . Crypt::encryptString('test-key')],
            'ai_tools_enabled' => $tools,
        ]);
    }

    private function toolCall(string $name, array $arguments): array
    {
        return ['choices' => [['message' => ['role' => 'assistant', 'content' => '', 'tool_calls' => [[
            'id' => 'call_1', 'type' => 'function', 'function' => ['name' => $name, 'arguments' => json_encode($arguments)],
        ]]]]]];
    }

    public function test_assistant_queries_the_crm_with_mcp_tools_before_answering(): void
    {
        $this->enableAi(true);
        Http::fakeSequence('api.deepseek.com/*')
            ->push($this->toolCall('buscar_clientes', ['texto' => 'domus']))
            ->push(['choices' => [['message' => ['role' => 'assistant', 'content' => 'El correo de Somos Domus es laura@domus.test.']]]]);

        $this->actingAs($this->admin);
        $reply = (new AiService())->reply('¿Cuál es el correo de Somos Domus?');

        $this->assertSame('El correo de Somos Domus es laura@domus.test.', $reply['content']);
        $this->assertSame(['buscar_clientes'], $reply['tools_used']);

        $requests = Http::recorded();
        $this->assertCount(2, $requests);
        $toolNames = collect($requests[0][0]['tools'])->pluck('function.name');
        $this->assertTrue($toolNames->contains('buscar_clientes'));
        $this->assertFalse($toolNames->contains('crear_cliente'), 'En la fase 1 no se exponen herramientas que escriben.');
        $this->assertSame('disabled', $requests[0][0]['thinking']['type']);

        $toolMessage = collect($requests[1][0]['messages'])->firstWhere('role', 'tool');
        $this->assertSame('call_1', $toolMessage['tool_call_id']);
        $this->assertStringContainsString('laura@domus.test', $toolMessage['content']);
        $this->assertSame('buscar_clientes', $requests[1][0]['messages'][count($requests[1][0]['messages']) - 2]['tool_calls'][0]['function']['name']);
    }

    public function test_classic_mode_does_not_send_tools(): void
    {
        $this->enableAi(false);
        Http::fake(['api.deepseek.com/*' => Http::response(['choices' => [['message' => ['content' => 'Hola']]]])]);

        $this->actingAs($this->admin);
        $reply = (new AiService())->reply('Hola');

        $this->assertSame('Hola', $reply['content']);
        Http::assertSent(fn ($request) => !isset($request['tools']));
    }

    public function test_tool_loop_stops_after_too_many_rounds(): void
    {
        $this->enableAi(true);
        Http::fake(['api.deepseek.com/*' => Http::response($this->toolCall('buscar_clientes', ['texto' => 'x']))]);

        $this->actingAs($this->admin);
        $reply = (new AiService())->reply('Busca sin parar');

        $this->assertStringContainsString('demasiadas consultas', $reply['content']);
        Http::assertSentCount(6);
    }

    public function test_bridge_rejects_tools_that_write_and_keeps_data_untouched(): void
    {
        $this->actingAs($this->admin);
        $bridge = new AiToolBridge();

        $result = $bridge->call('crear_cliente', ['empresa' => 'Inventada']);

        $this->assertStringStartsWith('ERROR', $result);
        $this->assertCount(1, (new FileStore('clientes.json'))->all());
        $this->assertNotContains('confirmar_accion', $bridge->names());
    }

    public function test_bridge_runs_tools_read_only_logs_them_and_restores_the_context(): void
    {
        $this->actingAs($this->admin);
        $outer = new McpContext('token:externo', 'completo');
        app()->instance(McpContext::class, $outer);

        $result = (new AiToolBridge())->call('buscar_clientes', ['texto' => 'domus']);

        $this->assertStringContainsString('Somos Domus', $result);
        $this->assertSame($outer, app(McpContext::class));
        $log = collect((new FileStore('mcp_activity.json'))->all())->last();
        $this->assertSame('asistente', $log['connection']);
        $this->assertSame('buscar_clientes', $log['tool']);
    }

    public function test_every_read_tool_has_a_valid_function_definition(): void
    {
        $definitions = (new AiToolBridge())->definitions();

        $this->assertCount(21, $definitions);
        foreach ($definitions as $definition) {
            $this->assertSame('function', $definition['type']);
            $this->assertMatchesRegularExpression('/^[a-z_]+$/', $definition['function']['name']);
            $this->assertNotSame('', $definition['function']['description']);
            $this->assertSame('object', $definition['function']['parameters']['type']);
            $this->assertNotFalse(json_encode($definition));
        }
    }

    public function test_settings_save_the_tool_mode_switch(): void
    {
        $this->actingAs($this->admin)->put(route('settings.ai.update'), [
            'ai_enabled' => '1',
            'ai_provider' => 'deepseek',
            'ai_model' => 'auto',
            'ai_tools_enabled' => '1',
        ])->assertRedirect(route('settings.ai'));

        $this->assertTrue((new FileStore('settings.json'))->find('settings')['ai_tools_enabled']);
    }
}
