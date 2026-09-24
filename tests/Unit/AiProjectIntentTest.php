<?php

namespace Tests\Unit;

use App\Support\Ai\AiActionExecutor;
use App\Support\Ai\ProjectAiContext;
use Tests\TestCase;
use ReflectionMethod;
use ReflectionProperty;

class AiProjectIntentTest extends TestCase
{
    public function test_creating_a_card_in_the_open_project_is_a_task_action(): void
    {
        $executor = new AiActionExecutor();
        (new ReflectionProperty($executor, 'context'))->setValue($executor, ['current_project' => ['id' => 'p1']]);
        $method = new ReflectionMethod($executor, 'resolveUserDirectiveIntent');

        $this->assertSame('task', $method->invoke($executor, 'Crea una tarjeta en este proyecto', 'proyecto: Demo'));
        $this->assertSame('project_update', $method->invoke($executor, 'Agrégame esto al proyecto', 'proyecto: Demo'));
        $this->assertSame('project_create', $method->invoke($executor, 'Crea un nuevo proyecto con tareas', 'nuevo proyecto'));
    }

    public function test_an_explicitly_named_project_overrides_the_open_project(): void
    {
        $project = (new ProjectAiContext())->findMentionedProject([
            ['id' => 'open', 'titulo' => 'Proyecto Abierto'],
            ['id' => 'named', 'titulo' => 'Ads Identifull'],
        ], 'Agrega una tarjeta en Ads Identifull');

        $this->assertSame('named', $project['id'] ?? null);
    }
}
