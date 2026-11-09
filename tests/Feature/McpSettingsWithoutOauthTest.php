<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\FileStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class McpSettingsWithoutOauthTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_page_works_before_passport_tables_exist(): void
    {
        config(['filesystems.disks.local.root' => storage_path('framework/testing/disks/local')]);
        foreach (['mcp_tokens.json', 'mcp_activity.json', 'settings.json', 'roles.json'] as $file) {
            Storage::disk('local')->put($file, '[]');
        }
        Schema::drop('oauth_access_tokens');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->withSession(['user' => ['id' => $admin->id]])
            ->get('/ajustes/mcp')
            ->assertOk()
            ->assertSee('Falta activar el MCP')
            ->assertSee('Dominios adicionales para OAuth')
            ->assertSee('--transport streamable-http');
    }

    public function test_activate_button_creates_oauth_tables(): void
    {
        config(['filesystems.disks.local.root' => storage_path('framework/testing/disks/local')]);
        foreach (['mcp_tokens.json', 'mcp_activity.json', 'settings.json', 'roles.json'] as $file) {
            Storage::disk('local')->put($file, '[]');
        }
        // Simula un servidor donde las migraciones de Passport nunca se ejecutaron.
        foreach (['oauth_auth_codes', 'oauth_access_tokens', 'oauth_refresh_tokens', 'oauth_clients', 'oauth_device_codes'] as $table) {
            Schema::dropIfExists($table);
        }
        \Illuminate\Support\Facades\DB::table('migrations')->where('migration', 'like', '%oauth%')->delete();
        $keysDir = storage_path('framework/testing/passport-keys-' . uniqid());
        mkdir($keysDir, 0700, true);
        \Laravel\Passport\Passport::loadKeysFrom($keysDir);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->withSession(['user' => ['id' => $admin->id]])
            ->post('/ajustes/mcp/activar')
            ->assertRedirect('/ajustes/mcp')
            ->assertSessionHas('success', fn ($msg) => str_contains($msg, 'llaves: creadas') && str_contains($msg, 'migrate: ok'));

        $this->assertTrue(Schema::hasTable('oauth_access_tokens'));
        $this->assertFileExists($keysDir . '/oauth-private.key');
        $this->assertStringContainsString('PRIVATE KEY', file_get_contents($keysDir . '/oauth-private.key'));
        $this->assertSame('0600', substr(sprintf('%o', fileperms($keysDir . '/oauth-private.key')), -4));
        \Laravel\Passport\Passport::loadKeysFrom(storage_path());
        array_map('unlink', glob($keysDir . '/*'));
        rmdir($keysDir);
    }

    public function test_admin_can_save_and_clear_additional_oauth_domains(): void
    {
        config(['filesystems.disks.local.root' => storage_path('framework/testing/disks/local')]);
        Storage::disk('local')->put('settings.json', '[{"id":"settings","company_name":"Indigo"}]');
        $admin = User::factory()->create(['role' => 'admin']);
        $url = route('settings.mcp.domains');
        $settings = new FileStore('settings.json');

        $this->actingAs($admin)->withSession(['user' => ['id' => $admin->id]])
            ->post($url, ['domains' => "https://Gateway.Example.com/oauth/mcp/callback\ngateway.example.com"])
            ->assertRedirect(route('settings.mcp'));
        $this->assertSame(['https://gateway.example.com'], $settings->find('settings')['mcp_redirect_domains']);

        $this->from(route('settings.mcp'))->post($url, ['domains' => "https://other.example.com\nhttp://gateway.example.com"])
            ->assertRedirect(route('settings.mcp'))
            ->assertSessionHasErrors('domains');
        $this->assertSame(['https://gateway.example.com'], $settings->find('settings')['mcp_redirect_domains']);

        $this->post($url, ['domains' => ''])->assertRedirect(route('settings.mcp'));
        $this->assertSame([], $settings->find('settings')['mcp_redirect_domains']);
        $this->assertSame('Indigo', $settings->find('settings')['company_name']);
    }

    public function test_non_admins_cannot_activate(): void
    {
        $member = User::factory()->create(['role' => 'ventas']);
        $this->actingAs($member)->withSession(['user' => ['id' => $member->id]])
            ->post('/ajustes/mcp/activar')
            ->assertRedirect()
            ->assertSessionMissing('success');
    }
}
