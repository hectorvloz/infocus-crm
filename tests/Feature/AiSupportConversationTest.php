<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\FileStore;
use App\Support\Ai\AiService;
use App\Support\Ai\AiSupportConversationStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class AiSupportConversationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        foreach (['ai_support_conversations.json', 'ai_chats.json', 'proyectos.json', 'mis_notas.json', 'clientes.json'] as $file) {
            Storage::disk('local')->put($file, '[]');
        }
    }

    public function test_support_history_is_isolated_by_user_scope_and_entity_and_expires_after_one_day(): void
    {
        Carbon::setTestNow('2026-09-24 10:00:00');
        $store = new AiSupportConversationStore();
        $store->recordExchange('user-1', 'project', 'project-1', 'Mejóralo', '{"summary":"Hecho"}', 'Hecho');

        $this->assertSame(['Mejóralo', 'Hecho'], array_column($store->messages('user-1', 'project', 'project-1'), 'content'));
        $this->assertSame([], $store->messages('user-2', 'project', 'project-1'));
        $this->assertSame([], $store->messages('user-1', 'task', 'project-1'));
        $this->assertSame([], $store->messages('user-1', 'project', 'project-2'));

        Carbon::setTestNow('2026-09-25 10:00:01');
        $this->assertSame([], $store->messages('user-1', 'project', 'project-1'));
        Carbon::setTestNow();
    }

    public function test_history_endpoint_returns_only_the_authenticated_users_entity_thread(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        (new FileStore('proyectos.json'))->create(['id' => 'project-1', 'titulo' => 'Proyecto', 'tareas' => []]);
        $store = new AiSupportConversationStore();
        $store->recordExchange((string) $user->id, 'project', 'project-1', 'Pregunta', 'Respuesta');
        $store->recordExchange('otro-usuario', 'project', 'project-1', 'Secreto', 'Privado');

        $this->actingAs($user)->withSession(['user' => ['id' => $user->id]])
            ->getJson(route('api.ai.support-history', ['scope' => 'project', 'entity_id' => 'project-1']))
            ->assertOk()
            ->assertJsonPath('expires_in_hours', 24)
            ->assertJsonCount(2, 'messages')
            ->assertJsonMissing(['content' => 'Secreto']);
    }

    public function test_note_support_sends_previous_exchange_to_the_ai_on_follow_up(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        (new FileStore('mis_notas.json'))->create([
            'id' => 'note-1',
            'ownerKey' => (string) $user->id,
            'title' => 'Ideas',
            'plainText' => 'Contenido actual',
        ]);

        $call = 0;
        $this->mock(AiService::class, function (MockInterface $mock) use (&$call) {
            $mock->shouldReceive('makeTitle')->once()->andReturn('Apoyo de nota');
            $mock->shouldReceive('extractMemoryCandidate')->twice()->andReturn(null);
            $mock->shouldReceive('reply')->twice()->andReturnUsing(function ($message, $history) use (&$call) {
                $call++;
                if ($call === 1) {
                    $this->assertSame([], $history);
                    return ['content' => 'Primera propuesta', 'provider' => 'fake', 'actions' => []];
                }
                $this->assertSame('Primera instrucción', $history[0]['content']);
                $this->assertSame('Primera propuesta', $history[1]['content']);
                return ['content' => 'Segunda propuesta', 'provider' => 'fake', 'actions' => []];
            });
        });

        $payload = fn (string $visible, string $prompt) => [
            'chat_id' => null,
            'message' => $prompt,
            'support_scope' => 'note',
            'support_entity_id' => 'note-1',
            'context' => [
                'current_note' => ['id' => 'note-1', 'title' => 'Ideas'],
                'last_user_message' => $visible,
                'forced_intent' => 'note_update',
            ],
        ];

        $client = $this->actingAs($user)->withSession(['user' => ['id' => $user->id]]);
        $client->postJson(route('api.ai.chat'), $payload('Primera instrucción', 'Prompt completo uno'))->assertOk();
        $client->postJson(route('api.ai.chat'), $payload('Continúa y agrega ejemplos', 'Prompt completo dos'))->assertOk();
        $this->assertSame(2, $call);
    }

    public function test_note_support_history_cannot_be_read_by_an_unrelated_user(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $other = User::factory()->create(['role' => 'admin']);
        (new FileStore('mis_notas.json'))->create(['id' => 'private-note', 'ownerKey' => (string) $owner->id, 'title' => 'Privada']);

        $this->actingAs($other)->withSession(['user' => ['id' => $other->id]])
            ->getJson(route('api.ai.support-history', ['scope' => 'note', 'entity_id' => 'private-note']))
            ->assertNotFound();
    }

    public function test_project_support_uses_its_previous_exchange_on_follow_up(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        (new FileStore('proyectos.json'))->create([
            'id' => 'project-follow-up',
            'titulo' => 'Proyecto continuo',
            'descripcion' => '<p>Inicial</p>',
            'tareas' => [],
        ]);

        $call = 0;
        $firstRaw = json_encode(['target' => 'description', 'description_html' => '<p>Primera versión</p>', 'summary' => 'Primera respuesta']);
        $this->mock(AiService::class, function (MockInterface $mock) use (&$call, $firstRaw) {
            $mock->shouldReceive('reply')->twice()->andReturnUsing(function ($prompt, $history) use (&$call, $firstRaw) {
                $call++;
                if ($call === 1) {
                    $this->assertSame([], $history);
                    return ['content' => $firstRaw, 'provider' => 'fake'];
                }
                $this->assertSame('Haz una primera versión', $history[0]['content']);
                $this->assertSame($firstRaw, $history[1]['content']);
                return ['content' => json_encode([
                    'target' => 'description',
                    'description_html' => '<p>Segunda versión</p>',
                    'summary' => 'Segunda respuesta',
                ]), 'provider' => 'fake'];
            });
        });

        $client = $this->actingAs($user)->withSession(['user' => ['id' => $user->id]]);
        $client->postJson(route('api.proyectos.ia-apoyo'), [
            'id' => 'project-follow-up',
            'message' => 'Haz una primera versión',
            'current_description' => '<p>Inicial</p>',
        ])->assertOk();
        $client->postJson(route('api.proyectos.ia-apoyo'), [
            'id' => 'project-follow-up',
            'message' => 'Ahora hazla más breve',
            'current_description' => '<p>Primera versión</p>',
        ])->assertOk();
        $this->assertSame(2, $call);
    }

    public function test_main_chat_keeps_an_open_note_as_its_exact_working_scope(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        (new FileStore('clientes.json'))->create(['id' => 'client-1', 'empresa' => 'Cliente']);
        (new FileStore('mis_notas.json'))->create([
            'id' => 'note-exact',
            'ownerKey' => (string) $user->id,
            'clientId' => 'client-1',
            'title' => 'Nota abierta',
            'plainText' => 'Texto actual',
        ]);

        $this->mock(AiService::class, function (MockInterface $mock) {
            $mock->shouldReceive('makeTitle')->once()->andReturn('Editar nota');
            $mock->shouldReceive('extractMemoryCandidate')->once()->andReturn(null);
            $mock->shouldReceive('reply')->once()->withArgs(function ($message, $history, $context) {
                return $message === 'Agrega el dato nuevo a esta nota'
                    && $history === []
                    && data_get($context, 'current_note.id') === 'note-exact';
            })->andReturn([
                'content' => "Actualizar nota personal:\nNota ID: note-exact\nTítulo: Nota abierta\nContenido:\nTexto actual con dato nuevo",
                'provider' => 'fake',
                'actions' => [],
            ]);
        });

        $this->actingAs($user)->withSession(['user' => ['id' => $user->id]])
            ->postJson(route('api.ai.chat'), [
                'message' => 'Agrega el dato nuevo a esta nota',
                'context' => ['current_note' => ['id' => 'note-exact', 'title' => 'Nota abierta', 'plain_text' => 'Texto actual']],
            ])->assertOk();

        $chat = collect((new FileStore('ai_chats.json'))->all())
            ->first(fn ($item) => (string) ($item['user_id'] ?? '') === (string) $user->id);
        $this->assertSame('note:note-exact', $chat['scope_key']);
    }
}
