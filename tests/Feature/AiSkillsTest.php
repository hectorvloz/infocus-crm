<?php

namespace Tests\Feature;

use App\Support\Ai\AiSkills;
use Tests\TestCase;

class AiSkillsTest extends TestCase
{
    private function names(string $message, string $path = ''): array
    {
        return array_column(AiSkills::select($message, $path), 'name');
    }

    public function test_all_skill_files_parse(): void
    {
        $skills = AiSkills::all();
        $this->assertGreaterThanOrEqual(10, count($skills));
        foreach ($skills as $skill) {
            $this->assertNotSame('', $skill['body'], $skill['name']);
            $this->assertTrue($skill['always'] || count($skill['triggers']) > 0, $skill['name']);
        }
    }

    public function test_requests_pick_the_right_guide(): void
    {
        $cases = [
            ['planea el proyecto web de Café Laurel por fases', '', 'planear-proyecto'],
            ['hazme un brief para el cliente nuevo', '', 'brief-creativo'],
            ['cómo va el proyecto de Somos Domus', '/proyectos', 'estado-proyecto'],
            ['arma la cotización del sitio', '', 'propuesta-comercial'],
            ['redacta un whatsapp para Pedro', '', 'redactar'],
            ['el cliente está bravo por el retraso, cómo le digo', '', 'responder-cliente'],
            ['qué leads tengo sin seguimiento', '/leads', 'leads'],
            ['prepárame la reunión con Marta', '', 'contacto-y-reunion'],
            ['qué tengo hoy', '', 'cobro-y-dia'],
            ['quién me debe facturas vencidas', '/facturas', 'cobro-y-dia'],
        ];
        foreach ($cases as [$message, $path, $expected]) {
            $names = $this->names($message, $path);
            $this->assertContains($expected, $names, "«{$message}» debería usar {$expected}; usó: " . implode(', ', $names));
            $this->assertContains('entender-pedidos', $names);
            $this->assertLessThanOrEqual(3, count($names));
        }
    }

    public function test_small_talk_only_gets_the_base_guide(): void
    {
        $this->assertSame(['entender-pedidos'], $this->names('hola, gracias'));
    }

    public function test_open_section_suggests_its_guide(): void
    {
        $this->assertContains('leads', $this->names('y este?', '/leads'));
    }

    public function test_guides_reach_the_system_prompt(): void
    {
        $service = app(\App\Support\Ai\AiService::class);
        $method = new \ReflectionMethod($service, 'buildMessages');
        $this->actingAs(\App\Models\User::factory()->make(['role' => 'admin']));
        config(['filesystems.disks.local.root' => storage_path('framework/testing/disks/local')]);
        \Illuminate\Support\Facades\Storage::disk('local')->put('settings.json', '[]');

        $messages = $method->invoke($service, 'hazme un brief para el cliente nuevo', [], [], []);
        $this->assertStringContainsString('Brief creativo', $messages[0]['content']);
        $this->assertStringContainsString('No incluye', $messages[0]['content']);
        $this->assertStringContainsString('Entender los pedidos', $messages[0]['content']);
    }
}
