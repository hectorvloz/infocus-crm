<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\FileStore;
use App\Support\ContractTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Mail\GenericMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContractsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.disks.local.root' => storage_path('framework/testing/disks/local')]);
        foreach (['contratos.json', 'proyectos.json', 'users.json'] as $file) {
            Storage::disk('local')->put($file, '[]');
        }
        Storage::disk('local')->put('clientes.json', json_encode([['id' => 'c1', 'empresa' => 'Café Laurel', 'nit' => '900123456', 'contacto_nombre' => 'Laura Gómez', 'contacto_email' => 'laura@laurel.test']]));
        Storage::disk('local')->put('settings.json', json_encode([['id' => 'settings', 'base_currency' => 'COP', 'company_name' => 'Estudio Indigo', 'company_tax_label' => 'NIT', 'company_tax_id' => '1088035605', 'company_city' => 'Bucaramanga']]));
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_templates_use_company_data_and_colombian_law(): void
    {
        $templates = ContractTemplates::all(['company_name' => 'Estudio Indigo', 'company_tax_id' => '1088035605', 'company_city' => 'Bucaramanga']);

        $this->assertCount(6, $templates);
        foreach ($templates as $tpl) {
            $this->assertNotEmpty($tpl['descripcion']);
            $this->assertStringContainsString('Estudio Indigo', $tpl['contenido']);
            $this->assertStringContainsString('[CLIENTE]', $tpl['contenido']);
        }
        $this->assertStringContainsString('Ley 23 de 1982', $templates['servicios']['contenido']);
        $this->assertStringContainsString('Ley 1581 de 2012', $templates['sitio_web']['contenido']);
        $this->assertStringContainsString('Superintendencia de Industria y Comercio', $templates['branding']['contenido']);
    }

    public function test_create_page_shows_templates_and_cop_by_default(): void
    {
        $html = $this->actingAs($this->admin)->get(route('contratos.create'))->assertOk()->getContent();

        $this->assertStringContainsString('Contrato de diseño y desarrollo de sitio web', $html);
        $this->assertMatchesRegularExpression('/<option value="COP"\s+selected/', $html);
        $this->assertStringContainsString('contract-editor', $html);
    }

    public function test_cop_contract_is_saved_and_editable(): void
    {
        $this->actingAs($this->admin)->post(route('contratos.store'), [
            'titulo' => 'Contrato web', 'cliente_id' => 'c1', 'contenido' => '<p>Hola</p>', 'monto' => 4500000, 'moneda' => 'COP', 'estado' => 'Borrador',
        ])->assertSessionHasNoErrors();

        $contract = collect((new FileStore('contratos.json'))->all())->first();
        $this->assertSame('COP', $contract['moneda']);

        $this->actingAs($this->admin)->get(route('contratos.edit', $contract['id']))->assertOk()->assertSee('Pesos colombianos (COP)');
        $this->actingAs($this->admin)->get(route('contratos.show', $contract['id']))->assertOk()->assertSee('4.500.000');
    }

    private function draft(array $extra = []): array
    {
        $this->actingAs($this->admin)->post(route('contratos.store'), array_merge([
            'titulo' => 'Contrato web', 'cliente_id' => 'c1', 'contenido' => '<h1>Contrato web</h1><h2>Objeto</h2><p>Diseño del sitio.</p>', 'monto' => 4500000, 'moneda' => 'COP', 'estado' => 'Borrador',
        ], $extra));

        return collect((new FileStore('contratos.json'))->all())->last();
    }

    public function test_pdf_is_a_real_download(): void
    {
        $contract = $this->draft();
        $response = $this->actingAs($this->admin)->get(route('contratos.pdf', $contract['id']))->assertOk();

        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_publish_sends_pdf_and_signing_link_to_client(): void
    {
        Mail::fake();
        $contract = $this->draft(['estado' => 'Enviado']);

        $contract = (new FileStore('contratos.json'))->find($contract['id']);
        $this->assertSame('Enviado', $contract['estado']);
        $this->assertNotEmpty($contract['public_token']);
        $this->assertSame('laura@laurel.test', $contract['enviado_a']);
        Mail::assertSent(GenericMail::class, fn ($m) => $m->hasTo('laura@laurel.test')
            && str_contains($m->content, '/contrato/' . $contract['id'] . '/' . $contract['public_token'])
            && ($m->attachment['mime'] ?? '') === 'application/pdf'
            && str_starts_with($m->attachment['data'], '%PDF'));
    }

    public function test_drafts_are_not_public_and_tokens_are_checked(): void
    {
        $contract = $this->draft();
        $contract = (new \App\Support\ContractDocuments())->ensureToken($contract);

        $this->get(route('contratos.public.show', ['id' => $contract['id'], 'token' => $contract['public_token']]))->assertNotFound();
        (new FileStore('contratos.json'))->update($contract['id'], ['estado' => 'Enviado']);
        $this->get(route('contratos.public.show', ['id' => $contract['id'], 'token' => 'otro-token']))->assertNotFound();
        $this->get(route('contratos.public.show', ['id' => $contract['id'], 'token' => $contract['public_token']]))->assertOk()->assertSee('Firmar contrato');
    }

    public function test_client_signs_from_private_link(): void
    {
        Mail::fake();
        $contract = $this->draft(['estado' => 'Enviado']);
        $contract = (new FileStore('contratos.json'))->find($contract['id']);
        $params = ['id' => $contract['id'], 'token' => $contract['public_token']];

        $this->post(route('contratos.public.sign', $params), ['nombre' => 'Laura Gómez', 'documento' => '1098765432'])->assertSessionHasErrors('acepto');
        $this->post(route('contratos.public.sign', $params), ['nombre' => 'Laura Gómez', 'documento' => '1098765432', 'acepto' => '1'])->assertRedirect();

        $signed = (new FileStore('contratos.json'))->find($contract['id']);
        $this->assertSame('Firmado', $signed['estado']);
        $signature = collect($signed['firmas'])->firstWhere('rol', 'Cliente');
        $this->assertSame('1098765432', $signature['documento']);
        $this->assertSame(hash('sha256', $signed['contenido']), $signature['hash_contenido']);
        Mail::assertSent(GenericMail::class, fn ($m) => $m->hasTo('laura@laurel.test') && str_contains($m->subjectLine, 'Contrato firmado'));

        // Firmado ya no se puede editar
        $this->actingAs($this->admin)->put(route('contratos.update', $contract['id']), ['titulo' => 'Otro', 'cliente_id' => 'c1', 'contenido' => '<p>x</p>', 'estado' => 'Borrador'])->assertSessionHas('error');
        $this->assertSame('Contrato web', (new FileStore('contratos.json'))->find($contract['id'])['titulo']);
    }

    public function test_client_portal_lists_published_contracts(): void
    {
        Storage::disk('local')->put('facturas.json', '[]');
        $this->draft(['titulo' => 'Borrador oculto']);
        Mail::fake();
        $this->draft(['titulo' => 'Contrato publicado', 'estado' => 'Enviado']);

        $html = $this->get(route('portal.dashboard', ['id' => 'c1', 'token' => md5('c1crm_portal_secret')]))->assertOk()->getContent();
        $this->assertStringContainsString('Contrato publicado', $html);
        $this->assertStringContainsString('Revisar y firmar', $html);
        $this->assertStringNotContainsString('Borrador oculto', $html);
    }
}
