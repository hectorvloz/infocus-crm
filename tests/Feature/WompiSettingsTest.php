<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\FileStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WompiSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.disks.local.root' => storage_path('framework/testing/disks/local')]);
        Storage::disk('local')->put('settings.json', '[]');
        Storage::disk('local')->put('users.json', '[]');
    }

    public function test_integrations_page_requests_the_wompi_event_secret_and_shows_webhook_url(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->withSession(['user' => ['id' => $admin->id]])
            ->get(route('settings.integrations'));

        $response->assertOk();
        $response->assertSee('name="wompi_event_secret"', false);
        $response->assertSee(route('webhooks.wompi'));
    }

    public function test_wompi_event_secret_is_encrypted_when_integrations_are_saved(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $integritySecret = 'ENC:'.Crypt::encryptString('prod_integrity_existing');
        (new FileStore('settings.json'))->create([
            'id' => 'settings',
            'wompi_integrity_secret' => $integritySecret,
        ]);

        $this->actingAs($admin)
            ->withSession(['user' => ['id' => $admin->id]])
            ->put(route('settings.integrations.update'), [
                'payment_gateway' => 'wompi',
                'wompi_mode' => 'live',
                'wompi_public_key' => 'pub_prod_example',
                'wompi_integrity_secret' => '',
                'wompi_event_secret' => 'prod_events_example',
                'wompi_currency' => 'COP',
            ])
            ->assertRedirect(route('settings.integrations'));

        $settings = (new FileStore('settings.json'))->find('settings');
        $this->assertSame($integritySecret, $settings['wompi_integrity_secret']);
        $this->assertStringStartsWith('ENC:', $settings['wompi_event_secret']);
        $this->assertSame(
            'prod_events_example',
            Crypt::decryptString(substr($settings['wompi_event_secret'], 4))
        );
    }

    public function test_live_wompi_configuration_rejects_sandbox_secrets(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->withSession(['user' => ['id' => $admin->id]])
            ->from(route('settings.integrations'))
            ->put(route('settings.integrations.update'), [
                'payment_gateway' => 'wompi',
                'wompi_mode' => 'live',
                'wompi_public_key' => 'pub_prod_example',
                'wompi_integrity_secret' => 'prod_integrity_example',
                'wompi_event_secret' => 'test_events_example',
                'wompi_currency' => 'COP',
            ]);

        $response->assertRedirect(route('settings.integrations'));
        $response->assertSessionHasErrors('wompi_event_secret');
        $this->assertNull((new FileStore('settings.json'))->find('settings'));
    }
}
