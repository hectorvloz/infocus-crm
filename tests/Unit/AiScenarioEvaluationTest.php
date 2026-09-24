<?php

namespace Tests\Unit;

use App\Http\Controllers\AiController;
use App\Support\Ai\AiActionExecutor;
use App\Support\Ai\AiMemoryService;
use App\Support\Ai\AiService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use Tests\TestCase;

class AiScenarioEvaluationTest extends TestCase
{
    public function test_notes_for_two_clients_create_distinct_chat_scopes_and_memories(): void
    {
        Storage::fake('local');
        Storage::put('clientes.json', json_encode([
            ['id' => 'c1', 'empresa' => 'Dproperty'],
            ['id' => 'c2', 'empresa' => 'Acme'],
        ]));
        Storage::put('mis_notas.json', json_encode([
            ['id' => 'n1', 'ownerKey' => 'u1', 'title' => 'Dproperty', 'clientId' => 'c1'],
            ['id' => 'n2', 'ownerKey' => 'u1', 'title' => 'Acme', 'clientId' => 'c2'],
        ]));
        Storage::put('ai_memories.json', json_encode([
            ['user_id' => 'u1', 'scope' => 'client', 'entity_id' => 'c1', 'text' => 'Usar tono sobrio'],
            ['user_id' => 'u1', 'scope' => 'client', 'entity_id' => 'c2', 'text' => 'Usar tono alegre'],
        ]));
        Auth::shouldReceive('user')->andReturn((object) ['id' => 'u1', 'role' => 'admin']);
        Auth::shouldReceive('id')->andReturn('u1');

        $controller = new AiController(new AiService(), new AiActionExecutor());
        $scope = new ReflectionMethod($controller, 'contextScopeKey');
        $dproperty = ['current_note' => ['id' => 'n1', 'client_id' => 'c2']];
        $acme = ['current_note' => ['id' => 'n2', 'client_id' => 'c1']];
        $this->assertSame('client:c1', $scope->invoke($controller, $dproperty));
        $this->assertSame('client:c2', $scope->invoke($controller, $acme));
        $this->assertSame('client:c2', $scope->invoke($controller, $dproperty, 'Muéstrame datos de Acme'));

        $memory = new AiMemoryService();
        $this->assertStringContainsString('tono sobrio', $memory->relevantContext('Ayúdame con esta nota', $dproperty));
        $this->assertStringNotContainsString('tono alegre', $memory->relevantContext('Ayúdame con esta nota', $dproperty));
        $this->assertStringContainsString('tono alegre', $memory->relevantContext('Ayúdame con esta nota', $acme));
        $this->assertStringNotContainsString('tono sobrio', $memory->relevantContext('Ayúdame con esta nota', $acme));
        $this->assertStringContainsString('tono alegre', $memory->relevantContext('Muéstrame datos de Acme', $dproperty));
        $source = new ReflectionMethod($memory, 'sourceUrl');
        $this->assertSame('/mis-notas?note=n1', $source->invoke($memory, 'client', 'c1', $dproperty));
    }

    public function test_explicit_request_to_create_project_from_note_is_not_blocked(): void
    {
        Storage::fake('local');
        $controller = new AiController(new AiService(), new AiActionExecutor());
        $preflight = new ReflectionMethod($controller, 'preflightAssistantReply');
        $this->assertNull($preflight->invoke($controller, 'De esta nota crea un proyecto con tareas', ['current_note' => ['id' => 'n1']]));

        $executor = new AiActionExecutor();
        $context = new \ReflectionProperty($executor, 'context');
        $context->setValue($executor, ['current_note' => ['id' => 'n1']]);
        $intent = new ReflectionMethod($executor, 'resolveUserDirectiveIntent');
        $this->assertSame('project_create', $intent->invoke($executor, 'De esta nota crea un proyecto con tareas', 'nuevo proyecto'));
    }
}
