<?php

namespace Tests\Feature;

use App\Models\User;
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
            ->assertSee('Falta activar el MCP');
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
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->withSession(['user' => ['id' => $admin->id]])
            ->post('/ajustes/mcp/activar')
            ->assertRedirect('/ajustes/mcp')
            ->assertSessionHas('success');

        $this->assertTrue(Schema::hasTable('oauth_access_tokens'));
        $this->assertFileExists(storage_path('oauth-private.key'));
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
