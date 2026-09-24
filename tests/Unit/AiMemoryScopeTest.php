<?php

namespace Tests\Unit;

use App\Support\Ai\AiMemoryService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use Tests\TestCase;

class AiMemoryScopeTest extends TestCase
{
    public function test_project_and_client_memories_stay_in_their_scope(): void
    {
        Storage::fake('local');
        Storage::put('ai_memories.json', json_encode([
            ['user_id' => 'u1', 'scope' => 'project', 'entity_id' => 'p1', 'text' => 'Proyecto uno usa tono sobrio'],
            ['user_id' => 'u1', 'scope' => 'client', 'entity_id' => 'c1', 'text' => 'Dproperty prefiere fotos claras'],
            ['user_id' => 'u1', 'scope' => 'project', 'entity_id' => 'p2', 'text' => 'Otro proyecto usa humor'],
            ['user_id' => 'u1', 'scope' => 'client', 'entity_id' => 'c2', 'text' => 'Otro cliente usa azul'],
        ]));
        Storage::put('clientes.json', json_encode([
            ['id' => 'c1', 'empresa' => 'Dproperty'],
            ['id' => 'c2', 'empresa' => 'Acme'],
        ]));
        Storage::put('proyectos.json', json_encode([
            ['id' => 'p1', 'titulo' => 'Campaña Dproperty', 'cliente_id' => 'c1'],
            ['id' => 'p2', 'titulo' => 'Campaña Acme', 'cliente_id' => 'c2'],
        ]));
        Auth::shouldReceive('id')->andReturn('u1');

        $service = new AiMemoryService();
        $context = ['current_project' => ['id' => 'p1', 'client_id' => 'c1']];
        $scope = (new ReflectionMethod($service, 'resolveScope'))->invoke($service, 'Recuerda que este proyecto usa tono sobrio', $context);
        $clientScope = (new ReflectionMethod($service, 'resolveScope'))->invoke($service, 'Dproperty prefiere fotos claras', $context);
        $memories = $service->relevantContext('Ayúdame con este proyecto', $context);

        $this->assertSame(['project', 'p1', 'Campaña Dproperty'], $scope);
        $this->assertSame(['client', 'c1', 'Dproperty'], $clientScope);
        $this->assertStringContainsString('Proyecto uno usa tono sobrio', $memories);
        $this->assertStringContainsString('Dproperty prefiere fotos claras', $memories);
        $this->assertStringNotContainsString('Otro proyecto usa humor', $memories);
        $this->assertStringNotContainsString('Otro cliente usa azul', $memories);

        $otherMemories = $service->relevantContext('Ayúdame con Campaña Acme', $context);
        $this->assertStringContainsString('Otro proyecto usa humor', $otherMemories);
        $this->assertStringContainsString('Otro cliente usa azul', $otherMemories);
        $this->assertStringNotContainsString('Proyecto uno usa tono sobrio', $otherMemories);

        $otherScope = (new ReflectionMethod($service, 'resolveScope'))->invoke(
            $service,
            'Usar humor en este trabajo',
            [...$context, 'last_user_message' => 'Recuerda: Campaña Acme debe usar humor']
        );
        $this->assertSame(['project', 'p2', 'Campaña Acme'], $otherScope);
    }
}
