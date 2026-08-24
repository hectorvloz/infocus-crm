<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\FileStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvoiceRecurrenceToggleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.disks.local.root' => storage_path('framework/testing/disks/local')]);
        Storage::disk('local')->put('facturas.json', '[]');
        Storage::disk('local')->put('clientes.json', '[]');
        Storage::disk('local')->put('productos.json', '[]');
        Storage::disk('local')->put('settings.json', '[]');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_recurrence_can_be_disabled_without_losing_its_schedule(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $store = new FileStore('facturas.json');
        $store->create($this->recurringInvoice('template-1'));

        $response = $this->actingAs($admin)
            ->withSession(['user' => ['id' => $admin->id]])
            ->patchJson(route('api.facturas.recurrencia.toggle', 'template-1'), ['enabled' => false]);

        $response->assertOk()->assertJson([
            'ok' => true,
            'enabled' => false,
            'recurrence_id' => 'template-1',
        ]);

        $recurrence = (new FileStore('facturas.json'))->find('template-1')['recurrencia'];
        $this->assertFalse($recurrence['enabled']);
        $this->assertSame('2026-09-09', $recurrence['next_send']);
        $this->assertNotEmpty($recurrence['disabled_at']);
    }

    public function test_disabling_a_generated_invoice_updates_its_recurrence_template(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $store = new FileStore('facturas.json');
        $store->create($this->recurringInvoice('template-1'));
        $store->create([
            'id' => 'generated-1',
            'numero' => 'INV-0051',
            'cliente' => 'Dproperty',
            'fecha' => '2026-08-09',
            'estado' => 'Pagada',
            'total' => 100,
            'origen' => 'recurrente',
            'recurrencia_origen_id' => 'template-1',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['user' => ['id' => $admin->id]])
            ->patchJson(route('api.facturas.recurrencia.toggle', 'generated-1'), ['enabled' => false]);

        $response->assertOk()->assertJson([
            'enabled' => false,
            'recurrence_id' => 'template-1',
        ]);
        $this->assertFalse((new FileStore('facturas.json'))->find('template-1')['recurrencia']['enabled']);
        $this->assertArrayNotHasKey('recurrencia', (new FileStore('facturas.json'))->find('generated-1'));
    }

    public function test_reactivating_an_overdue_schedule_moves_it_to_the_next_future_cycle(): void
    {
        Carbon::setTestNow('2026-08-24 10:00:00');
        $admin = User::factory()->create(['role' => 'admin']);
        $store = new FileStore('facturas.json');
        $invoice = $this->recurringInvoice('template-1');
        $invoice['recurrencia']['enabled'] = false;
        $invoice['recurrencia']['next_send'] = '2026-07-09';
        $store->create($invoice);

        $response = $this->actingAs($admin)
            ->withSession(['user' => ['id' => $admin->id]])
            ->patchJson(route('api.facturas.recurrencia.toggle', 'template-1'), ['enabled' => true]);

        $response->assertOk()->assertJson([
            'enabled' => true,
            'next_send' => '2026-09-09',
        ]);
        $this->assertSame('2026-09-09', (new FileStore('facturas.json'))->find('template-1')['recurrencia']['next_send']);
    }

    public function test_reactivating_a_modern_schedule_without_next_send_rebuilds_that_field(): void
    {
        Carbon::setTestNow('2026-08-24 10:00:00');
        $admin = User::factory()->create(['role' => 'admin']);
        $invoice = $this->recurringInvoice('template-1');
        $invoice['recurrencia']['enabled'] = false;
        unset($invoice['recurrencia']['next_send']);
        (new FileStore('facturas.json'))->create($invoice);

        $this->actingAs($admin)
            ->withSession(['user' => ['id' => $admin->id]])
            ->patchJson(route('api.facturas.recurrencia.toggle', 'template-1'), ['enabled' => true])
            ->assertOk()
            ->assertJson(['enabled' => true, 'next_send' => '2026-09-09']);

        $recurrence = (new FileStore('facturas.json'))->find('template-1')['recurrencia'];
        $this->assertSame('2026-09-09', $recurrence['next_send']);
        $this->assertArrayNotHasKey('siguiente', $recurrence);
    }

    public function test_non_recurring_invoice_cannot_be_toggled(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        (new FileStore('facturas.json'))->create([
            'id' => 'regular-1',
            'numero' => 'INV-0053',
            'cliente' => 'Cliente',
            'fecha' => '2026-08-20',
            'estado' => 'Pendiente',
            'total' => 100,
        ]);

        $this->actingAs($admin)
            ->withSession(['user' => ['id' => $admin->id]])
            ->patchJson(route('api.facturas.recurrencia.toggle', 'regular-1'), ['enabled' => false])
            ->assertUnprocessable()
            ->assertJson(['ok' => false]);
    }

    public function test_invoice_list_renders_an_accessible_toggle_for_active_and_paused_recurrences(): void
    {
        Carbon::setTestNow('2026-08-24 10:00:00');
        $admin = User::factory()->create(['role' => 'admin']);
        $store = new FileStore('facturas.json');
        $store->create($this->recurringInvoice('active-template'));

        $paused = $this->recurringInvoice('paused-template');
        $paused['numero'] = 'INV-0050';
        $paused['recurrencia']['enabled'] = false;
        $store->create($paused);

        $response = $this->actingAs($admin)
            ->withSession(['user' => ['id' => $admin->id]])
            ->get(route('facturas.index', ['range' => 'all']));

        $response->assertOk();
        $response->assertSee('data-recurrence-toggle="active-template"', false);
        $response->assertSee('data-recurrence-toggle="paused-template"', false);
        $response->assertSee('aria-checked="true"', false);
        $response->assertSee('aria-checked="false"', false);
    }

    public function test_paused_schedule_remains_visible_in_recurring_filter(): void
    {
        Carbon::setTestNow('2026-08-24 10:00:00');
        $admin = User::factory()->create(['role' => 'admin']);
        $paused = $this->recurringInvoice('paused-template');
        $paused['recurrencia']['enabled'] = false;
        (new FileStore('facturas.json'))->create($paused);

        $response = $this->actingAs($admin)
            ->withSession(['user' => ['id' => $admin->id]])
            ->get(route('facturas.index', ['range' => 'all', 'estado' => 'Recurrente']));

        $response->assertOk();
        $response->assertSee('INV-0049');
        $response->assertSee('data-recurrence-toggle="paused-template"', false);
    }

    private function recurringInvoice(string $id): array
    {
        return [
            'id' => $id,
            'numero' => 'INV-0049',
            'cliente' => 'Estudio Indigo',
            'fecha' => '2026-07-09',
            'vencimiento' => '2026-07-09',
            'estado' => 'Pagada',
            'total' => 100,
            'items' => [],
            'recurrencia' => [
                'enabled' => true,
                'day_of_month' => 9,
                'every_months' => 1,
                'next_send' => '2026-09-09',
                'lead_days_before' => 0,
                'last_sent_at' => '2026-08-09T08:00:00Z',
            ],
        ];
    }
}
