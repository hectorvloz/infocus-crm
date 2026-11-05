<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\FileStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GastoComprobanteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.disks.local.root' => storage_path('framework/testing/disks/local')]);
        config(['filesystems.disks.public.root' => storage_path('framework/testing/disks/public')]);
        Storage::disk('local')->put('gastos.json', '[]');
        Storage::disk('local')->put('roles.json', json_encode([
            ['id' => 'ventas', 'permissions' => ['leads.read', 'panel.read']],
            ['id' => 'contabilidad', 'permissions' => ['gastos.read']],
        ]));
        Storage::disk('public')->put('comprobantes/recibo.pdf', '%PDF-1.4 prueba');
        (new FileStore('gastos.json'))->create(['id' => 'g1', 'concepto' => 'Hosting', 'comprobante_path' => 'comprobantes/recibo.pdf']);
        (new FileStore('gastos.json'))->create(['id' => 'g2', 'concepto' => 'Raro', 'comprobante_path' => '../private/settings.json']);
    }

    private function team(string $role = 'admin')
    {
        $user = User::factory()->create(['role' => $role]);
        return $this->actingAs($user)->withSession(['user' => ['id' => $user->id]]);
    }

    public function test_guests_cannot_see_receipts(): void
    {
        $this->get('/gastos/g1/comprobante')->assertRedirect();
    }

    public function test_portal_clients_cannot_see_receipts(): void
    {
        $this->team('client')->get('/gastos/g1/comprobante')->assertRedirect();
    }

    public function test_team_member_without_expenses_permission_cannot_see_receipts(): void
    {
        $this->team('ventas')->get('/gastos/g1/comprobante')->assertRedirect();
    }

    public function test_team_member_with_expenses_permission_gets_the_receipt(): void
    {
        $response = $this->team('contabilidad')->get('/gastos/g1/comprobante');
        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertSame('%PDF-1.4 prueba', file_get_contents($response->baseResponse->getFile()->getPathname()));
    }

    public function test_paths_outside_receipts_folder_and_missing_receipts_are_404(): void
    {
        $this->team()->get('/gastos/g2/comprobante')->assertNotFound();
        $this->team()->get('/gastos/no-existe/comprobante')->assertNotFound();
    }
}
