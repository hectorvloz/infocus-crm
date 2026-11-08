<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\FileStore;
use App\Support\Ai\AiMemoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AiMemoryChannelsTest extends TestCase
{
    use RefreshDatabase;

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
            'whatsapp_enabled' => true, 'whatsapp_provider' => 'textmebot', 'whatsapp_owner' => '+573001112233', 'whatsapp_user_id' => (string) $this->admin->id,
            'whatsapp_textmebot_apikey' => 'ENC:' . Crypt::encryptString('tmb-key'), 'whatsapp_textmebot_webhook_key' => 'clave',
        ]);
        Http::fake(function ($request) {
            if (str_contains($request->url(), 'api.textmebot.com')) {
                return Http::response(['status' => 'ok']);
            }
            $next = array_shift($this->aiReplies);

            return Http::response(is_callable($next) ? $next($request) : $next);
        });
    }

    private function text(string $content): array
    {
        return ['choices' => [['message' => ['role' => 'assistant', 'content' => $content]]]];
    }

    public function test_whatsapp_saves_how_i_want_to_be_spoken_to_and_uses_it_next_time(): void
    {
        $this->aiReplies = [
            $this->text('Listo, te escribo con emojis.'),
            $this->text('{"remember":true,"memory":"Escribirle con emojis"}'),
        ];
        $this->postJson('/webhooks/whatsapp/textmebot/clave', ['type' => 'text', 'from' => '573001112233', 'message' => 'Quiero que me escribas con emojis'])->assertOk();

        $memory = collect((new FileStore('ai_memories.json'))->all())->first();
        $this->assertSame('Escribirle con emojis', $memory['text']);
        $this->assertSame('user', $memory['scope']);
        $this->assertSame((string) $this->admin->id, $memory['user_id']);

        $this->aiReplies = [
            function ($request) {
                $this->assertStringContainsString('Escribirle con emojis', json_encode($request['messages'], JSON_UNESCAPED_UNICODE));

                return $this->text('Hola');
            },
            $this->text('{"remember":false,"memory":""}'),
        ];
        $this->postJson('/webhooks/whatsapp/textmebot/clave', ['type' => 'text', 'from' => '573001112233', 'message' => '¿Quién me debe?'])->assertOk();
        $this->assertCount(1, (new FileStore('ai_memories.json'))->all());
    }

    public function test_personal_memories_always_reach_the_model_even_with_many_project_memories(): void
    {
        $this->actingAs($this->admin);
        $store = new FileStore('ai_memories.json');
        $store->create(['user_id' => (string) $this->admin->id, 'scope' => 'user', 'text' => 'Respuestas de máximo 3 líneas', 'updated_at' => now()->subYear()->toISOString()]);
        for ($i = 0; $i < 15; $i++) {
            $store->create(['user_id' => (string) $this->admin->id, 'scope' => 'project', 'entity_id' => 'p1', 'text' => "Regla del proyecto {$i}", 'updated_at' => now()->toISOString()]);
        }

        $context = (new AiMemoryService())->relevantContext('hola');

        $this->assertStringContainsString('Respuestas de máximo 3 líneas', $context);
    }

    public function test_i_can_add_personal_memories_by_hand(): void
    {
        $this->actingAs($this->admin)->post(route('settings.ai.memory.store'), ['scope' => 'user', 'text' => 'Háblame de tú, corto y con emojis'])
            ->assertRedirect(route('settings.ai', ['tab' => 'memoria']));
        $this->actingAs($this->admin)->post(route('settings.ai.memory.store'), ['scope' => 'project', 'text' => 'No'])->assertSessionHasErrors('scope');

        $memory = collect((new FileStore('ai_memories.json'))->all())->first();
        $this->assertSame('Háblame de tú, corto y con emojis', $memory['text']);
        $this->assertSame('manual', $memory['source']);
        $this->actingAs($this->admin)->get(route('settings.ai', ['tab' => 'memoria']))->assertOk()->assertSee('Háblame de tú, corto y con emojis');
    }
}
