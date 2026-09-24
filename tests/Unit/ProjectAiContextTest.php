<?php

namespace Tests\Unit;

use App\Support\Ai\ProjectAiContext;
use PHPUnit\Framework\TestCase;

class ProjectAiContextTest extends TestCase
{
    public function test_it_includes_current_card_and_sibling_content(): void
    {
        $project = [
            'id' => 'p1', 'titulo' => 'Ads Identifull', 'cliente' => 'Acme',
            'descripcion' => '<p>Campaña de marca</p>',
            'task_stages' => ['Por hacer', 'Recursos. Graficos'],
            'tareas' => [
                ['id' => 't1', 'texto' => 'Lluvia de Ideas', 'descripcion' => '<p>Logo con IA</p>', 'board_stage' => 'Por hacer', 'subtasks' => [['texto' => 'Definir mensaje']], 'notes' => [['texto' => 'Mantener tono claro']]],
                ['id' => 't2', 'texto' => 'Campaña 1 Trafico Web', 'descripcion' => '<p>Atraer visitas calificadas</p>', 'board_stage' => 'Recursos. Graficos'],
            ],
        ];

        $context = (new ProjectAiContext())->summarize($project, 't2');

        $this->assertStringContainsString('Tarjeta abierta: Campaña 1 Trafico Web', $context);
        $this->assertStringContainsString('Atraer visitas calificadas', $context);
        $this->assertStringContainsString('Lluvia de Ideas', $context);
        $this->assertStringContainsString('Logo con IA', $context);
        $this->assertStringContainsString('Definir mensaje', $context);
        $this->assertStringContainsString('Mantener tono claro', $context);
    }

    public function test_it_limits_context_and_redacts_secrets(): void
    {
        $project = [
            'titulo' => 'Demo',
            'descripcion' => '<p>token: abc123</p>',
            'tareas' => array_fill(0, 100, ['texto' => 'Tarjeta', 'descripcion' => str_repeat('contenido ', 100)]),
        ];

        $context = (new ProjectAiContext())->summarize($project);

        $this->assertStringNotContainsString('abc123', $context);
        $this->assertLessThanOrEqual(15000, mb_strlen($context));
    }

    public function test_client_history_uses_only_the_exact_associated_client(): void
    {
        $project = ['id' => 'current', 'cliente_id' => 'dproperty-id', 'titulo' => 'Nueva campaña'];
        $client = ['id' => 'dproperty-id', 'empresa' => 'Dproperty', 'ciudad' => 'Bogotá', 'pais' => 'Colombia', 'direccion' => 'Chapinero', 'categoria' => 'Inmobiliaria'];
        $projects = [
            $project,
            ['id' => 'old', 'cliente_id' => 'dproperty-id', 'titulo' => 'Campaña anterior', 'descripcion' => '<p>Enfocada en apartamentos</p>'],
            ['id' => 'other', 'cliente_id' => 'other-id', 'titulo' => 'Proyecto ajeno', 'descripcion' => 'Datos de otro cliente'],
        ];

        $context = (new ProjectAiContext())->clientContext($project, $client, $projects);

        $this->assertStringContainsString('Dproperty', $context);
        $this->assertStringContainsString('Bogotá', $context);
        $this->assertStringContainsString('Campaña anterior', $context);
        $this->assertStringContainsString('Enfocada en apartamentos', $context);
        $this->assertStringNotContainsString('Proyecto ajeno', $context);
        $this->assertStringNotContainsString('Datos de otro cliente', $context);
        $this->assertSame('', (new ProjectAiContext())->clientContext(['cliente_id' => 'other-id'], $client, $projects));
    }
}
