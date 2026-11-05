<?php

namespace App\Http\Controllers;

use App\Mcp\Support\McpTokens;
use App\Repositories\FileStore;
use Illuminate\Http\Request;
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
}
