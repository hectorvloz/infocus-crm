<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Repositories\FileStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Support\RoleAccess;

class AuthController extends Controller
{
    protected function redirectAfterLogin(User $user)
    {
        if (($user->role ?? null) === 'client' && !empty($user->cliente_id)) {
            return route('portal.auth.dashboard');
        }

        return route(RoleAccess::firstAllowedRoute($user));
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
        ]);
        $email = strtolower(trim((string) $data['email']));

        $teamUser = $this->findTeamUserByEmail($email);
        if ($teamUser && !User::where('email', $email)->exists()) {
            $this->syncTeamUserToLoginUser($teamUser);
        }

        $status = Password::sendResetLink(
            ['email' => $email]
        );

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'Si el correo existe en el sistema, enviamos el enlace de recuperación.');
        }

        // Laravel puede responder con throttle aunque el intento anterior ya haya enviado correo.
        if ($status === Password::RESET_THROTTLED) {
            return back()->with('status', 'El enlace ya fue solicitado. Revisa tu correo y espera un momento antes de pedir otro.');
        }

        return back()->withErrors([
            'email' => 'No pudimos procesar la solicitud con ese correo. Verifica e intenta de nuevo.',
        ]);
    }

    public function showResetPassword(string $token, Request $request)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $hashedPassword = Hash::make($password);
                $user->forceFill([
                    'password' => $hashedPassword,
                    'remember_token' => Str::random(60),
                ])->save();

                $this->syncPasswordInTeamStore((string) $user->email, $hashedPassword);

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login.show')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }

    public function redirectToGoogle(Request $request)
    {
        $clientId = config('services.google.client_id');
        $redirectUri = config('services.google.redirect');

        if (!$clientId || !$redirectUri) {
            return redirect()->route('login.show')->with('google_error', 'Google Login no esta configurado.');
        }

        $state = Str::random(40);
        $request->session()->put('google_oauth_state', $state);
        $request->session()->put('google_oauth_intent', 'app');

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?' . $query);
    }

    public function handleGoogleCallback(Request $request)
    {
        if ($request->filled('error')) {
            return redirect()->route('login.show')->with('google_error', 'Inicio con Google cancelado.');
        }

        $state = $request->input('state');
        if (!$state || $state !== $request->session()->pull('google_oauth_state')) {
            return redirect()->route('login.show')->with('google_error', 'Estado OAuth invalido.');
        }

        $intent = (string) $request->session()->pull('google_oauth_intent', 'app');

        $code = $request->input('code');
        if (!$code) {
            return redirect()->route('login.show')->with('google_error', 'Google no devolvio un codigo valido.');
        }

        $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => config('services.google.redirect'),
            'grant_type' => 'authorization_code',
        ]);

        if (!$tokenResponse->successful()) {
            return redirect()->route('login.show')->with('google_error', 'No se pudo validar Google Login.');
        }

        $accessToken = $tokenResponse->json('access_token');
        $profileResponse = Http::withToken($accessToken)
            ->get('https://openidconnect.googleapis.com/v1/userinfo');

        if (!$profileResponse->successful()) {
            return redirect()->route('login.show')->with('google_error', 'No se pudo obtener el perfil de Google.');
        }

        $profile = $profileResponse->json();
        $email = $profile['email'] ?? null;
        $name = $profile['name'] ?? 'Administrador';
        $verified = (bool) ($profile['email_verified'] ?? false);

        if (!$email || !$verified) {
            return redirect()->route('login.show')->with('google_error', 'Tu cuenta de Google no tiene un email verificado.');
        }

        $normalizedEmail = strtolower(trim((string) $email));

        if ($intent === 'portal') {
            $clientes = new FileStore('clientes.json');
            $clientPortal = collect($clientes->all())->first(function ($client) use ($normalizedEmail) {
                return strtolower(trim((string) ($client['portal_email'] ?? ''))) === $normalizedEmail
                    || strtolower(trim((string) ($client['contacto_email'] ?? ''))) === $normalizedEmail
                    || strtolower(trim((string) ($client['email'] ?? ''))) === $normalizedEmail;
            });

            if (!$clientPortal) {
                return redirect()->route('portal.magic-link');
            }

            $portalUser = User::query()->get()->first(function (User $candidate) use ($normalizedEmail) {
                return strtolower(trim((string) $candidate->email)) === $normalizedEmail;
            });

            if (!$portalUser) {
                $portalUser = User::create([
                    'name' => $name !== '' ? $name : (string) ($clientPortal['nombre'] ?? 'Cliente'),
                    'email' => $normalizedEmail,
                    'password' => Hash::make(Str::random(32)),
                    'role' => 'client',
                    'cliente_id' => (string) ($clientPortal['id'] ?? ''),
                ]);
            } else {
                if (($portalUser->role ?? '') !== 'client') {
                    return redirect()->route('portal.magic-link');
                }
                if (empty($portalUser->cliente_id)) {
                    $portalUser->cliente_id = (string) ($clientPortal['id'] ?? '');
                    $portalUser->save();
                }
            }

            Auth::login($portalUser, true);
            $request->session()->regenerate();

            return redirect()->intended(route('portal.auth.dashboard'));
        }

        $allowedAdminEmails = collect(explode(',', (string) config('services.google.admin_emails', '')))
            ->map(fn ($item) => strtolower(trim($item)))
            ->filter()
            ->values();

        $user = User::query()->get()->first(function (User $candidate) use ($normalizedEmail) {
            return strtolower(trim((string) $candidate->email)) === $normalizedEmail;
        });

        if (!$user) {
            $teamUser = $this->findTeamUserByEmail($normalizedEmail);
            if ($teamUser) {
                $user = $this->syncTeamUserToLoginUser($teamUser, $name);
            }
        }

        if (!$user && $allowedAdminEmails->contains($normalizedEmail)) {
            $user = User::create([
                'name' => $name,
                'email' => $normalizedEmail,
                'password' => Hash::make(Str::random(32)),
                'role' => 'admin',
            ]);
        }

        if ($user) {
            Auth::login($user, true);
            $request->session()->regenerate();

            return redirect()->intended($this->redirectAfterLogin($user));
        }

        $clientes = new FileStore('clientes.json');
        $clientPortal = collect($clientes->all())->first(function ($client) use ($normalizedEmail) {
            return strtolower(trim((string) ($client['portal_email'] ?? ''))) === $normalizedEmail
                || strtolower(trim((string) ($client['contacto_email'] ?? ''))) === $normalizedEmail
                || strtolower(trim((string) ($client['email'] ?? ''))) === $normalizedEmail;
        });

        if ($clientPortal) {
            $id = $clientPortal['id'];
            $token = hash_hmac('sha256', (string) $id, (string) config('app.key') . '|portal');

            return redirect()->route('portal.dashboard', compact('id', 'token'));
        }

        Log::warning('Google login no autorizado', [
            'email' => $normalizedEmail,
            'allowed_admin_emails' => $allowedAdminEmails->values()->all(),
            'has_user_match' => (bool) $user,
            'has_client_match' => (bool) $clientPortal,
        ]);

        return redirect()->route('login.show')->with('google_error', 'La cuenta de Google detectada (' . $normalizedEmail . ') no esta autorizada en este CRM.');
    }

    private function findTeamUserByEmail(string $email): ?array
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return null;
        }

        return collect((new FileStore('users.json'))->all())->first(function ($user) use ($email) {
            return (bool) ($user['active'] ?? true)
                && strtolower(trim((string) ($user['email'] ?? ''))) === $email;
        }) ?: null;
    }

    private function syncTeamUserToLoginUser(array $teamUser, string $fallbackName = ''): User
    {
        $email = strtolower(trim((string) ($teamUser['email'] ?? '')));
        $user = User::firstOrNew(['email' => $email]);
        $user->name = (string) ($teamUser['name'] ?? ($fallbackName !== '' ? $fallbackName : $email));
        $user->email = $email;
        if (!$user->exists || empty($user->password)) {
            $user->password = (string) ($teamUser['password'] ?? Hash::make(Str::random(32)));
        }
        $user->role = (string) ($teamUser['role'] ?? 'employee');
        $user->cliente_id = null;
        $user->must_change_password = false;
        $user->save();

        return $user;
    }

    private function syncPasswordInTeamStore(string $email, string $hashedPassword): void
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return;
        }

        $store = new FileStore('users.json');
        $users = $store->all();
        $changed = false;
        foreach ($users as &$teamUser) {
            if (strtolower(trim((string) ($teamUser['email'] ?? ''))) === $email) {
                $teamUser['password'] = $hashedPassword;
                $teamUser['updated_at'] = now()->toISOString();
                $changed = true;
                break;
            }
        }
        unset($teamUser);

        if ($changed) {
            $store->save($users);
        }
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        $credentials['email'] = strtolower(trim((string) $credentials['email']));

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended($this->redirectAfterLogin(Auth::user()));
        }

        $teamUser = collect((new FileStore('users.json'))->all())
            ->first(function ($user) use ($credentials) {
                return (bool) ($user['active'] ?? true)
                    && strtolower(trim((string) ($user['email'] ?? ''))) === $credentials['email']
                    && Hash::check($credentials['password'], (string) ($user['password'] ?? ''));
            });

        if ($teamUser) {
            $user = User::updateOrCreate(
                ['email' => $credentials['email']],
                [
                    'name' => (string) ($teamUser['name'] ?? $credentials['email']),
                    'password' => (string) ($teamUser['password'] ?? ''),
                    'role' => (string) ($teamUser['role'] ?? 'employee'),
                    'cliente_id' => null,
                    'must_change_password' => false,
                ]
            );

            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            return redirect()->intended($this->redirectAfterLogin($user));
        }

        $demoEmail = env('DEMO_LOGIN_EMAIL');
        $demoPass = env('DEMO_LOGIN_PASSWORD');
        if ($demoEmail && $demoPass && $credentials['email'] === $demoEmail && $credentials['password'] === $demoPass) {
            $request->session()->put('user', [
                'id' => 'demo',
                'email' => $demoEmail,
                'name' => 'Demo User',
                'role' => 'admin',
            ]);

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'Credenciales incorrectas',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->forget(['user', 'user_id', 'demo_user']);

        return redirect()->route('login.show');
    }
}
