<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\FileStore;
use App\Support\Ai\AiChatImageStore;
use App\Support\Ai\GeminiProvider;
use App\Support\Ai\OpenAiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AiChatImagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.disks.local.root' => storage_path('framework/testing/disks/local')]);
        Storage::disk('local')->put('ai_chats.json', json_encode([['id' => 'sentinel', 'user_id' => 'nobody', 'messages' => []]]));
        Storage::disk('local')->put('settings.json', json_encode([[
            'id' => 'settings', 'ai_enabled' => true, 'ai_provider' => 'deepseek',
            'ai_model' => 'auto', 'ai_api_key' => 'test-key',
        ]]));
        Storage::disk('local')->put('clientes.json', json_encode([['id' => 'sentinel', 'empresa' => 'Otro cliente']]));
        Storage::disk('local')->put('proyectos.json', json_encode([['id' => 'sentinel', 'titulo' => 'Otro proyecto']]));
    }

    public function test_uploaded_image_is_sent_to_ai_and_only_chat_owner_can_view_it(): void
    {
        Http::fake(['api.deepseek.com/*' => Http::response(['choices' => [['message' => ['content' => 'Veo una captura.']]]])]);
        $owner = User::factory()->create(['role' => 'admin']);
        $other = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($owner)
            ->withSession(['user' => ['id' => $owner->id]])
            ->post(route('api.ai.chat'), [
                'message' => 'Lee esta captura',
                'images' => [UploadedFile::fake()->image('captura.png', 800, 500)],
            ], ['Accept' => 'application/json']);

        $response->assertOk()->assertJsonPath('message.content', 'Veo una captura.');
        $chatId = $response->json('chat_id');
        $chat = (new FileStore('ai_chats.json'))->find($chatId);
        $image = $chat['messages'][0]['images'][0];
        Storage::disk('local')->assertExists($image['path']);
        Http::assertSent(fn ($request) => $request['model'] === 'deepseek-flash'
            && str_starts_with((string) data_get($request->data(), 'messages.1.content.1.image_url.url'), 'data:image/png;base64,'));

        $this->get(route('api.ai.chats.show', $chatId))
            ->assertOk()
            ->assertJsonMissing(['path' => $image['path']]);
        $this->get(route('api.ai.chats.image', [$chatId, $image['id']]))->assertOk()->assertHeader('Content-Type', 'image/png');

        $this->actingAs($other)->withSession(['user' => ['id' => $other->id]])
            ->get(route('api.ai.chats.image', [$chatId, $image['id']]))->assertNotFound();

        $this->actingAs($owner)->withSession(['user' => ['id' => $owner->id]])
            ->delete(route('api.ai.chats.destroy', $chatId))->assertOk();
        Storage::disk('local')->assertMissing($image['path']);
    }

    public function test_expired_image_is_not_served_or_sent_to_the_model(): void
    {
        Http::fake(['api.deepseek.com/*' => Http::response(['choices' => [['message' => ['content' => 'Respuesta']]]])]);
        $owner = User::factory()->create(['role' => 'admin']);
        $first = $this->actingAs($owner)
            ->withSession(['user' => ['id' => $owner->id]])
            ->post(route('api.ai.chat'), [
                'message' => 'Mira esto',
                'images' => [UploadedFile::fake()->image('vieja.png', 100, 100)],
            ], ['Accept' => 'application/json']);
        $first->assertOk();
        $chatId = $first->json('chat_id');
        $chat = (new FileStore('ai_chats.json'))->find($chatId);
        $image = $chat['messages'][0]['images'][0];
        $chat['messages'][0]['images'][0]['expires_at'] = now()->subDay()->toISOString();
        (new FileStore('ai_chats.json'))->update($chatId, $chat);

        $this->get(route('api.ai.chats.image', [$chatId, $image['id']]))->assertNotFound();
        $this->postJson(route('api.ai.chat'), ['chat_id' => $chatId, 'message' => '¿Qué había en la imagen?'])->assertOk();
        Http::assertSent(fn ($request) => $request['model'] === 'deepseek-flash'
            && data_get($request->data(), 'messages.1.content') === 'Mira esto');
    }

    public function test_cleanup_removes_old_cached_images(): void
    {
        $disk = Storage::disk('local');
        $disk->put('ai-chat-cache/old/image.png', 'old');
        $disk->put('ai-chat-cache/recent/image.png', 'recent');
        touch($disk->path('ai-chat-cache/old/image.png'), now()->subDays(8)->timestamp);

        $this->assertSame(1, (new AiChatImageStore())->pruneExpired());
        $disk->assertMissing('ai-chat-cache/old/image.png');
        $disk->assertExists('ai-chat-cache/recent/image.png');
    }

    public function test_other_providers_receive_image_in_their_supported_format(): void
    {
        $content = [
            ['type' => 'text', 'text' => 'Lee la imagen'],
            ['type' => 'image_url', 'image_url' => ['url' => 'data:image/png;base64,' . base64_encode('test')]],
        ];
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'Gemini']]]]]]),
            'api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => 'OpenAI']]]]),
        ]);

        $this->assertSame('Gemini', (new GeminiProvider())->chat([['role' => 'user', 'content' => $content]], ['api_key' => 'test']));
        $this->assertSame('OpenAI', (new OpenAiProvider())->chat([['role' => 'user', 'content' => $content]], ['api_key' => 'test']));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'generativelanguage.googleapis.com')
            && data_get($request->data(), 'contents.0.parts.1.inlineData.mimeType') === 'image/png');
        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.openai.com')
            && str_starts_with((string) data_get($request->data(), 'messages.0.content.1.image_url.url'), 'data:image/png;base64,'));
    }

    public function test_chat_composer_supports_drop_paste_and_professional_markdown_rendering(): void
    {
        $view = file_get_contents(resource_path('views/layouts/app.blade.php'));

        $this->assertStringContainsString("shell?.addEventListener('drop'", $view);
        $this->assertStringContainsString("shell?.addEventListener('paste'", $view);
        $this->assertStringContainsString('function renderAiMarkdownTable(', $view);
        $this->assertStringContainsString('data-copy-ai-code', $view);

        $sendStart = strpos($view, 'async function sendMessage(text)');
        $sendEnd = strpos($view, 'async function executeConfirmedAction(', $sendStart);
        $sendFunction = substr($view, $sendStart, $sendEnd - $sendStart);
        $this->assertLessThan(
            strpos($sendFunction, 'const thinking = appendThinkingMessage();'),
            strpos($sendFunction, 'pendingImages = [];')
        );
    }
}
