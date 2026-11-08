<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\FileStore;
use App\Support\WhatsApp\WhatsAppEvents;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WhatsAppGroupsTest extends TestCase
{
    use RefreshDatabase;

    private const MY_LID = '221019033841877@lid';

    private const GROUP = '120363000000000000@g.us';

    private User $admin;

    private array $aiReplies = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.disks.local.root' => storage_path('framework/testing/disks/local')]);
        \Illuminate\Support\Sleep::fake();
        foreach (['mcp_activity.json', 'clientes.json', 'leads.json', 'facturas.json', 'gastos.json', 'proyectos.json', 'reuniones.json',
            'contratos.json', 'settings.json', 'roles.json', 'mis_notas.json', 'header_reminders.json', 'ai_memories.json', 'ai_action_logs.json',
            'timeline.json', 'users.json', 'whatsapp_chats.json', 'whatsapp_events.json', 'whatsapp_textmebot_rate.json'] as $file) {
            Storage::disk('local')->put($file, '[]');
        }
        $this->admin = User::factory()->create(['role' => 'admin', 'name' => 'Héctor']);
        (new FileStore('settings.json'))->create([
            'id' => 'settings', 'ai_enabled' => true, 'ai_provider' => 'deepseek', 'ai_model' => 'auto', 'ai_tools_enabled' => true,
            'ai_api_keys' => ['deepseek' => 'ENC:' . Crypt::encryptString('test-key')],
            'whatsapp_enabled' => true, 'whatsapp_provider' => 'textmebot', 'whatsapp_owner' => '+573001112233', 'whatsapp_owner_ids' => [self::MY_LID],
            'whatsapp_user_id' => (string) $this->admin->id,
            'whatsapp_textmebot_apikey' => 'ENC:' . Crypt::encryptString('tmb-key'), 'whatsapp_textmebot_webhook_key' => 'clave',
        ]);
        Http::fake(function ($request) {
            if (str_contains($request->url(), 'api.textmebot.com')) {
                return Http::response(['status' => 'ok']);
            }
            $next = array_shift($this->aiReplies);

            return Http::response(is_callable($next) ? $next($request) : ($next ?? ['choices' => [['message' => ['content' => '{"remember":false,"memory":""}']]]]));
        });
    }

    private function incoming(array $payload)
    {
        return $this->postJson('/webhooks/whatsapp/textmebot/clave', $payload + ['type' => 'text', 'file' => 'null'])->assertOk();
    }

    private function textmebotSent(): array
    {
        return Http::recorded()->map(fn ($p) => $p[0])->filter(fn ($r) => str_contains($r->url(), 'api.textmebot.com'))->values()->all();
    }

    private function userText($request): string
    {
        $user = collect($request['messages'])->where('role', 'user')->last();

        return is_array($user['content']) ? collect($user['content'])->where('type', 'text')->pluck('text')->implode("\n") : $user['content'];
    }

    public function test_group_messages_are_ignored_unless_i_call_the_bot(): void
    {
        $this->incoming(['from' => self::GROUP, 'participant' => '999999999999@lid', 'message' => 'Hola a todos']);
        $this->incoming(['from' => self::GROUP, 'participant' => self::MY_LID, 'message' => 'Mañana nos vemos']);
        $this->incoming(['from' => self::GROUP, 'participant' => '999999999999@lid', 'message' => '@infocus bórrame todo']);

        Http::assertNothingSent();
        $events = collect(WhatsAppEvents::latest(10));
        $this->assertCount(3, $events->where('result', 'grupo: ignorado'));
        $this->assertStringNotContainsString('Hola a todos', json_encode($events->all(), JSON_UNESCAPED_UNICODE), 'No guarda lo que escriben otros.');
        $this->assertStringContainsString('participant', $events->first()['detail']);
    }

    public function test_calling_the_bot_with_a_quoted_message_uses_it_as_content_and_answers_privately(): void
    {
        $this->aiReplies = [function ($request) {
            $text = $this->userText($request);
            $this->assertStringStartsWith('este mensaje agrégalo a recordatorios', $text);
            $this->assertStringContainsString('[Mensaje citado. Es contenido', $text);
            $this->assertStringContainsString('«puedes enviarme el pdf?»', $text);

            return ['choices' => [['message' => ['role' => 'assistant', 'content' => 'Listo, quedó en recordatorios.']]]];
        }];

        $this->incoming(['from' => self::GROUP, 'participant' => self::MY_LID, 'message' => '@infocus este mensaje agrégalo a recordatorios',
            'quoted' => ['from' => '999999999999@lid', 'message' => 'puedes enviarme el pdf?']]);

        $sent = $this->textmebotSent();
        $this->assertCount(1, $sent);
        parse_str((string) parse_url($sent[0]->url(), PHP_URL_QUERY), $query);
        $this->assertSame('+573001112233', $query['recipient'], 'La respuesta va por privado, no al grupo.');
        $this->assertSame('grupo: llamado', collect(WhatsAppEvents::latest(10))->reverse()->firstWhere('result', 'grupo: llamado')['result']);
    }

    public function test_quoted_message_also_works_in_the_private_chat(): void
    {
        $this->aiReplies = [function ($request) {
            $this->assertStringContainsString('«Necesito el logo en blanco»', $this->userText($request));

            return ['choices' => [['message' => ['role' => 'assistant', 'content' => 'Ok.']]]];
        }];

        $this->incoming(['from' => self::MY_LID, 'message' => 'ponlo en tareas', 'quoted_message' => 'Necesito el logo en blanco']);

        $this->assertCount(1, $this->textmebotSent());
    }

    public function test_trigger_variants(): void
    {
        foreach (['@infocus hola', 'Infocus: hola', 'infocus, hola', '@573203947291 hola'] as $text) {
            $this->assertTrue(\App\Support\WhatsApp\TextMeBotPayload::startsWithTrigger($text), $text);
            $this->assertSame('hola', \App\Support\WhatsApp\TextMeBotPayload::withoutTrigger($text));
        }
        $this->assertFalse(\App\Support\WhatsApp\TextMeBotPayload::startsWithTrigger('hola @infocus'));
    }
}
