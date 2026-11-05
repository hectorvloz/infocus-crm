<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * El CRM se despliega con la raíz del repo como raíz web. Estas reglas evitan que
 * storage (datos de clientes, settings con tokens), vendor o config se sirvan por web.
 */
class WebRootProtectionTest extends TestCase
{
    private function root(): string
    {
        return dirname(__DIR__, 2);
    }

    public function test_root_htaccess_sends_every_request_to_public(): void
    {
        $htaccess = file_get_contents($this->root() . '/.htaccess');

        $this->assertMatchesRegularExpression('/RewriteRule \^\(\.\*\)\$ public\/\$1 \[L\]/', $htaccess);
        // Si vuelve la condición "!-f", los archivos reales fuera de public/ se sirven directo.
        $this->assertStringNotContainsString('!-f', $htaccess);
        $this->assertStringContainsString('Options -Indexes', $htaccess);
    }

    public function test_internal_folders_deny_web_access_on_their_own(): void
    {
        foreach (['app', 'bootstrap', 'config', 'database', 'resources', 'routes', 'storage', 'vendor'] as $dir) {
            $file = $this->root() . "/{$dir}/.htaccess";
            $this->assertFileExists($file, "{$dir}/.htaccess es obligatorio");
            $this->assertStringContainsString('Require all denied', file_get_contents($file), "{$dir} debe negar el acceso web");
        }
    }
}
