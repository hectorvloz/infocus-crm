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
            ->assertSee('aún no está activada');
    }
}
