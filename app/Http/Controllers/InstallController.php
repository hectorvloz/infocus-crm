<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Repositories\FileStore;

class InstallController extends Controller
{
    private function getRequirements()
    {
        return [
            'php' => [
                'label' => 'PHP >= 8.2',
                'current' => PHP_VERSION,
                'ok' => version_compare(PHP_VERSION, '8.2.0', '>=')
            ],
            'extensions' => [
                ['label' => 'OpenSSL', 'ok' => extension_loaded('openssl')],
                ['label' => 'Mbstring', 'ok' => extension_loaded('mbstring')],
                ['label' => 'PDO', 'ok' => extension_loaded('pdo')],
                ['label' => 'PDO MySQL', 'ok' => extension_loaded('pdo_mysql')],
                ['label' => 'Tokenizer', 'ok' => extension_loaded('tokenizer')],
                ['label' => 'JSON', 'ok' => extension_loaded('json')],
            ],
            'permissions' => [
                ['label' => 'storage/', 'ok' => is_writable(storage_path())],
                ['label' => 'storage/framework/', 'ok' => is_writable(storage_path('framework'))],
                ['label' => 'storage/framework/sessions/', 'ok' => is_writable(storage_path('framework/sessions'))],
                ['label' => 'bootstrap/cache/', 'ok' => is_writable(base_path('bootstrap/cache'))],
            ],
        ];
    }

    public function show()
    {
        $requirements = $this->getRequirements();
        return view('install', [
            'requirements' => $requirements,
            'initialStep' => 1,
            'formData' => [],
        ]);
    }

    public function testDb(Request $request)
    {
        $data = $request->validate([
            'db_host' => 'required|string',
            'db_port' => 'required|numeric',
            'db_name' => 'required|string',
            'db_user' => 'required|string',
            'db_pass' => 'nullable|string',
        ]);

        config([
            'database.connections.mysql.host' => $data['db_host'],
            'database.connections.mysql.port' => $data['db_port'],
            'database.connections.mysql.database' => $data['db_name'],
            'database.connections.mysql.username' => $data['db_user'],
            'database.connections.mysql.password' => $data['db_pass'] ?? '',
        ]);

        try {
            DB::purge('mysql');
            DB::connection('mysql')->getPdo();
            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function store(Request $request)
    {
        try {
            if ((bool) config('app.installed', false)) {
                return response()->view('install', [
                    'requirements' => $this->getRequirements(),
                    'errorMessage' => 'La aplicación ya está instalada.',
                    'initialStep' => 4,
                    'formData' => [],
                ], 422);
            }
            Artisan::call('config:clear');
            try {
                Artisan::call('cache:clear');
            } catch (\Throwable $e) {
                // Ignore cache clear failures during first-time install.
            }
            $data = $request->validate([
                'db_host' => 'required|string',
                'db_port' => 'required|numeric',
                'db_name' => 'required|string',
                'db_user' => 'required|string',
                'db_pass' => 'nullable|string',
                'company_name' => 'required|string',
                'admin_name' => 'required|string',
                'admin_email' => 'required|email',
                'admin_password' => 'required|string|min:8',
                'app_url' => 'required|url',
            ]);

            $data['db_host'] = trim($data['db_host']);
            $data['db_name'] = trim($data['db_name']);
            $data['db_user'] = trim($data['db_user']);
            
            config([
                'database.connections.mysql.host' => $data['db_host'],
                'database.connections.mysql.port' => $data['db_port'],
                'database.connections.mysql.database' => $data['db_name'],
                'database.connections.mysql.username' => $data['db_user'],
                'database.connections.mysql.password' => $data['db_pass'] ?? '',
            ]);
            config(['database.default' => 'mysql']);
            DB::setDefaultConnection('mysql');
            DB::purge('mysql');
            DB::connection('mysql')->getPdo();

            Artisan::call('migrate', ['--force' => true]);

            if (!User::where('email', $data['admin_email'])->exists()) {
                User::create([
                    'name' => $data['admin_name'],
                    'email' => $data['admin_email'],
                    'password' => Hash::make($data['admin_password']),
                ]);
            }

            $settingsStore = new FileStore('settings.json');
            $settings = $settingsStore->find('settings') ?: [];
            $settings['company_name'] = $data['company_name'];
            $settingsStore->update('settings', array_merge(['id' => 'settings'], $settings));

            // Write .env file
            $appKey = 'base64:'.base64_encode(random_bytes(32));
            $this->writeEnv([
                'APP_NAME' => $data['company_name'],
                'APP_ENV' => 'production',
                'APP_KEY' => $appKey,
                'APP_DEBUG' => 'false',
                'APP_URL' => $data['app_url'],
                'LOG_CHANNEL' => 'stack',
                'DB_CONNECTION' => 'mysql',
                'DB_HOST' => $data['db_host'],
                'DB_PORT' => $data['db_port'],
                'DB_DATABASE' => $data['db_name'],
                'DB_USERNAME' => $data['db_user'],
                'DB_PASSWORD' => $data['db_pass'] ?? '',
                'SESSION_DRIVER' => 'database',
                'APP_INSTALLED' => 'true',
            ]);
            
            Artisan::call('config:clear');

            File::put(storage_path('app/installed.lock'), now()->toDateTimeString());

            return response()->view('install-success', [
                'company_name' => $data['company_name'],
                'app_url' => $data['app_url'],
                'db_host' => $data['db_host'],
                'db_name' => $data['db_name'],
                'admin_email' => $data['admin_email'],
            ]);

        } catch (\Throwable $e) {
            Log::error('Installation Error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->view('install', [
                'requirements' => $this->getRequirements(),
                'errorMessage' => 'Error durante la instalación: ' . $e->getMessage(),
                'initialStep' => 4,
                'formData' => $request->only([
                    'db_host',
                    'db_port',
                    'db_name',
                    'db_user',
                    'app_url',
                    'company_name',
                    'admin_name',
                    'admin_email',
                ]),
            ], 422);
        }
    }

    private function writeEnv(array $data)
    {
        $path = base_path('.env');
        if (!File::exists($path)) {
            // Try to copy .env.example
            if (File::exists(base_path('.env.example'))) {
                File::copy(base_path('.env.example'), $path);
            } else {
                // Create empty if not exists
                File::put($path, '');
            }
        }

        $env = File::get($path);
        foreach ($data as $key => $value) {
            // If value contains spaces, quote it
            if (str_contains($value, ' ')) {
                $value = '"' . $value . '"';
            }
            
            // Check if key exists
            if (preg_match("/^{$key}=/m", $env)) {
                // Replace
                $env = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $env);
            } else {
                // Append
                $env .= "\n{$key}={$value}";
            }
        }
        File::put($path, $env);
    }

}
