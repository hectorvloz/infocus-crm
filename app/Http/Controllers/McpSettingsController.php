<?php

namespace App\Http\Controllers;

use App\Mcp\Support\McpTokens;
use App\Repositories\FileStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Passport;

/** Configuración → MCP: conexiones de asistentes de IA (tokens personales y OAuth) y su actividad. */
class McpSettingsController extends Controller
{
    private function ensureAdmin(): void
    {
        abort_unless(in_array(strtolower((string) (Auth::user()->role ?? '')), ['admin', 'super_admin'], true), 403, 'Solo administradores.');
    }

    public function index()
    {
        $this->ensureAdmin();
        $user = Auth::user();

        // Si aún no se corrieron las migraciones de Passport, la pantalla funciona y avisa en vez de fallar.
        $oauthReady = true;
        try {
            $oauth = Passport::token()->newQuery()
                ->where('user_id', $user->getAuthIdentifier())
                ->where('revoked', false)
                ->where('expires_at', '>', now()->subDays(30))
                ->with('client')
                ->latest()
                ->get()
                ->groupBy('client_id')
                ->map(fn ($tokens) => ['client' => $tokens->first()->client?->name ?? 'Aplicación', 'client_id' => $tokens->first()->client_id, 'last' => $tokens->first()->created_at]);
        } catch (\Throwable $e) {
            report($e);
            $oauthReady = false;
            $oauth = collect();
        }

        $activity = collect((new FileStore('mcp_activity.json'))->all())
            ->where('user_id', (string) $user->getAuthIdentifier())
            ->sortByDesc('at')
            ->take(50)
            ->values();

        return view('settings.mcp', [
            'tokens' => (new McpTokens())->forUser($user->getAuthIdentifier()),
            'oauth' => $oauth->values(),
            'activity' => $activity,
            'endpoint' => url('/mcp'),
            'newToken' => session('mcp_new_token'),
            'oauthReady' => $oauthReady,
            'setup' => $this->setupStatus(),
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureAdmin();
        $data = $request->validate([
            'name' => 'required|string|max:60',
            'access' => 'required|in:' . implode(',', McpTokens::ACCESS_LEVELS),
            'days' => 'nullable|in:30,90,365',
        ]);
        $result = (new McpTokens())->create(Auth::id(), $data['name'], $data['access'], isset($data['days']) ? (int) $data['days'] : null);

        // El token se muestra una sola vez; flash evita que quede en la URL o en el historial.
        return redirect()->route('settings.mcp')->with('mcp_new_token', ['token' => $result['token'], 'name' => $result['record']['name']]);
    }

    public function destroy(string $id)
    {
        $this->ensureAdmin();
        abort_unless((new McpTokens())->revoke($id, Auth::id()), 404);

        return redirect()->route('settings.mcp')->with('success', 'Conexión revocada.');
    }

    public function revokeOauth(string $clientId)
    {
        $this->ensureAdmin();
        $tokens = Passport::token()->newQuery()->where('user_id', Auth::id())->where('client_id', $clientId)->get();
        abort_if($tokens->isEmpty(), 404);
        foreach ($tokens as $token) {
            $token->revoke();
            $token->refreshToken?->revoke();
        }

        return redirect()->route('settings.mcp')->with('success', 'Acceso de la aplicación revocado.');
    }

    /** Estado de la activación: paquetes registrados, tablas de OAuth y llaves de Passport. */
    private function setupStatus(): array
    {
        $tables = false;
        try {
            $tables = Schema::hasTable('oauth_access_tokens') && Schema::hasTable('oauth_clients');
        } catch (\Throwable) {
        }

        return [
            'packages' => Route::has('mcp.oauth.register'),
            'tables' => $tables,
            'keys' => is_file(Passport::keyPath('oauth-private.key')) && is_file(Passport::keyPath('oauth-public.key')),
        ];
    }

    /**
     * Activa el MCP desde la web en hostings sin consola: registra los paquetes nuevos,
     * crea las tablas de OAuth y genera las llaves de Passport. Se puede repetir sin riesgo.
     */
    public function activate()
    {
        $this->ensureAdmin();
        $log = [];
        $run = function (string $command, array $args = []) use (&$log) {
            try {
                $code = Artisan::call($command, $args);
                $log[] = $command . ($code === 0 ? ': ok' : ': falló');
            } catch (\Throwable $e) {
                report($e);
                $log[] = $command . ': falló (' . $e->getMessage() . ')';
            }
        };

        $run('package:discover');
        $run('migrate', ['--force' => true]);
        $log[] = 'llaves: ' . $this->ensurePassportKeys();
        $run('optimize:clear');

        return redirect()->route('settings.mcp')->with('success', 'Activación ejecutada · ' . implode(' · ', $log));
    }

    /**
     * Genera las llaves de OAuth como lo hace "php artisan passport:keys". Ese comando solo
     * existe en consola, y este hosting no tiene consola.
     */
    private function ensurePassportKeys(): string
    {
        $public = Passport::keyPath('oauth-public.key');
        $private = Passport::keyPath('oauth-private.key');
        if (is_file($public) && is_file($private)) {
            return 'ya existían';
        }
        try {
            $key = \phpseclib4\Crypt\RSA::createKey(4096);
            file_put_contents($public, (string) $key->getPublicKey());
            file_put_contents($private, (string) $key);
            @chmod($public, 0660);
            @chmod($private, 0600);

            return 'creadas';
        } catch (\Throwable $e) {
            report($e);

            return 'falló (' . $e->getMessage() . ')';
        }
    }
}
