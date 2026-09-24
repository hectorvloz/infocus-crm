<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\FileStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuickClientFromInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.disks.local.root' => storage_path('framework/testing/disks/local')]);
        Storage::disk('local')->put('clientes.json', json_encode([['id' => 'existing', 'empresa' => 'Cliente existente']]));
        Storage::disk('local')->put('settings.json', json_encode([['id' => 'settings', 'base_currency' => 'USD']]));
    }

    public function test_only_company_is_required_and_currency_is_saved(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->withSession(['user' => ['id' => $admin->id]])
            ->postJson(route('api.clientes.quick.store'), [
                'empresa' => 'Cliente desde factura',
                'moneda' => 'COP',
            ]);

        $response->assertOk()->assertJsonPath('cliente.moneda', 'COP');
        $created = (new FileStore('clientes.json'))->find($response->json('cliente.id'));
        $this->assertSame('Cliente desde factura', $created['empresa']);
        $this->assertSame('COP', $created['moneda']);
    }

    public function test_email_without_nit_does_not_fail_after_creating_client(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->withSession(['user' => ['id' => $admin->id]])
            ->postJson(route('api.clientes.quick.store'), [
                'empresa' => 'Solo correo',
                'contacto_email' => 'cliente@example.com',
            ]);

        $response->assertOk()->assertJsonPath('cliente.empresa', 'Solo correo');
        $this->assertCount(2, (new FileStore('clientes.json'))->all());
    }

    public function test_repeated_quick_create_request_returns_the_same_client(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $payload = [
            'empresa' => 'Un solo cliente',
            'request_id' => '0c3f5a71-6c1b-4e26-b403-50a4d25123db',
        ];

        $first = $this->actingAs($admin)
            ->withSession(['user' => ['id' => $admin->id]])
            ->postJson(route('api.clientes.quick.store'), $payload);
        $second = $this->postJson(route('api.clientes.quick.store'), $payload);

        $first->assertOk();
        $second->assertOk();
        $this->assertSame($first->json('cliente.id'), $second->json('cliente.id'));
        $this->assertCount(2, (new FileStore('clientes.json'))->all());
    }

    public function test_existing_portal_email_is_rejected_before_creating_a_client(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['email' => 'usado@example.com']);

        $response = $this->actingAs($admin)
            ->withSession(['user' => ['id' => $admin->id]])
            ->postJson(route('api.clientes.quick.store'), [
                'empresa' => 'Cliente rechazado',
                'contacto_email' => 'usado@example.com',
                'nit' => '123456',
            ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('contacto_email');
        $this->assertCount(1, (new FileStore('clientes.json'))->all());
    }

    public function test_retry_with_email_and_nit_returns_existing_client(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $payload = [
            'empresa' => 'Cliente con acceso',
            'contacto_email' => 'nuevo@example.com',
            'nit' => '123456',
            'request_id' => 'a6be017d-3214-4c45-861b-57f1e169a090',
        ];

        $first = $this->actingAs($admin)
            ->withSession(['user' => ['id' => $admin->id]])
            ->postJson(route('api.clientes.quick.store'), $payload);
        $second = $this->postJson(route('api.clientes.quick.store'), $payload);

        $first->assertOk();
        $second->assertOk();
        $this->assertSame($first->json('cliente.id'), $second->json('cliente.id'));
        $this->assertCount(2, (new FileStore('clientes.json'))->all());
    }
}
