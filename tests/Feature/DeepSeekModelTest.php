<?php

namespace Tests\Feature;

use App\Support\Ai\AiService;
use App\Support\Ai\DeepSeekProvider;
use Illuminate\Support\Facades\Http;
use ReflectionMethod;
use Tests\TestCase;

class DeepSeekModelTest extends TestCase
{
    public function test_automatic_and_old_chat_settings_use_the_latest_flash_model(): void
    {
        $service = new AiService();
        $resolve = new ReflectionMethod($service, 'resolveModel');

        $this->assertSame('deepseek-flash', $resolve->invoke($service, 'deepseek', 'auto'));
        $this->assertSame('deepseek-flash', $resolve->invoke($service, 'deepseek', 'deepseek-chat'));
        $this->assertSame('deepseek-flash-thinking', $resolve->invoke($service, 'deepseek', 'deepseek-reasoner'));
    }

    public function test_flash_request_disables_thinking_for_regular_chat(): void
    {
        Http::fake(['api.deepseek.com/*' => Http::response(['choices' => [['message' => ['content' => 'Listo']]]])]);

        $reply = (new DeepSeekProvider())->chat([['role' => 'user', 'content' => 'Hola']], [
            'api_key' => 'test-key',
            'model' => 'deepseek-flash',
        ]);

        $this->assertSame('Listo', $reply);
        Http::assertSent(fn ($request) => $request['model'] === 'deepseek-flash'
            && $request['thinking']['type'] === 'disabled');
    }

    public function test_flash_request_enables_thinking_when_selected(): void
    {
        Http::fake(['api.deepseek.com/*' => Http::response(['choices' => [['message' => ['content' => 'Resultado']]]])]);

        (new DeepSeekProvider())->chat([['role' => 'user', 'content' => 'Analiza']], [
            'api_key' => 'test-key',
            'model' => 'deepseek-flash-thinking',
        ]);

        Http::assertSent(fn ($request) => $request['model'] === 'deepseek-flash'
            && $request['thinking']['type'] === 'enabled');
    }
}
