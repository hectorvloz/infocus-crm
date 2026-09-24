<?php

namespace Tests\Feature;

use App\Http\Controllers\ProyectosController;
use App\Models\User;
use App\Repositories\FileStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use Tests\TestCase;

class ProjectAiSupportContextTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        foreach (['proyectos.json', 'clientes.json', 'mis_notas.json', 'settings.json', 'documentos.json', 'document_folders.json'] as $file) {
            Storage::disk('local')->put($file, '[]');
        }
    }

    public function test_project_ai_support_uses_only_visible_notes_and_history_from_the_linked_client(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        (new FileStore('clientes.json'))->create([
            'id' => 'client-a',
            'empresa' => 'Cliente A',
            'categoria' => 'Inmobiliaria',
        ]);
        (new FileStore('clientes.json'))->create(['id' => 'client-b', 'empresa' => 'Cliente B']);

        $project = (new FileStore('proyectos.json'))->create([
            'id' => 'project-current',
            'titulo' => 'Campaña actual',
            'cliente_id' => 'client-a',
            'cliente' => 'Cliente A',
            'descripcion' => '<p>Objetivo del proyecto actual</p>',
            'tareas' => [[
                'id' => 'task-1',
                'texto' => 'Tarjeta de anuncios',
                'descripcion' => '<p>Preparar carrusel</p>',
                'notes' => [['texto' => 'Mantener el tono premium']],
            ]],
        ]);
        (new FileStore('proyectos.json'))->create([
            'id' => 'project-history',
            'titulo' => 'Campaña anterior Cliente A',
            'cliente_id' => 'client-a',
            'descripcion' => 'Funcionaron los anuncios de apartamentos.',
            'tareas' => [[
                'id' => 'history-task',
                'texto' => 'Carrusel histórico',
                'descripcion' => 'El enfoque en espacios amplios tuvo mejor respuesta.',
                'notes' => [['texto' => 'Aprendizaje: usar fotografías luminosas.']],
            ]],
        ]);
        (new FileStore('proyectos.json'))->create([
            'id' => 'project-other',
            'titulo' => 'Proyecto de Cliente B',
            'cliente_id' => 'client-b',
            'descripcion' => 'Información que no debe cruzarse.',
        ]);

        $notes = new FileStore('mis_notas.json');
        $notes->create([
            'id' => 'visible-note',
            'ownerKey' => (string) $user->id,
            'clientId' => 'client-a',
            'title' => 'Preferencias del cliente',
            'plainText' => 'Prefiere mensajes sobrios y fotografías de interiores.',
            'updatedAt' => 20,
        ]);
        $notes->create([
            'id' => 'shared-note',
            'ownerKey' => 'another-user',
            'clientId' => 'client-a',
            'title' => 'Nota compartida del cliente',
            'plainText' => 'Usar llamados a la acción breves.',
            'collaborators' => [['userKey' => (string) $user->id, 'mode' => 'view']],
            'updatedAt' => 10,
        ]);
        $notes->create([
            'id' => 'private-note',
            'ownerKey' => 'another-user',
            'clientId' => 'client-a',
            'title' => 'Nota privada ajena',
            'plainText' => 'No debe ser visible.',
            'updatedAt' => 30,
        ]);
        $notes->create([
            'id' => 'other-client-note',
            'ownerKey' => (string) $user->id,
            'clientId' => 'client-b',
            'title' => 'Nota de Cliente B',
            'plainText' => 'Tampoco debe cruzarse.',
            'updatedAt' => 40,
        ]);

        $method = new ReflectionMethod(ProyectosController::class, 'buildProjectAiSupportPrompt');
        $method->setAccessible(true);
        $prompt = $method->invoke(app(ProyectosController::class), $project, 'Mejora la descripción');

        $this->assertStringContainsString('Tarjeta de anuncios', $prompt);
        $this->assertStringContainsString('Mantener el tono premium', $prompt);
        $this->assertStringContainsString('Campaña anterior Cliente A', $prompt);
        $this->assertStringContainsString('El enfoque en espacios amplios tuvo mejor respuesta.', $prompt);
        $this->assertStringContainsString('Aprendizaje: usar fotografías luminosas.', $prompt);
        $this->assertStringContainsString('Preferencias del cliente', $prompt);
        $this->assertStringContainsString('Nota compartida del cliente', $prompt);
        $this->assertStringNotContainsString('Nota privada ajena', $prompt);
        $this->assertStringNotContainsString('Nota de Cliente B', $prompt);
        $this->assertStringNotContainsString('Proyecto de Cliente B', $prompt);
    }
}
