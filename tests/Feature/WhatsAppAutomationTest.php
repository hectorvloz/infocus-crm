<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\FileStore;
use App\Support\Ai\AiService;
use App\Support\Ai\AiToolBridge;
use App\Support\Ai\AiUsage;
use App\Support\WhatsApp\ClientInvoiceWhatsApp;
use App\Support\WhatsApp\WhatsAppAlerts;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WhatsAppAutomationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private array $aiReplies = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.disks.local.root' => storage_path('framework/testing/disks/local'), 'services.leads_webhook.token' => 'secreto']);
        \Illuminate\Support\Sleep::fake();
        Mail::fake();
        foreach (['mcp_activity.json', 'clientes.json', 'leads.json', 'facturas.json', 'gastos.json', 'proyectos.json', 'reuniones.json', 'meeting_reminders.json',
            'contratos.json', 'settings.json', 'roles.json', 'mis_notas.json', 'header_reminders.json', 'ai_memories.json', 'ai_action_logs.json',
            'timeline.json', 'users.json', 'whatsapp_chats.json', 'whatsapp_events.json', 'whatsapp_textmebot_rate.json', 'whatsapp_alerts_state.json',
            'mcp_pending_actions.json', 'ai_usage.json', 'ai_scheduled_tasks.json', 'email_history.json'] as $file) {
            Storage::disk('local')->put($file, '[]');
        }
        $this->admin = User::factory()->create(['role' => 'admin', 'name' => 'Héctor']);
        $this->settings();
        (new FileStore('clientes.json'))->create(['id' => 'c1', 'empresa' => 'Somos Domus', 'contacto_nombre' => 'Laura Navarro', 'contacto_email' => 'laura@domus.test', 'contacto_telefono' => '310 123 4567']);

        Http::fake(function ($request) {
            if (str_contains($request->url(), 'api.textmebot.com')) {
                return Http::response(['status' => 'ok']);
            }
            $next = array_shift($this->aiReplies);

            return Http::response(is_callable($next) ? $next($request) : ($next ?? ['choices' => [['message' => ['content' => '{"remember":false,"memory":""}']]]]));
        });
    }

    private function settings(array $extra = []): void
    {
        $store = new FileStore('settings.json');
        $data = array_merge([
            'id' => 'settings', 'ai_enabled' => true, 'ai_provider' => 'deepseek', 'ai_model' => 'auto', 'ai_tools_enabled' => true,
            'ai_api_keys' => ['deepseek' => 'ENC:' . Crypt::encryptString('test-key')],
            'whatsapp_enabled' => true, 'whatsapp_provider' => 'textmebot', 'whatsapp_owner' => '+573001112233', 'whatsapp_user_id' => (string) $this->admin->id,
            'whatsapp_textmebot_apikey' => 'ENC:' . Crypt::encryptString('tmb-key'), 'whatsapp_textmebot_webhook_key' => 'clave',
        ], $extra);
        $store->find('settings') ? $store->update('settings', $data) : $store->create($data);
    }

    /** @return array<int, array<string, string>> query de cada envío por TextMeBot */
    private function whatsapps(): array
    {
        return Http::recorded()->map(fn ($p) => $p[0])->filter(fn ($r) => str_contains($r->url(), 'api.textmebot.com'))
            ->map(function ($r) {
                parse_str((string) parse_url($r->url(), PHP_URL_QUERY), $q);

                return $q;
            })->values()->all();
    }

    private function toolCall(string $name, array $arguments): array
    {
        return ['choices' => [['message' => ['role' => 'assistant', 'content' => '', 'tool_calls' => [[
            'id' => 'call_1', 'type' => 'function', 'function' => ['name' => $name, 'arguments' => json_encode($arguments)],
        ]]]]]];
    }

    public function test_new_lead_from_the_website_alerts_my_whatsapp(): void
    {
        $this->withToken('secreto')->postJson('/webhooks/leads', ['nombre' => 'Pedro Ruiz', 'email' => 'pedro@ruiz.test', 'telefono' => '3001234567'])->assertCreated();

        $sent = $this->whatsapps();
        $this->assertCount(1, $sent);
        $this->assertSame('+573001112233', $sent[0]['recipient']);
        $this->assertStringStartsWith('Lead nuevo: Pedro Ruiz (pedro@ruiz.test, 3001234567)', $sent[0]['text']);
    }

    public function test_alerts_respect_their_switches(): void
    {
        $this->settings(['whatsapp_alerts' => ['lead' => false, 'pago' => true]]);

        WhatsAppAlerts::deliver('lead', 'no debería salir');
        WhatsAppAlerts::deliver('pago', 'Pago recibido: prueba');

        $this->assertSame(['Pago recibido: prueba'], array_column($this->whatsapps(), 'text'));
    }

    public function test_meeting_in_30_minutes_alerts_once(): void
    {
        $start = now('America/Bogota')->addMinutes(20);
        (new FileStore('reuniones.json'))->create(['id' => 'r1', 'titulo' => 'Revisión Somos Domus', 'fecha' => $start->toDateString(), 'hora_inicio' => $start->format('H:i'), 'hora_fin' => $start->copy()->addHour()->format('H:i')]);

        $this->artisan('reuniones:send-reminders')->assertSuccessful();
        $this->artisan('reuniones:send-reminders')->assertSuccessful();

        $sent = $this->whatsapps();
        $this->assertCount(1, $sent);
        $this->assertStringContainsString('Reunión en 30 minutos: "Revisión Somos Domus"', $sent[0]['text']);
    }

    public function test_daily_invoice_alert_once_after_8(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-09 07:30', 'America/Bogota'));
        (new FileStore('facturas.json'))->create(['id' => 'f1', 'numero' => 'INV-0070', 'cliente' => 'Somos Domus', 'moneda' => 'COP', 'total' => 500000, 'estado' => 'Pendiente', 'vencimiento' => '2026-11-09']);
        (new FileStore('facturas.json'))->create(['id' => 'f2', 'numero' => 'INV-0050', 'cliente' => 'MundoInnova', 'moneda' => 'USD', 'total' => 149, 'estado' => 'Vencida', 'vencimiento' => '2026-03-18']);
        (new FileStore('facturas.json'))->create(['id' => 'f3', 'numero' => 'INV-0040', 'cliente' => 'Pagada SA', 'moneda' => 'COP', 'total' => 1, 'estado' => 'Pagada', 'vencimiento' => '2026-01-01']);

        $this->artisan('asistente:run-scheduled')->assertSuccessful();
        $this->assertCount(0, $this->whatsapps(), 'Antes de las 8 no avisa.');

        $this->travelTo(CarbonImmutable::parse('2026-11-09 08:05', 'America/Bogota'));
        $this->artisan('asistente:run-scheduled')->assertSuccessful();
        $this->artisan('asistente:run-scheduled')->assertSuccessful();

        $sent = $this->whatsapps();
        $this->assertCount(1, $sent);
        $this->assertStringStartsWith('Facturas por cobrar (2):', $sent[0]['text']);
        $this->assertStringContainsString('INV-0050 · MundoInnova · USD 149 · vencida desde 2026-03-18', $sent[0]['text']);
        $this->assertStringContainsString('INV-0070 · Somos Domus · COP 500.000 · vence hoy', $sent[0]['text']);
    }

    public function test_client_invoice_notice_automatic(): void
    {
        ClientInvoiceWhatsApp::handle(['numero' => 'INV-0068', 'cliente_id' => 'c1', 'moneda' => 'COP', 'total' => 1045000], 'laura@domus.test', 'auto');

        $sent = $this->whatsapps();
        $this->assertSame('+573101234567', $sent[0]['recipient']);
        $this->assertSame('Hola Laura, te acabamos de enviar a laura@domus.test la factura INV-0068 por COP 1.045.000. Cualquier duda nos escribes por aquí. Indigo. Estudio Creativo', $sent[0]['text']);
    }

    public function test_client_invoice_notice_asks_me_first_and_sends_after_my_yes(): void
    {
        ClientInvoiceWhatsApp::handle(['numero' => 'INV-0068', 'cliente_id' => 'c1', 'moneda' => 'COP', 'total' => 1045000], 'laura@domus.test', 'ask');

        $proposal = $this->whatsapps()[0];
        $this->assertSame('+573001112233', $proposal['recipient'], 'Primero me pregunta a mí.');
        $this->assertStringContainsString('¿Le aviso también por WhatsApp?', $proposal['text']);
        $code = AiToolBridge::codesIn($proposal['text'])[0];

        $this->travel(3)->hours();
        $this->aiReplies = [$this->toolCall('confirmar_accion', ['codigo' => $code]), ['choices' => [['message' => ['role' => 'assistant', 'content' => 'Listo, le avisé a Laura.']]]]];
        $this->postJson('/webhooks/whatsapp/textmebot/clave', ['type' => 'text', 'from' => '573001112233', 'message' => 'sí', 'file' => 'null'])->assertOk();

        $recipients = array_column($this->whatsapps(), 'recipient');
        $this->assertContains('+573101234567', $recipients, 'Con el sí, le escribe al cliente.');
    }

    public function test_client_invoice_mode_is_off_by_default(): void
    {
        $this->assertSame('off', ClientInvoiceWhatsApp::mode());
        $this->settings(['whatsapp_client_invoice' => 'ask', 'whatsapp_enabled' => false]);
        $this->assertSame('off', ClientInvoiceWhatsApp::mode());
    }

    public function test_prepare_client_whatsapp_tool(): void
    {
        $this->actingAs($this->admin);
        (new FileStore('clientes.json'))->create(['id' => 'c2', 'empresa' => 'Sin Teléfono']);

        $preview = (new AiToolBridge())->call('preparar_whatsapp_cliente', ['cliente_id' => 'c1', 'mensaje' => 'Hola Laura, ¿pudiste revisar la propuesta?']);
        $this->assertStringContainsString('WhatsApp para: Laura Navarro (+573101234567)', $preview);
        $this->assertCount(0, $this->whatsapps(), 'Preparar no envía.');
        $this->assertStringContainsString('no tiene un teléfono válido', (new AiToolBridge())->call('preparar_whatsapp_cliente', ['cliente_id' => 'c2', 'mensaje' => 'Hola']));
    }

    public function test_timer_from_chat_starts_and_saves_time(): void
    {
        $this->actingAs($this->admin);
        (new FileStore('proyectos.json'))->create(['id' => 'p1', 'titulo' => 'Somos Domus', 'cliente' => 'Somos Domus', 'tareas' => [], 'time_logs' => []]);
        $bridge = new AiToolBridge();

        $this->assertStringStartsWith('Temporizador iniciado. Corriendo: Somos Domus', $bridge->call('temporizador', ['accion' => 'iniciar', 'proyecto_id' => 'p1']));
        $this->travel(90)->minutes();
        $this->assertStringContainsString('1 h 30 min', $bridge->call('temporizador', ['accion' => 'estado']));
        $this->assertStringStartsWith('Tiempo guardado: 1 h 30 min en Somos Domus', $bridge->call('temporizador', ['accion' => 'terminar']));
        $this->assertSame('No tienes ningún temporizador corriendo ni en pausa.', $bridge->call('temporizador', ['accion' => 'estado']));
    }

    public function test_daily_ai_cap_stops_the_assistant(): void
    {
        $this->settings(['ai_daily_limit' => 20]);
        (new FileStore('ai_usage.json'))->create(['id' => now('America/Bogota')->toDateString(), 'modelo' => 20]);
        $this->actingAs($this->admin);

        $reply = (new AiService())->reply('¿Quién me debe?');

        $this->assertStringContainsString('tope diario de 20 consultas', $reply['content']);
        Http::assertNothingSent();
    }

    public function test_usage_is_counted_per_call(): void
    {
        $this->actingAs($this->admin);
        $this->aiReplies = [['choices' => [['message' => ['role' => 'assistant', 'content' => 'Hola']]]]];

        (new AiService())->reply('Hola');

        $this->assertSame(1, AiUsage::today()['modelo']);
    }

    public function test_sending_an_invoice_email_triggers_the_client_notice(): void
    {
        $this->settings(['whatsapp_client_invoice' => 'auto']);
        (new FileStore('facturas.json'))->create([
            'id' => 'f1', 'numero' => 'INV-0068', 'cliente_id' => 'c1', 'cliente' => 'Somos Domus', 'fecha' => '2026-11-04', 'moneda' => 'COP', 'estado' => 'Pendiente',
            'items' => [['descripcion' => 'Diseño web', 'cantidad' => 1, 'precio' => 1045000, 'total' => 1045000]], 'subtotal' => 1045000, 'impuesto' => 0, 'total' => 1045000, 'pagos' => [],
        ]);

        $this->actingAs($this->admin)->withSession(['user' => ['id' => $this->admin->id]])
            ->postJson(route('api.facturas.enviar'), ['id' => 'f1', 'to' => 'laura@domus.test'])->assertOk()->assertJson(['ok' => true]);

        $this->assertSame('+573101234567', $this->whatsapps()[0]['recipient']);
    }
}
