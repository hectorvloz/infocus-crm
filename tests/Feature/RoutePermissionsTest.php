<?php

namespace Tests\Feature;

use App\Support\RoleAccess;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * RoleAccess deja pasar las rutas que no están en su mapa. Toda ruta del equipo
 * debe tener un permiso; si no, cualquier usuario con sesión podría abrirla.
 */
class RoutePermissionsTest extends TestCase
{
    public function test_every_team_route_has_a_permission(): void
    {
        $ref = new \ReflectionClass(RoleAccess::class);
        $patterns = array_merge(array_keys($ref->getConstant('ROUTE_PERMISSIONS')), $ref->getConstant('ALWAYS_ALLOWED_ROUTE_PATTERNS'));

        $missing = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => in_array('auth.session', $route->gatherMiddleware(), true))
            ->reject(fn ($route) => $route->getName() && collect($patterns)->contains(fn ($p) => Str::is($p, $route->getName())))
            ->map(fn ($route) => $route->getName() ?: $route->uri())
            ->values()
            ->all();

        $this->assertSame([], $missing, 'Rutas sin permiso en RoleAccess: ' . implode(', ', $missing));
    }
}
