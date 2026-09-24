<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\FileStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SocialMediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'filesystems.disks.local.root' => storage_path('framework/testing/disks/local'),
            'services.meta.app_id' => null,
            'services.meta.app_secret' => null,
        ]);
        Storage::disk('local')->delete([
            'social_accounts.json',
            'social_insights.json',
            'social_ads.json',
            'social_leads.json',
            'social_content_calendar.json',
            'social_sync_logs.json',
            'clientes.json',
            'users.json',
            'leads.json',
        ]);
    }

    public function test_admin_can_open_social_media_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->withSession(['user' => ['id' => $admin->id]])
            ->get(route('social-media.index'));

        $response->assertOk();
        $response->assertSee('Social Media');
        $response->assertDontSee('token_encrypted');
    }

    public function test_meta_connect_requires_credentials(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        (new FileStore('clientes.json'))->create([
            'id' => 'client-1',
            'nombre' => 'Cliente Social',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['user' => ['id' => $admin->id]])
            ->get(route('social-media.meta.connect', [
                'client_id' => 'client-1',
                'platform' => 'instagram',
            ]));

        $response->assertRedirect(route('social-media.index', [
            'tab' => 'configuracion',
            'client_id' => 'client-1',
        ]));
        $response->assertSessionHas('error');
    }

    public function test_meta_connect_requires_client_context(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->withSession(['user' => ['id' => $admin->id]])
            ->get(route('social-media.meta.connect', ['platform' => 'instagram']));

        $response->assertRedirect(route('social-media.index', ['tab' => 'cuentas']));
        $response->assertSessionHas('error');
    }

    public function test_sync_requires_active_meta_connection(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->withSession(['user' => ['id' => $admin->id]])
            ->postJson(route('api.social-media.sync'));

        $response->assertStatus(422);
        $response->assertJson(['ok' => false]);
    }

    public function test_can_create_manual_social_account_for_client(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        (new FileStore('clientes.json'))->create([
            'id' => 'client-1',
            'nombre' => 'Cliente Social',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['user' => ['id' => $admin->id]])
            ->post(route('social-media.accounts.store'), [
                'client_id' => 'client-1',
                'platform' => 'instagram',
                'type' => 'profile',
                'name' => 'Instagram Cliente',
                'username' => '@cliente',
            ]);

        $response->assertRedirect(route('social-media.index', [
            'tab' => 'cuentas',
            'client_id' => 'client-1',
        ]));

        $accounts = (new FileStore('social_accounts.json'))->all();
        $this->assertCount(1, $accounts);
        $this->assertSame('client-1', $accounts[0]['client_id']);
        $this->assertSame('instagram', $accounts[0]['platform']);
    }

    public function test_client_filter_hides_other_client_accounts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $clients = new FileStore('clientes.json');
        $clients->create(['id' => 'client-1', 'nombre' => 'Cliente Uno']);
        $clients->create(['id' => 'client-2', 'nombre' => 'Cliente Dos']);

        $accounts = new FileStore('social_accounts.json');
        $accounts->create([
            'external_id' => 'manual:instagram:1',
            'platform' => 'instagram',
            'type' => 'profile',
            'name' => 'Cuenta Cliente Uno',
            'client_id' => 'client-1',
            'status' => 'connected',
        ]);
        $accounts->create([
            'external_id' => 'manual:facebook:2',
            'platform' => 'facebook',
            'type' => 'page',
            'name' => 'Cuenta Cliente Dos',
            'client_id' => 'client-2',
            'status' => 'connected',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['user' => ['id' => $admin->id]])
            ->get(route('social-media.index', ['tab' => 'cuentas', 'client_id' => 'client-1']));

        $response->assertOk();
        $response->assertSee('Cuenta Cliente Uno');
        $response->assertDontSee('Cuenta Cliente Dos');
    }
}
