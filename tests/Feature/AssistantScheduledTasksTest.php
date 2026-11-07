<?php

namespace Tests\Feature;

use App\Mail\GenericMail;
use App\Models\User;
use App\Repositories\FileStore;
use App\Support\Ai\AiService;
use App\Support\Ai\AiToolBridge;
use App\Support\Ai\ScheduledTasks;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssistantScheduledTasksTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private array $aiReplies = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.disks.local.root' => storage_path('framework/testing/disks/local')]);
        foreach (['mcp_activity.json', 'clientes.json', 'leads.json', 'facturas.json', 'gastos.json', 'proyectos.json', 'reuniones.json',
            'contratos.json', 'settings.json', 'roles.json', 'mis_notas.json', 'header_reminders.json', 'ai_memories.json', 'ai_action_logs.json',
            'timeline.json', 'users.json', 'mcp_pending_actions.json', 'email_templates.json', 'email_history.json', 'mcp_history.json',
            'whatsapp_chats.json', 'ai_scheduled_tasks.json'] as $file) {
            Storage::disk('local')->put($file, '[]');
        }
        Mail::fake();
        // Sábado 7 de noviembre de 2026, 10:00 en Colombia.
        $this->travelTo(CarbonImmutable::parse('2026-11-07 10:00', ScheduledTasks::TZ));
        $this->admin = User::factory()->create(['role' => 'admin', 'name' => 'Héctor', 'email' => 'hector@indigo.test']);
        (new FileStore('clientes.json'))->create(['id' => 'c1', 'empresa' => 'Somos Domus', 'contacto_nombre' => 'Laura Navarro', 'contacto_email' => 'laura@domus.test', 'estado' => 'Activo']);
        $this->settings();

        Http::fake(function ($request) {
            if (str_contains($request->url(), 'api.twilio.com')) {
                return Http::response(['sid' => 'SM1'], 201);
            }
            $next = array_shift($this->aiReplies);

            return Http::response(is_callable($next) ? $next($request) : $next);
        });
    }

    private function settings(array $extra = []): void
    {
        $store = new FileStore('settings.json');
        $data = array_merge([
            'id' => 'settings',
            'ai_enabled' => true,
            'ai_provider' => 'deepseek',
            'ai_model' => 'auto',
            'ai_api_keys' => ['deepseek' => 'ENC:' . Crypt::encryptString('test-key')],
            'ai_tools_enabled' => true,
            'whatsapp_enabled' => true,
            'whatsapp_twilio_sid' => 'A' . 'C' . str_repeat('0', 32),
            'whatsapp_twilio_token' => 'ENC:' . Crypt::encryptString('token'),
            'whatsapp_from' => '+14155238886',
            'whatsapp_owner' => '+573001112233',
            'whatsapp_user_id' => (string) $this->admin->id,
        ], $extra);
        $store->find('settings') ? $store->update('settings', $data) : $store->create($data);
    }

    private function openWindow(): void
    {
        (new FileStore('whatsapp_chats.json'))->create(['id' => 'owner', 'messages' => [], 'seen' => [], 'last_inbound_at' => now()->subHour()->toISOString()]);
    }

    private function program(array $args): string
    {
        $this->actingAs($this->admin);

        return (new AiToolBridge())->call('programar_tarea', $args);
    }

    private function twilio(): array
    {
        return Http::recorded()->map(fn ($p) => $p[0])->filter(fn ($r) => str_contains($r->url(), 'api.twilio.com'))->values()->all();
    }

    private function toolCall(string $name, array $arguments): array
    {
        return ['choices' => [['message' => ['role' => 'assistant', 'content' => '', 'tool_calls' => [[
            'id' => 'call_1', 'type' => 'function', 'function' => ['name' => $name, 'arguments' => json_encode($arguments)],
        ]]]]]];
    }

    public function test_next_run_handles_each_frequency(): void
    {
        $now = CarbonImmutable::parse('2026-11-07 10:00', ScheduledTasks::TZ);
        $next = fn (array $t) => ScheduledTasks::nextRun($t, $now)?->format('Y-m-d H:i');

        $this->assertSame('2026-11-13 15:00', $next(['frecuencia' => 'una_vez', 'fecha' => '2026-11-13', 'hora' => '15:00']));
        $this->assertNull($next(['frecuencia' => 'una_vez', 'fecha' => '2026-11-07', 'hora' => '09:00']));
        $this->assertSame('2026-11-07 18:00', $next(['frecuencia' => 'diaria', 'hora' => '18:00']));
        $this->assertSame('2026-11-08 08:00', $next(['frecuencia' => 'diaria', 'hora' => '08:00']));
        $this->assertSame('2026-11-09 08:00', $next(['frecuencia' => 'semanal', 'dias' => [1], 'hora' => '08:00']));
        $this->assertSame('2026-11-30 09:00', $next(['frecuencia' => 'mensual', 'dia_mes' => 31, 'hora' => '09:00']));
    }

    public function test_assistant_programs_a_weekly_check_from_chat(): void
    {
        $this->aiReplies = [
            $this->toolCall('programar_tarea', ['tipo' => 'consulta', 'texto' => 'Revisa quién me debe', 'frecuencia' => 'semanal', 'dias' => ['lunes'], 'hora' => '08:00']),
            ['choices' => [['message' => ['role' => 'assistant', 'content' => 'Listo: cada lunes a las 8 te reviso quién te debe.']]]],
        ];
        $this->actingAs($this->admin);

        $reply = (new AiService())->reply('Todos los lunes a las 8 revisa quién me debe y me dices');

        $this->assertSame(['programar_tarea'], $reply['tools_used']);
        $task = collect((new FileStore(ScheduledTasks::FILE))->all())->first();
        $this->assertSame('consulta', $task['tipo']);
        $this->assertSame([1], $task['dias']);
        $this->assertSame('2026-11-09 08:00', CarbonImmutable::parse($task['proxima'])->setTimezone(ScheduledTasks::TZ)->format('Y-m-d H:i'));
        $this->assertSame((string) $this->admin->id, $task['user_id']);
    }

    public function test_reminder_is_sent_by_whatsapp_at_its_time_only_once(): void
    {
        $this->openWindow();
        $this->assertStringStartsWith('Programado', $this->program(['tipo' => 'aviso', 'texto' => 'Llamar a Laura', 'frecuencia' => 'una_vez', 'fecha' => '2026-11-07', 'hora' => '15:00']));

        $this->artisan('asistente:run-scheduled')->assertSuccessful();
        $this->assertCount(0, $this->twilio(), 'Todavía no es la hora.');

        $this->travelTo(CarbonImmutable::parse('2026-11-07 15:01', ScheduledTasks::TZ));
        $this->artisan('asistente:run-scheduled')->assertSuccessful();
        $this->artisan('asistente:run-scheduled')->assertSuccessful();

        $sent = $this->twilio();
        $this->assertCount(1, $sent);
        $this->assertSame('Recordatorio: Llamar a Laura', $sent[0]['Body']);
        $task = collect((new FileStore(ScheduledTasks::FILE))->all())->first();
        $this->assertFalse($task['activa']);
        $this->assertSame('whatsapp', $task['ultimo_estado']);
        $chat = (new FileStore('whatsapp_chats.json'))->find('owner');
        $this->assertSame('Recordatorio: Llamar a Laura', end($chat['messages'])['content']);
    }

    public function test_outside_the_24_hour_window_uses_the_template(): void
    {
        $this->settings(['whatsapp_template_sid' => 'H' . 'X' . str_repeat('1', 32)]);
        $this->program(['tipo' => 'aviso', 'texto' => 'Pagar hosting de fiestasybanquetesluna.com', 'frecuencia' => 'una_vez', 'fecha' => '2026-11-07', 'hora' => '11:00']);

        $this->travelTo(CarbonImmutable::parse('2026-11-07 11:00', ScheduledTasks::TZ));
        $this->artisan('asistente:run-scheduled')->assertSuccessful();

        $sent = $this->twilio();
        $this->assertCount(1, $sent);
        $this->assertSame('H' . 'X' . str_repeat('1', 32), $sent[0]['ContentSid']);
        $this->assertSame(['1' => 'Recordatorio: Pagar hosting de fiestasybanquetesluna.com'], json_decode($sent[0]['ContentVariables'], true));
        $this->assertSame('whatsapp_plantilla', collect((new FileStore(ScheduledTasks::FILE))->all())->first()['ultimo_estado']);
    }

    public function test_without_template_or_window_it_falls_back_to_email(): void
    {
        $this->program(['tipo' => 'aviso', 'texto' => 'Revisar contrato', 'frecuencia' => 'una_vez', 'fecha' => '2026-11-07', 'hora' => '11:00']);

        $this->travelTo(CarbonImmutable::parse('2026-11-07 11:00', ScheduledTasks::TZ));
        $this->artisan('asistente:run-scheduled')->assertSuccessful();

        $this->assertCount(0, $this->twilio());
        Mail::assertSent(GenericMail::class, fn ($mail) => $mail->hasTo('hector@indigo.test'));
        $this->assertSame('correo', collect((new FileStore(ScheduledTasks::FILE))->all())->first()['ultimo_estado']);
    }

    public function test_scheduled_query_runs_with_read_only_tools_and_reschedules(): void
    {
        $this->program(['tipo' => 'consulta', 'texto' => 'Revisa el contacto de Somos Domus', 'frecuencia' => 'semanal', 'dias' => ['lunes'], 'hora' => '08:00']);
        $this->aiReplies = [
            $this->toolCall('buscar_clientes', ['texto' => 'domus']),
            ['choices' => [['message' => ['role' => 'assistant', 'content' => 'El contacto es Laura Navarro.']]]],
        ];

        $this->travelTo(CarbonImmutable::parse('2026-11-09 08:00', ScheduledTasks::TZ));
        $this->openWindow();
        $this->artisan('asistente:run-scheduled')->assertSuccessful();

        $aiRequest = Http::recorded()->map(fn ($p) => $p[0])->first(fn ($r) => str_contains($r->url(), 'deepseek'));
        $tools = collect($aiRequest['tools'])->pluck('function.name');
        $this->assertTrue($tools->contains('buscar_clientes'));
        foreach (['crear_recordatorio', 'programar_tarea', 'preparar_correo', 'confirmar_accion'] as $write) {
            $this->assertFalse($tools->contains($write), "{$write} no debe estar en una consulta programada.");
        }
        $this->assertStringContainsString('El contacto es Laura Navarro.', $this->twilio()[0]['Body']);

        $task = collect((new FileStore(ScheduledTasks::FILE))->all())->first();
        $this->assertTrue($task['activa']);
        $this->assertSame('2026-11-16 08:00', CarbonImmutable::parse($task['proxima'])->setTimezone(ScheduledTasks::TZ)->format('Y-m-d H:i'));
    }

    public function test_paused_tasks_do_not_run_and_tasks_are_per_user(): void
    {
        $this->openWindow();
        $this->program(['tipo' => 'aviso', 'texto' => 'Pausada', 'frecuencia' => 'diaria', 'hora' => '11:00']);
        $id = collect((new FileStore(ScheduledTasks::FILE))->all())->first()['id'];
        $this->assertStringStartsWith('Actualizado', (new AiToolBridge())->call('editar_tarea_programada', ['tarea_programada_id' => $id, 'activa' => false]));

        $this->travelTo(CarbonImmutable::parse('2026-11-07 11:00', ScheduledTasks::TZ));
        $this->artisan('asistente:run-scheduled')->assertSuccessful();
        $this->assertCount(0, $this->twilio());

        $other = User::factory()->create(['role' => 'admin']);
        $this->actingAs($other);
        $this->assertStringContainsString('No tienes tareas programadas', (new AiToolBridge())->call('ver_tareas_programadas', ['incluir_pausadas' => true]));
        $this->assertStringStartsWith('ERROR', (new AiToolBridge())->call('editar_tarea_programada', ['tarea_programada_id' => $id, 'activa' => true]));
    }

    public function test_programming_in_the_past_is_rejected(): void
    {
        $this->assertStringStartsWith('ERROR', $this->program(['tipo' => 'aviso', 'texto' => 'Tarde', 'frecuencia' => 'una_vez', 'fecha' => '2026-11-06', 'hora' => '09:00']));
        $this->assertSame([], (new FileStore(ScheduledTasks::FILE))->all());
    }
}
