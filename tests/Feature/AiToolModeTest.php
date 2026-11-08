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
use Illuminate\Support\Facades\Mail;
use App\Support\Ai\AiApproval;
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
            'timeline.json', 'users.json', 'sales_documents.json', 'cotizaciones.json', 'mcp_pending_actions.json', 'email_templates.json',
            'email_history.json', 'mcp_history.json'] as $file) {
            Storage::disk('local')->put($file, '[]');
        }
        Mail::fake();
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
        $this->assertFalse($toolNames->contains('crear_cliente'), 'Crear clientes sigue fuera del asistente.');
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

    public function test_bridge_does_not_expose_high_impact_direct_writes(): void
    {
        $this->actingAs($this->admin);
        $bridge = new AiToolBridge();

        $result = $bridge->call('crear_cliente', ['empresa' => 'Inventada']);

        $this->assertStringStartsWith('ERROR', $result);
        $this->assertCount(1, (new FileStore('clientes.json'))->all());
        foreach (['crear_cliente', 'crear_factura_borrador', 'crear_proyecto', 'actualizar_proyecto', 'convertir_lead_cliente'] as $name) {
            $this->assertNotContains($name, $bridge->names(), "{$name} no debe estar en el asistente todavía.");
        }
        $this->assertFalse($bridge->writes('buscar_clientes'));
        $this->assertTrue($bridge->writes('crear_recordatorio'));
    }

    public function test_assistant_creates_a_reminder_when_asked(): void
    {
        $this->enableAi(true);
        Http::fakeSequence('api.deepseek.com/*')
            ->push($this->toolCall('crear_recordatorio', ['texto' => 'Llamar a Laura', 'fecha' => '2026-11-13', 'prioridad' => 'Atención']))
            ->push(['choices' => [['message' => ['role' => 'assistant', 'content' => 'Listo: te recuerdo el viernes 13 llamar a Laura.']]]]);

        $this->actingAs($this->admin);
        $reply = (new AiService())->reply('Recuérdame el viernes llamar a Laura');

        $this->assertSame(['crear_recordatorio'], $reply['tools_used']);
        $this->assertStringContainsString('Llamar a Laura', (new AiToolBridge())->call('ver_recordatorios', []));
        $toolMessage = collect(Http::recorded()[1][0]['messages'])->firstWhere('role', 'tool');
        $this->assertStringNotContainsString('ERROR', $toolMessage['content']);
        $log = collect((new FileStore('mcp_activity.json'))->all())->firstWhere('tool', 'crear_recordatorio');
        $this->assertSame('asistente', $log['connection']);
        $this->assertSame('ok', $log['result']);
    }

    public function test_write_tools_respect_the_user_role(): void
    {
        (new FileStore('roles.json'))->create(['id' => 'ia_solo_lectura', 'permissions' => ['leads.read']]);
        $member = User::factory()->create(['role' => 'ia_solo_lectura']);
        $this->actingAs($member);

        $result = (new AiToolBridge())->call('crear_lead', ['nombre' => 'Pedro Ruiz', 'email' => 'pedro@ruiz.test']);

        $this->assertStringStartsWith('ERROR', $result);
        $this->assertStringContainsString('leads.create', $result);
        $this->assertSame([], (new FileStore('leads.json'))->all());
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

    public function test_every_tool_has_a_valid_function_definition(): void
    {
        $definitions = (new AiToolBridge())->definitions();

        $this->assertCount(50, $definitions);
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

    private array $replies = [];

    private bool $repliesFaked = false;

    /** Respuestas del modelo en orden; se puede llamar varias veces en un mismo test. */
    private function queueReplies(array $replies): void
    {
        $this->replies = $replies;
        if (!$this->repliesFaked) {
            $this->repliesFaked = true;
            Http::fake(function ($request) {
                $next = array_shift($this->replies);

                return Http::response(is_callable($next) ? $next($request) : $next);
            });
        }
    }

    private function text(string $content): array
    {
        return ['choices' => [['message' => ['role' => 'assistant', 'content' => $content]]]];
    }

    private function prepareEmailTurn(): array
    {
        $queue = [
            $this->toolCall('preparar_correo', ['cliente_id' => 'c1', 'asunto' => 'Seguimiento', 'mensaje' => '<p>Hola {cliente}, te escribo por el diseño.</p>']),
            $this->text('Te preparé el correo para Laura. ¿Lo envío?'),
        ];
        $this->queueReplies($queue);
        $this->actingAs($this->admin);

        return (new AiService())->reply('Mándale un correo a Somos Domus por el diseño');
    }

    public function test_two_step_email_shows_the_real_preview_and_sends_only_after_explicit_yes(): void
    {
        $this->enableAi(true);
        $first = $this->prepareEmailTurn();

        $this->assertStringContainsString('Te preparé el correo', $first['content']);
        $this->assertStringContainsString('Asunto: Seguimiento', $first['content'], 'La vista previa la agrega Infocus tal cual.');
        $this->assertMatchesRegularExpression('/Código de confirmación: [A-Z0-9]{3}-[A-Z0-9]{3}\./', $first['content']);
        $this->assertStringNotContainsString('confirmar_accion', $first['content']);
        Mail::assertNothingSent();

        $code = AiToolBridge::codesIn($first['content'])[0];
        $history = [['role' => 'user', 'content' => 'Mándale un correo a Somos Domus por el diseño'], ['role' => 'assistant', 'content' => $first['content']]];
        $queue = [$this->toolCall('confirmar_accion', ['codigo' => $code]), $this->text('Listo, enviado.')];
        $this->queueReplies($queue);

        $second = (new AiService())->reply('Sí, envíalo', $history);

        $this->assertSame(['confirmar_accion'], $second['tools_used']);
        Mail::assertSentCount(1);
    }

    public function test_confirmation_is_blocked_when_the_user_did_not_approve(): void
    {
        $this->enableAi(true);
        $first = $this->prepareEmailTurn();
        $code = AiToolBridge::codesIn($first['content'])[0];
        $history = [['role' => 'user', 'content' => 'Mándale un correo'], ['role' => 'assistant', 'content' => $first['content']]];

        $queue = [$this->toolCall('confirmar_accion', ['codigo' => $code]), $this->text('Ok.')];
        $this->queueReplies($queue);
        (new AiService())->reply('¿Y cuánto le cobramos por el diseño?', $history);

        Mail::assertNothingSent();
        $toolMessage = collect(Http::recorded()->last()[0]['messages'])->firstWhere('role', 'tool');
        $this->assertStringStartsWith('ERROR: no confirmes todavía', $toolMessage['content']);
    }

    public function test_model_cannot_prepare_and_confirm_in_the_same_turn(): void
    {
        $this->enableAi(true);
        $queue = [
            $this->toolCall('preparar_correo', ['para' => ['laura@domus.test'], 'asunto' => 'Hola', 'mensaje' => 'Texto']),
            function ($request) {
                $prepared = collect($request['messages'])->where('role', 'tool')->last()['content'];

                return $this->toolCall('confirmar_accion', ['codigo' => AiToolBridge::codesIn($prepared)[0]]);
            },
            $this->text('Listo.'),
        ];
        $this->queueReplies($queue);
        $this->actingAs($this->admin);

        (new AiService())->reply('Sí, mándale un hola a Laura');

        Mail::assertNothingSent();
        $toolMessage = collect(Http::recorded()->last()[0]['messages'])->where('role', 'tool')->last();
        $this->assertStringContainsString('no es el de la vista previa que el usuario acaba de ver', $toolMessage['content']);
    }

    public function test_approval_detection_is_strict(): void
    {
        foreach (['sí', 'Sí, envíalo', 'dale', 'listo, mándalo', 'ok', 'confirmo'] as $yes) {
            $this->assertTrue(AiApproval::isExplicit($yes), $yes);
        }
        foreach (['no', 'sí pero cambia el asunto', '¿y cuánto vale?', 'espera', 'mejor mañana', '', 'mándale otro correo a Pedro con el resumen de la reunión y los entregables'] as $no) {
            $this->assertFalse(AiApproval::isExplicit($no), $no);
        }
    }

    public function test_gemini_uses_function_calling_with_the_same_tools(): void
    {
        $this->enableAi(true, 'gemini');
        Http::fakeSequence('generativelanguage.googleapis.com/*')
            ->push(['candidates' => [['content' => ['role' => 'model', 'parts' => [['functionCall' => ['name' => 'buscar_clientes', 'args' => ['texto' => 'domus']], 'thoughtSignature' => 'sig-1']]]]]])
            ->push(['candidates' => [['content' => ['role' => 'model', 'parts' => [['text' => 'Laura Navarro, laura@domus.test.']]]]]]);

        $this->actingAs($this->admin);
        $reply = (new AiService())->reply('¿Quién es el contacto de Somos Domus?');

        $this->assertSame('Laura Navarro, laura@domus.test.', $reply['content']);
        $this->assertSame(['buscar_clientes'], $reply['tools_used']);
        $requests = Http::recorded();
        $declarations = collect($requests[0][0]['tools'][0]['functionDeclarations'])->pluck('name');
        $this->assertTrue($declarations->contains('buscar_clientes'));
        $contents = $requests[1][0]['contents'];
        $model = collect($contents)->firstWhere('role', 'model');
        $this->assertSame('buscar_clientes', $model['parts'][0]['functionCall']['name']);
        $this->assertSame('sig-1', $model['parts'][0]['thoughtSignature']);
        $response = collect(end($contents)['parts'])->first();
        $this->assertSame('buscar_clientes', $response['functionResponse']['name']);
        $this->assertStringContainsString('laura@domus.test', $response['functionResponse']['response']['content']);
    }
}
