<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\FileStore;
use App\Support\Ai\AiToolBridge;
use App\Support\WhatsApp\TwilioWhatsApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WhatsAppChannelTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'twilio-test-token';

    private const OWNER = '+573001112233';

    private User $admin;

    /** SID ficticio armado al vuelo: un literal con forma de SID real lo bloquea el escáner de secretos de GitHub. */
    private function sid(): string
    {
        return 'A' . 'C' . str_repeat('0', 32);
    }

    private array $aiReplies = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.disks.local.root' => storage_path('framework/testing/disks/local')]);
        foreach (['mcp_activity.json', 'clientes.json', 'leads.json', 'facturas.json', 'gastos.json', 'proyectos.json', 'reuniones.json',
            'contratos.json', 'settings.json', 'roles.json', 'mis_notas.json', 'header_reminders.json', 'ai_memories.json', 'ai_action_logs.json',
            'timeline.json', 'users.json', 'mcp_pending_actions.json', 'email_templates.json', 'email_history.json', 'mcp_history.json',
            'whatsapp_chats.json'] as $file) {
            Storage::disk('local')->put($file, '[]');
        }
        Mail::fake();
        $this->admin = User::factory()->create(['role' => 'admin', 'name' => 'Héctor']);
        (new FileStore('clientes.json'))->create(['id' => 'c1', 'empresa' => 'Somos Domus', 'contacto_nombre' => 'Laura Navarro', 'contacto_email' => 'laura@domus.test', 'estado' => 'Activo']);
        (new FileStore('settings.json'))->create([
            'id' => 'settings',
            'ai_enabled' => true,
            'ai_provider' => 'deepseek',
            'ai_model' => 'auto',
            'ai_api_keys' => ['deepseek' => 'ENC:' . Crypt::encryptString('test-key')],
            'ai_tools_enabled' => true,
            'whatsapp_enabled' => true,
            'whatsapp_twilio_sid' => $this->sid(),
            'whatsapp_twilio_token' => 'ENC:' . Crypt::encryptString(self::TOKEN),
            'whatsapp_from' => '+14155238886',
            'whatsapp_owner' => self::OWNER,
            'whatsapp_user_id' => (string) $this->admin->id,
        ]);

        Http::fake(function ($request) {
            if (str_contains($request->url(), 'api.twilio.com')) {
                return Http::response(['sid' => 'SM1'], 201);
            }
            $next = array_shift($this->aiReplies);

            return Http::response(is_callable($next) ? $next($request) : $next);
        });
    }

    private function incoming(string $body, string $sid, string $from = self::OWNER, ?string $signature = null)
    {
        $params = ['From' => 'whatsapp:' . $from, 'To' => 'whatsapp:+14155238886', 'Body' => $body, 'MessageSid' => $sid, 'NumMedia' => '0'];
        $url = url('/webhooks/whatsapp');
        if ($signature === null) {
            ksort($params);
            $data = $url;
            foreach ($params as $k => $v) {
                $data .= $k . $v;
            }
            $signature = base64_encode(hash_hmac('sha1', $data, self::TOKEN, true));
        }

        return $this->withHeader('X-Twilio-Signature', $signature)->post('/webhooks/whatsapp', $params);
    }

    private function text(string $content): array
    {
        return ['choices' => [['message' => ['role' => 'assistant', 'content' => $content]]]];
    }

    private function toolCall(string $name, array $arguments): array
    {
        return ['choices' => [['message' => ['role' => 'assistant', 'content' => '', 'tool_calls' => [[
            'id' => 'call_1', 'type' => 'function', 'function' => ['name' => $name, 'arguments' => json_encode($arguments)],
        ]]]]]];
    }

    /** @return array<int, \Illuminate\Http\Client\Request> */
    private function twilioMessages(): array
    {
        return Http::recorded()->map(fn ($pair) => $pair[0])->filter(fn ($r) => str_contains($r->url(), 'api.twilio.com'))->values()->all();
    }

    public function test_rejects_requests_without_a_valid_twilio_signature(): void
    {
        $this->incoming('Hola', 'SM_bad', self::OWNER, 'firma-falsa')->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_ignores_messages_from_other_numbers(): void
    {
        $this->incoming('Dame los datos de los clientes', 'SM_other', '+573009998877')->assertOk();

        Http::assertNothingSent();
    }

    public function test_owner_gets_the_assistant_answer_on_whatsapp(): void
    {
        $this->aiReplies = [
            $this->toolCall('buscar_clientes', ['texto' => 'domus']),
            $this->text('El contacto de **Somos Domus** es Laura Navarro.'),
        ];

        $this->incoming('¿Quién es el contacto de Somos Domus?', 'SM_1')->assertOk()->assertHeader('Content-Type', 'text/xml; charset=utf-8');

        $sent = $this->twilioMessages();
        $this->assertCount(1, $sent);
        $this->assertSame('whatsapp:' . self::OWNER, $sent[0]['To']);
        $this->assertSame('whatsapp:+14155238886', $sent[0]['From']);
        $this->assertSame('El contacto de *Somos Domus* es Laura Navarro.', $sent[0]['Body']);
        $this->assertTrue($sent[0]->hasHeader('Authorization', 'Basic ' . base64_encode($this->sid() . ':' . self::TOKEN)));

        $chat = (new FileStore('whatsapp_chats.json'))->find('owner');
        $this->assertCount(2, $chat['messages']);
        $this->assertSame(['buscar_clientes'], $chat['messages'][1]['tools_used']);
        $log = collect((new FileStore('mcp_activity.json'))->all())->last();
        $this->assertSame((string) $this->admin->id, $log['user_id']);
    }

    public function test_twilio_retries_are_answered_only_once(): void
    {
        $this->aiReplies = [$this->text('Hola'), $this->text('Hola otra vez')];

        $this->incoming('Hola', 'SM_dup')->assertOk();
        $this->incoming('Hola', 'SM_dup')->assertOk();

        $this->assertCount(1, $this->twilioMessages());
    }

    public function test_email_from_whatsapp_needs_the_preview_and_a_yes(): void
    {
        $this->aiReplies = [
            $this->toolCall('preparar_correo', ['cliente_id' => 'c1', 'asunto' => 'Seguimiento', 'mensaje' => '<p>Hola {cliente}.</p>']),
            $this->text('Te preparé el correo para Laura. ¿Lo envío?'),
        ];
        $this->incoming('Mándale un correo de seguimiento a Somos Domus', 'SM_a')->assertOk();

        $preview = $this->twilioMessages()[0]['Body'];
        $this->assertStringContainsString('Asunto: Seguimiento', $preview);
        $this->assertStringContainsString('Código de confirmación', $preview);
        Mail::assertNothingSent();

        $code = AiToolBridge::codesIn($preview)[0];
        $this->aiReplies = [$this->toolCall('confirmar_accion', ['codigo' => $code]), $this->text('Enviado.')];
        $this->incoming('Sí, envíalo', 'SM_b')->assertOk();

        Mail::assertSentCount(1);
        $this->assertSame('Enviado.', $this->twilioMessages()[1]['Body']);
    }

    public function test_settings_encrypt_the_token_normalize_numbers_and_send_a_test(): void
    {
        $this->actingAs($this->admin)->put(route('settings.ai.whatsapp.update'), [
            'whatsapp_enabled' => '1',
            'whatsapp_provider' => 'twilio',
            'whatsapp_twilio_sid' => $this->sid(),
            'whatsapp_twilio_token' => 'nuevo-token',
            'whatsapp_from' => 'whatsapp:+1 415 523 8886',
            'whatsapp_owner' => '300 111 2233',
            'whatsapp_user_id' => (string) $this->admin->id,
        ])->assertRedirect(route('settings.ai', ['tab' => 'whatsapp']));

        $saved = (new FileStore('settings.json'))->find('settings');
        $this->assertStringStartsWith('ENC:', $saved['whatsapp_twilio_token']);
        $this->assertStringNotContainsString('nuevo-token', Storage::disk('local')->get('settings.json'));
        $this->assertSame('+14155238886', $saved['whatsapp_from']);
        $this->assertSame('+3001112233', $saved['whatsapp_owner']);

        $this->actingAs($this->admin)->post(route('settings.ai.whatsapp.test'))->assertSessionHas('success');
        $this->assertSame('whatsapp:+3001112233', $this->twilioMessages()[0]['To']);

        $this->actingAs($this->admin)->get(route('settings.ai', ['tab' => 'whatsapp']))
            ->assertOk()->assertSee(url('/webhooks/whatsapp'))->assertSee('Guardar WhatsApp')->assertDontSee('nuevo-token');

        $member = User::factory()->create(['role' => 'ventas']);
        $this->actingAs($this->admin)->put(route('settings.ai.whatsapp.update'), [
            'whatsapp_provider' => 'twilio', 'whatsapp_twilio_sid' => $this->sid(), 'whatsapp_user_id' => (string) $member->id,
        ])->assertSessionHasErrors('whatsapp_user_id');
    }

    public function test_formats_markdown_and_splits_long_messages(): void
    {
        config(['app.url' => 'https://crm.test']);
        $this->assertSame("*Título*\n\n• uno\n• Ver factura (https://crm.test/facturas/1)",
            TwilioWhatsApp::toWhatsAppText("## Título\n\n- uno\n- [Ver factura](/facturas/1)"));

        $chunks = TwilioWhatsApp::chunks(str_repeat("Párrafo de prueba.\n\n", 200));
        $this->assertGreaterThan(1, count($chunks));
        foreach ($chunks as $chunk) {
            $this->assertLessThanOrEqual(1500, mb_strlen($chunk));
        }
    }
}
