<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\FileStore;
use App\Support\Ai\ScheduledTasks;
use App\Support\WhatsApp\WhatsAppChannel;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WhatsAppProvidersTest extends TestCase
{
    use RefreshDatabase;

    private const OWNER = '+573001112233';

    private const APP_SECRET = 'meta-app-secret';

    private const PHONE_ID = '123456789012345';

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    private User $admin;

    private array $aiReplies = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.disks.local.root' => storage_path('framework/testing/disks/local')]);
        foreach (['mcp_activity.json', 'clientes.json', 'leads.json', 'facturas.json', 'gastos.json', 'proyectos.json', 'reuniones.json',
            'contratos.json', 'settings.json', 'roles.json', 'mis_notas.json', 'header_reminders.json', 'ai_memories.json', 'ai_action_logs.json',
            'timeline.json', 'users.json', 'mcp_pending_actions.json', 'email_templates.json', 'email_history.json', 'mcp_history.json',
            'whatsapp_chats.json', 'ai_attachments.json', 'ai_scheduled_tasks.json'] as $file) {
            Storage::disk('local')->put($file, '[]');
        }
        Mail::fake();
        $this->admin = User::factory()->create(['role' => 'admin', 'name' => 'Héctor']);

        Http::fake(function ($request) {
            $url = $request->url();
            if (str_contains($url, 'graph.facebook.com') && str_ends_with(parse_url($url, PHP_URL_PATH), '/messages')) {
                return Http::response(['messages' => [['id' => 'wamid.out']]]);
            }
            if (str_contains($url, 'graph.facebook.com/v23.0/9988')) {
                return Http::response(['url' => 'https://lookaside.fbsbx.com/whatsapp_business/attachments/?mid=9988', 'mime_type' => 'image/png']);
            }
            if (str_contains($url, 'lookaside.fbsbx.com')) {
                return Http::response(base64_decode(self::PNG), 200, ['Content-Type' => 'image/png']);
            }
            if (str_contains($url, 'api.textmebot.com')) {
                return Http::response(['status' => 'ok']);
            }
            $next = array_shift($this->aiReplies);

            return Http::response(is_callable($next) ? $next($request) : $next);
        });
    }

    private function settings(string $provider, array $extra = []): void
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
            'whatsapp_provider' => $provider,
            'whatsapp_owner' => self::OWNER,
            'whatsapp_user_id' => (string) $this->admin->id,
            'whatsapp_meta_phone_id' => self::PHONE_ID,
            'whatsapp_meta_token' => 'ENC:' . Crypt::encryptString('meta-token'),
            'whatsapp_meta_app_secret' => 'ENC:' . Crypt::encryptString(self::APP_SECRET),
            'whatsapp_meta_verify_token' => 'verificame',
            'whatsapp_meta_version' => 'v23.0',
            'whatsapp_textmebot_apikey' => 'ENC:' . Crypt::encryptString('tmb-key'),
            'whatsapp_textmebot_webhook_key' => 'clave-secreta-de-prueba',
        ], $extra);
        $store->find('settings') ? $store->update('settings', $data) : $store->create($data);
    }

    private function text(string $content): array
    {
        return ['choices' => [['message' => ['role' => 'assistant', 'content' => $content]]]];
    }

    private function metaPost(array $message, ?string $signature = null, string $phoneId = self::PHONE_ID)
    {
        $payload = json_encode(['object' => 'whatsapp_business_account', 'entry' => [['id' => 'WABA', 'changes' => [['field' => 'messages', 'value' => [
            'messaging_product' => 'whatsapp',
            'metadata' => ['display_phone_number' => '15550001111', 'phone_number_id' => $phoneId],
            'messages' => [$message],
        ]]]]]]);
        $signature ??= 'sha256=' . hash_hmac('sha256', $payload, self::APP_SECRET);

        return $this->call('POST', '/webhooks/whatsapp/meta', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_HUB_SIGNATURE_256' => $signature], $payload);
    }

    private function sent(string $needle): array
    {
        return Http::recorded()->map(fn ($p) => $p[0])->filter(fn ($r) => str_contains($r->url(), $needle))->values()->all();
    }

    public function test_meta_webhook_verification(): void
    {
        $this->settings('meta');

        $this->get('/webhooks/whatsapp/meta?hub.mode=subscribe&hub.verify_token=verificame&hub.challenge=12345')->assertOk()->assertSee('12345');
        $this->get('/webhooks/whatsapp/meta?hub.mode=subscribe&hub.verify_token=otro&hub.challenge=12345')->assertForbidden();
    }

    public function test_meta_text_from_owner_is_answered_through_the_graph_api(): void
    {
        $this->settings('meta');
        $this->aiReplies = [$this->text('Hola, **Héctor**.')];

        $this->metaPost(['from' => '573001112233', 'id' => 'wamid.1', 'type' => 'text', 'text' => ['body' => 'Hola']])->assertOk();

        $sent = $this->sent('/messages');
        $this->assertCount(1, $sent);
        $this->assertStringEndsWith('/v23.0/' . self::PHONE_ID . '/messages', $sent[0]->url());
        $this->assertSame('573001112233', $sent[0]['to']);
        $this->assertSame('Hola, *Héctor*.', $sent[0]['text']['body']);
        $this->assertTrue($sent[0]->hasHeader('Authorization', 'Bearer meta-token'));
    }

    public function test_meta_rejects_bad_signatures_other_numbers_and_duplicates(): void
    {
        $this->settings('meta');
        $this->aiReplies = [$this->text('Uno'), $this->text('Dos')];

        $this->metaPost(['from' => '573001112233', 'id' => 'wamid.x', 'type' => 'text', 'text' => ['body' => 'Hola']], 'sha256=falsa')->assertForbidden();
        $this->metaPost(['from' => '573009998877', 'id' => 'wamid.y', 'type' => 'text', 'text' => ['body' => 'Hola']])->assertOk();
        $this->metaPost(['from' => '573001112233', 'id' => 'wamid.z', 'type' => 'text', 'text' => ['body' => 'Hola']], null, '999')->assertOk();
        $this->metaPost(['from' => '573001112233', 'id' => 'wamid.dup', 'type' => 'text', 'text' => ['body' => 'Hola']])->assertOk();
        $this->metaPost(['from' => '573001112233', 'id' => 'wamid.dup', 'type' => 'text', 'text' => ['body' => 'Hola']])->assertOk();

        $this->assertCount(1, $this->sent('/messages'));
    }

    public function test_meta_photo_is_downloaded_and_shown_to_the_model(): void
    {
        $this->settings('meta');
        $this->aiReplies = [function ($request) {
            $user = collect($request['messages'])->where('role', 'user')->last();
            $this->assertSame('image_url', collect($user['content'])->pluck('type')->last());

            return $this->text('Es un recibo.');
        }];

        $this->metaPost(['from' => '573001112233', 'id' => 'wamid.img', 'type' => 'image', 'image' => ['id' => '9988', 'mime_type' => 'image/png', 'caption' => '¿Qué es esto?']])->assertOk();

        $this->assertCount(1, (new FileStore('ai_attachments.json'))->all());
        $this->assertSame('Es un recibo.', $this->sent('/messages')[0]['text']['body']);
    }

    public function test_meta_scheduled_notice_uses_its_template_outside_the_window(): void
    {
        $this->settings('meta', ['whatsapp_meta_template_name' => 'aviso_infocus', 'whatsapp_meta_template_lang' => 'es']);
        $this->travelTo(CarbonImmutable::parse('2026-11-07 10:00', ScheduledTasks::TZ));
        ScheduledTasks::store()->create(['id' => 't1', 'user_id' => (string) $this->admin->id, 'tipo' => 'aviso', 'texto' => 'Llamar a Laura',
            'frecuencia' => 'una_vez', 'fecha' => '2026-11-07', 'hora' => '10:00', 'canal' => 'whatsapp', 'activa' => true, 'proxima' => now()->toIso8601String()]);

        $this->artisan('asistente:run-scheduled')->assertSuccessful();

        $sent = $this->sent('/messages')[0];
        $this->assertSame('template', $sent['type']);
        $this->assertSame('aviso_infocus', $sent['template']['name']);
        $this->assertSame('Recordatorio: Llamar a Laura', $sent['template']['components'][0]['parameters'][0]['text']);
    }

    public function test_textmebot_needs_the_secret_key_and_answers_by_its_api(): void
    {
        $this->settings('textmebot');
        $this->aiReplies = [$this->text('Listo.')];

        $this->postJson('/webhooks/whatsapp/textmebot/clave-falsa', ['type' => 'text', 'from' => '573001112233', 'message' => 'Hola'])->assertForbidden();
        $this->postJson('/webhooks/whatsapp/textmebot/clave-secreta-de-prueba', ['type' => 'text', 'from' => '573009998877', 'message' => 'Hola'])->assertOk();
        $this->postJson('/webhooks/whatsapp/textmebot/clave-secreta-de-prueba', ['type' => 'text', 'from' => '573001112233', 'from_name' => 'Héctor', 'to' => '57300', 'file' => 'null', 'message' => 'Hola'])->assertOk();
        $this->postJson('/webhooks/whatsapp/textmebot/clave-secreta-de-prueba', ['type' => 'text', 'from' => '573001112233', 'file' => 'null', 'message' => 'Hola'])->assertOk();

        $sent = $this->sent('api.textmebot.com');
        $this->assertCount(1, $sent, 'Solo el dueño, una vez.');
        parse_str((string) parse_url($sent[0]->url(), PHP_URL_QUERY), $query);
        $this->assertSame('573001112233', $query['recipient']);
        $this->assertSame('tmb-key', $query['apikey']);
        $this->assertSame('Listo.', $query['text']);
    }

    public function test_textmebot_does_not_download_from_private_addresses(): void
    {
        $this->assertFalse(\App\Support\WhatsApp\TextMeBotWhatsApp::publicHost('127.0.0.1'));
        $this->assertFalse(\App\Support\WhatsApp\TextMeBotWhatsApp::publicHost('10.0.0.5'));
        $this->assertTrue(\App\Support\WhatsApp\TextMeBotWhatsApp::publicHost('8.8.8.8'));
    }

    public function test_settings_switch_provider_generate_keys_and_encrypt_secrets(): void
    {
        $this->settings('twilio', ['whatsapp_meta_verify_token' => '', 'whatsapp_textmebot_webhook_key' => '']);

        $this->actingAs($this->admin)->put(route('settings.ai.whatsapp.update'), [
            'whatsapp_enabled' => '1',
            'whatsapp_provider' => 'meta',
            'whatsapp_owner' => '+57 300 111 2233',
            'whatsapp_user_id' => (string) $this->admin->id,
            'whatsapp_meta_phone_id' => self::PHONE_ID,
            'whatsapp_meta_token' => 'token-nuevo',
            'whatsapp_meta_app_secret' => 'secreto-nuevo',
        ])->assertRedirect(route('settings.ai', ['tab' => 'whatsapp']));

        $saved = (new FileStore('settings.json'))->find('settings');
        $raw = Storage::disk('local')->get('settings.json');
        $this->assertSame('meta', $saved['whatsapp_provider']);
        $this->assertSame(32, strlen($saved['whatsapp_meta_verify_token']));
        $this->assertSame(40, strlen($saved['whatsapp_textmebot_webhook_key']));
        $this->assertStringNotContainsString('token-nuevo', $raw);
        $this->assertStringNotContainsString('secreto-nuevo', $raw);
        $this->assertSame('meta', WhatsAppChannel::current()->provider());

        $this->actingAs($this->admin)->get(route('settings.ai', ['tab' => 'whatsapp']))
            ->assertOk()
            ->assertSee(url('/webhooks/whatsapp/meta'))
            ->assertSee($saved['whatsapp_meta_verify_token'])
            ->assertSee(url('/webhooks/whatsapp/textmebot/' . $saved['whatsapp_textmebot_webhook_key']))
            ->assertDontSee('token-nuevo');

        $this->actingAs($this->admin)->post(route('settings.ai.whatsapp.test'))->assertSessionHas('success');
        $this->assertSame('573001112233', $this->sent('/messages')[0]['to']);
    }
}
