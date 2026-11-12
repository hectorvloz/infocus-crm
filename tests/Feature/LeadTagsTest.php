<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\FileStore;
use App\Support\LeadTags;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeadTagsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.disks.local.root' => storage_path('framework/testing/disks/local')]);
        foreach (['leads.json', 'settings.json', 'roles.json', 'header_reminders.json'] as $file) {
            Storage::disk('local')->put($file, '[]');
        }
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    private function asAdmin()
    {
        return $this->actingAs($this->admin)->withSession(['user' => ['id' => $this->admin->id]]);
    }

    public function test_normalizes_tags_without_duplicates(): void
    {
        $this->assertSame(['RentaCar', 'Inmobiliarias'], LeadTags::normalize(' RentaCar , rentacar,  Inmobiliarias ,, '));
        $this->assertSame(['Clínicas'], LeadTags::normalize(['Clínicas', 'clinicas']));
        // Conserva cómo se escribió la primera vez y ordena sin importar mayúsculas.
        $this->assertSame(['A', 'b'], LeadTags::all([['etiquetas' => ['b', 'A']], ['etiquetas' => ['B']], ['nombre' => 'Sin etiquetas']]));
    }

    public function test_tags_are_saved_from_the_lead_forms(): void
    {
        $this->asAdmin()->post(route('leads.store'), [
            'nombre' => 'Renta Car Pinares', 'telefono' => '+573235300800', 'etapa' => 'Posible cliente',
            'etiquetas' => 'RentaCar, Pereira, rentacar',
        ])->assertRedirect(route('leads.index'));

        $lead = collect((new FileStore('leads.json'))->all())->firstWhere('nombre', 'Renta Car Pinares');
        $this->assertSame(['RentaCar', 'Pereira'], $lead['etiquetas']);

        $this->asAdmin()->post(route('leads.update', ['id' => $lead['id']]), [
            'nombre' => 'Renta Car Pinares', 'etapa' => 'Contactado', 'etiquetas' => 'RentaCar',
        ])->assertRedirect(route('leads.index'));
        $this->assertSame(['RentaCar'], (new FileStore('leads.json'))->find($lead['id'])['etiquetas']);
    }

    public function test_kanban_offers_a_filter_with_existing_tags(): void
    {
        (new FileStore('leads.json'))->create(['nombre' => 'Rent a Car Gómez', 'etapa' => 'Posible cliente', 'etiquetas' => ['RentaCar']]);
        (new FileStore('leads.json'))->create(['nombre' => 'Inmobiliaria Sur', 'etapa' => 'Posible cliente', 'etiquetas' => ['Inmobiliarias']]);

        $html = $this->asAdmin()->get(route('leads.index'))->assertOk()->getContent();
        $this->assertStringContainsString('id="leadsTagFilter"', $html);
        $this->assertStringContainsString('<option value="RentaCar">RentaCar</option>', $html);
        $this->assertStringContainsString('<option value="Inmobiliarias">Inmobiliarias</option>', $html);

        $edit = $this->asAdmin()->get(route('leads.create'))->assertOk()->getContent();
        $this->assertStringContainsString('name="etiquetas"', $edit);
        $this->assertStringContainsString('data-etiqueta="RentaCar"', $edit);
    }

    public function test_leads_can_be_archived_restored_and_purged_from_the_trash(): void
    {
        $store = new FileStore('leads.json');
        $lead = $store->create(['nombre' => 'Gallo Rent A Car', 'etapa' => 'Posible cliente']);

        $this->asAdmin()->postJson(route('api.leads.archivar', ['id' => $lead['id']]))->assertOk();
        $this->assertNotEmpty($store->find($lead['id'])['archivado_en']);

        $html = $this->asAdmin()->get(route('leads.index'))->assertOk()->getContent();
        $this->assertStringContainsString('Papelera de leads', $html);
        $this->assertStringContainsString('"leadsArchivedData"', $html);
        $this->assertStringNotContainsString('"nombre":"Gallo Rent A Car","etapa"', explode('id="leadsArchivedData"', $html)[0]);

        $this->asAdmin()->postJson(route('api.leads.restaurar', ['id' => $lead['id']]))->assertOk();
        $this->assertNull($store->find($lead['id'])['archivado_en']);

        $this->asAdmin()->deleteJson(route('leads.destroy', ['id' => $lead['id']]))->assertOk();
        $this->assertNull($store->find($lead['id']));
    }

    public function test_not_interested_is_a_red_column_and_a_valid_stage(): void
    {
        $store = new FileStore('leads.json');
        $lead = $store->create(['nombre' => 'TU RENT A CAR', 'etapa' => 'Contactado']);

        $this->asAdmin()->postJson(route('api.leads.mover'), ['id' => $lead['id'], 'etapa' => 'No interesado'])->assertOk();
        $this->assertSame('No interesado', $store->find($lead['id'])['etapa']);
        $this->assertSame('No interesado', \App\Support\LeadStages::normalize('Perdido'));

        $html = $this->asAdmin()->get(route('leads.index'))->assertOk()->getContent();
        $this->assertStringContainsString('"No interesado"', $html);
        $this->assertStringContainsString('Zona roja', $html);
    }
}
