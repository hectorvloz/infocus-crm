<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\FileStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
        foreach (['mis_notas.json', 'documentos.json', 'document_folders.json', 'clientes.json', 'facturas.json', 'roles.json'] as $file) {
            Storage::disk('local')->put($file, '[]');
        }
    }

    public function test_note_html_is_sanitized_on_write_and_when_reading_legacy_content(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $this->actingAs($owner)->withSession(['user' => ['id' => $owner->id]])
            ->postJson(route('api.mis-notas.save'), [
                'notes' => [[
                    'id' => 'safe-note',
                    'title' => 'Nota',
                    'html' => '<h1>Bien</h1><img src="data:image/png;base64,aGVsbG8=" onerror="alert(1)"><img src="//attacker.example/tracker.png"><script>alert(2)</script><a href="javascript:alert(3)">enlace</a><a href="//attacker.example/phishing">externo</a>',
                    'plainText' => 'Bien',
                ]],
            ])->assertOk();

        $stored = (new FileStore('mis_notas.json'))->find('safe-note');
        $this->assertStringNotContainsString('onerror', $stored['html']);
        $this->assertStringNotContainsString('<script', $stored['html']);
        $this->assertStringNotContainsString('javascript:', $stored['html']);
        $this->assertStringNotContainsString('attacker.example', $stored['html']);
        $this->assertStringContainsString('<h1>Bien</h1>', $stored['html']);

        (new FileStore('mis_notas.json'))->update('safe-note', [
            'html' => '<p onclick="alert(1)">Contenido anterior</p><iframe src="https://example.com"></iframe>',
        ]);

        $response = $this->getJson(route('api.mis-notas.index'))->assertOk();
        $html = (string) $response->json('data.0.html');
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('<iframe', $html);
    }

    public function test_notes_page_does_not_persist_note_contents_in_browser_local_storage(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->withSession(['user' => ['id' => $admin->id]])
            ->get(route('mis-notas.index'))
            ->assertOk()
            ->assertDontSee('localStorage.setItem(NOTES_KEY', false);
    }

    public function test_project_attachment_preview_is_hoisted_above_detail_modals(): void
    {
        $view = file_get_contents(resource_path('views/proyectos/index.blade.php'));

        $this->assertStringContainsString(
            '.project-file-preview-modal {'.PHP_EOL.'      z-index: 2147483647 !important;',
            $view
        );
        $this->assertStringContainsString(
            'document.body.appendChild(modal);',
            $view
        );
        $previewFunction = substr(
            $view,
            strpos($view, 'function openProjectFilePreview({'),
            strpos($view, 'function closeProjectFilePreview()', strpos($view, 'function openProjectFilePreview({'))
                - strpos($view, 'function openProjectFilePreview({')
        );
        $this->assertStringNotContainsString(
            'if (modal.parentElement !== document.body) {'.PHP_EOL.'        document.body.appendChild(modal);',
            $previewFunction
        );
        $this->assertStringContainsString('id="projectFilePreviewPrevious"', $view);
        $this->assertStringContainsString('id="projectFilePreviewNext"', $view);
        $this->assertStringContainsString('function showProjectFileGalleryItem(', $view);
        $this->assertStringContainsString("if (event.key === 'ArrowLeft')", $view);
        $this->assertStringContainsString("if (event.key === 'ArrowRight')", $view);
        $this->assertStringContainsString('projectPreviewSwipeStart', $view);
    }

    public function test_dangerous_document_type_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->withSession(['user' => ['id' => $admin->id]])
            ->post(route('documentos.upload'), [
                'scope' => 'personal',
                'folder' => 'Pruebas',
                'storage_mode' => 'local',
                'archivo' => UploadedFile::fake()->createWithContent('ataque.html', '<script>alert(1)</script>'),
            ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('archivo');
    }

    public function test_user_with_read_only_notes_permission_cannot_modify_a_note(): void
    {
        $viewer = User::factory()->create(['role' => 'notes-viewer']);
        (new FileStore('roles.json'))->create([
            'id' => 'notes-viewer',
            'permissions' => ['mis-notas.read'],
        ]);
        (new FileStore('mis_notas.json'))->create([
            'id' => 'owned-note',
            'ownerKey' => (string) $viewer->id,
            'title' => 'Original',
            'html' => '<p>Original</p>',
        ]);

        $this->actingAs($viewer)->withSession(['user' => ['id' => $viewer->id]])
            ->postJson(route('api.mis-notas.save'), [
                'notes' => [[
                    'id' => 'owned-note',
                    'title' => 'Alterada',
                    'html' => '<p>Alterada</p>',
                ]],
            ])->assertForbidden();

        $this->assertSame('Original', (new FileStore('mis_notas.json'))->find('owned-note')['title']);
    }

    public function test_uploaded_document_is_stored_privately(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->withSession(['user' => ['id' => $admin->id]])
            ->post(route('documentos.upload'), [
                'scope' => 'personal',
                'folder' => 'Pruebas',
                'storage_mode' => 'local',
                'archivo' => UploadedFile::fake()->image('portada.png', 80, 80),
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $document = collect((new FileStore('documentos.json'))->all())->first();
        Storage::disk('local')->assertExists($document['path']);
        Storage::disk('public')->assertMissing($document['path']);
        $this->get(route('documentos.preview', $document['id']))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_legacy_public_document_is_moved_to_private_storage_when_opened(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $path = 'documentos/legacy/archivo.pdf';
        Storage::disk('public')->put($path, '%PDF-1.4 legacy');
        $document = (new FileStore('documentos.json'))->create([
            'name' => 'Archivo anterior',
            'original_name' => 'archivo.pdf',
            'storage' => 'local',
            'path' => $path,
            'mime' => 'application/pdf',
        ]);

        $this->actingAs($admin)->withSession(['user' => ['id' => $admin->id]])
            ->get(route('documentos.download', $document['id']))
            ->assertOk();

        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_login_is_rate_limited_after_repeated_failures(): void
    {
        User::factory()->create(['email' => 'seguridad@example.com']);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from(route('login.show'))->post(route('login.perform'), [
                'email' => 'seguridad@example.com',
                'password' => 'incorrecta',
            ])->assertRedirect(route('login.show'));
        }

        $this->from(route('login.show'))->post(route('login.perform'), [
            'email' => 'seguridad@example.com',
            'password' => 'incorrecta',
        ])->assertStatus(429);
    }
}
