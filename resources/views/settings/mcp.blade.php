@extends('layouts.settings')

@section('title', 'MCP · Asistentes de IA')

@section('content')
@php
    $tok = $newToken['token'] ?? 'TU_TOKEN';
    $setups = [
        ['Claude Code', 'Terminal o pestaña Code de la app de Claude.', "claude mcp add --transport http infocus {$endpoint} --header \"Authorization: Bearer {$tok}\""],
        ['Codex', 'Agrega esto a ~/.codex/config.toml y guarda el token en la variable INFOCUS_MCP_TOKEN.', "[mcp_servers.infocus]\nurl = \"{$endpoint}\"\nbearer_token_env_var = \"INFOCUS_MCP_TOKEN\"\n\n# En tu terminal (o en ~/.zshrc):\nexport INFOCUS_MCP_TOKEN=\"{$tok}\""],
        ['Gemini CLI', 'Agrega esto a ~/.gemini/settings.json.', json_encode(['mcpServers' => ['infocus' => ['httpUrl' => $endpoint, 'headers' => ['Authorization' => "Bearer {$tok}"]]]], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)],
        ['Claude Desktop (chat)', 'Configuración → Desarrollador → Editar configuración (claude_desktop_config.json). Requiere Node.js.', json_encode(['mcpServers' => ['infocus' => ['command' => 'npx', 'args' => ['-y', 'mcp-remote', $endpoint, '--header', 'Authorization:${AUTH}'], 'env' => ['AUTH' => "Bearer {$tok}"]]]], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)],
    ];
@endphp

<div class="mb-8">
    <h2 class="text-2xl font-bold text-slate-900">MCP · Asistentes de IA</h2>
    <p class="text-slate-500">Conecta Claude, Codex, Gemini o ChatGPT a tu Infocus para consultar y trabajar desde el chat.</p>
</div>

@if (!($setup['packages'] && $setup['tables'] && $setup['keys']))
    <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-6">
        <p class="font-semibold text-amber-900">Falta activar el MCP en este servidor</p>
        <ul class="mt-2 space-y-1 text-sm text-amber-800">
            <li>{{ $setup['packages'] ? '✓' : '✗' }} Paquetes registrados</li>
            <li>{{ $setup['tables'] ? '✓' : '✗' }} Tablas de OAuth (claude.ai y ChatGPT)</li>
            <li>{{ $setup['keys'] ? '✓' : '✗' }} Llaves de OAuth</li>
        </ul>
        <form method="POST" action="{{ route('settings.mcp.activate') }}" class="mt-4">
            @csrf
            <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Activar MCP</button>
        </form>
        <p class="mt-2 text-xs text-amber-700">Hazlo después de cada despliegue que traiga paquetes nuevos. Se puede repetir sin riesgo.</p>
    </div>
@endif

@if ($newToken)
    <div class="mb-6 rounded-2xl border-2 border-amber-300 bg-amber-50 p-6">
        <p class="font-semibold text-amber-900">Token de "{{ $newToken['name'] }}" — cópialo ahora, no se volverá a mostrar.</p>
        <div class="mt-3 flex gap-2">
            <input id="mcp-token" readonly value="{{ $newToken['token'] }}" class="w-full rounded-xl border border-amber-300 bg-white px-3 py-2 font-mono text-sm">
            <button type="button" data-copy="#mcp-token" class="rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white">Copiar</button>
        </div>
        <p class="mt-2 text-xs text-amber-800">Las instrucciones de abajo ya lo incluyen. Guárdalo como una contraseña.</p>
    </div>
@endif

<div class="grid gap-6 lg:grid-cols-2">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="font-semibold text-slate-900">Nueva conexión con token</h3>
        <p class="mt-1 text-sm text-slate-500">Para Claude Code, Codex, Gemini CLI y Claude Desktop. Crea una por herramienta.</p>
        <form method="POST" action="{{ route('settings.mcp.store') }}" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Nombre</label>
                <input name="name" required maxlength="60" placeholder="Ej. Claude Code de Héctor" class="block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Acceso</label>
                    <select name="access" class="block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                        <option value="completo">Completo</option>
                        <option value="lectura">Solo lectura</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Vence en</label>
                    <select name="days" class="block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                        <option value="90">90 días</option>
                        <option value="30">30 días</option>
                        <option value="365">1 año</option>
                    </select>
                </div>
            </div>
            <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Crear token</button>
        </form>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="font-semibold text-slate-900">claude.ai, ChatGPT y Gemini (web y celular)</h3>
        <p class="mt-1 text-sm text-slate-500">No necesitan token: se conectan iniciando sesión en Infocus.</p>
        <ol class="mt-4 list-decimal space-y-2 pl-5 text-sm text-slate-700">
            <li><strong>claude.ai:</strong> Configuración → Conectores → Agregar conector personalizado.</li>
            <li><strong>ChatGPT:</strong> Configuración → Aplicaciones y conectores → Modo desarrollador → Crear.</li>
            <li><strong>Gemini:</strong> Configuración → Apps conectadas → App personalizada → MCP en la nube.</li>
            <li>Pega esta URL y pulsa conectar:</li>
        </ol>
        <div class="mt-3 flex gap-2">
            <input id="mcp-url" readonly value="{{ $endpoint }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 font-mono text-sm">
            <button type="button" data-copy="#mcp-url" class="rounded-xl border border-slate-300 px-4 text-sm font-semibold">Copiar</button>
        </div>
        <p class="mt-3 text-sm text-slate-500">Se abrirá Infocus para que autorices. Las conexiones OAuth tienen acceso completo.</p>
    </div>
</div>

<div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <h3 class="font-semibold text-slate-900">Cómo conectar cada asistente</h3>
    <div class="mt-4 space-y-5">
        @foreach ($setups as $i => [$title, $hint, $code])
            <div>
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-slate-800">{{ $title }}</p>
                    <button type="button" data-copy="#mcp-setup-{{ $i }}" class="text-xs font-semibold text-slate-600 underline">Copiar</button>
                </div>
                <p class="text-xs text-slate-500">{{ $hint }}</p>
                <pre id="mcp-setup-{{ $i }}" class="mt-2 overflow-x-auto rounded-xl bg-slate-900 p-4 text-xs text-slate-100">{{ $code }}</pre>
            </div>
        @endforeach
    </div>
</div>

<div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <h3 class="font-semibold text-slate-900">Conexiones activas</h3>
    <div class="mt-4 divide-y divide-slate-100">
        @forelse ($tokens as $t)
            <div class="flex items-center justify-between py-3 text-sm">
                <div>
                    <p class="font-medium text-slate-900">{{ $t['name'] }} <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ $t['access'] === 'completo' ? 'Completo' : 'Solo lectura' }}</span></p>
                    <p class="text-xs text-slate-500">Token …{{ $t['token_hint'] }} · último uso: {{ $t['last_used_at'] ? \Illuminate\Support\Carbon::parse($t['last_used_at'])->diffForHumans() : 'nunca' }} · vence: {{ $t['expires_at'] ? \Illuminate\Support\Carbon::parse($t['expires_at'])->format('d/m/Y') : 'no vence' }}</p>
                </div>
                <form method="POST" action="{{ route('settings.mcp.destroy', $t['id']) }}" onsubmit="return confirm('¿Revocar esta conexión? El asistente dejará de funcionar de inmediato.')">
                    @csrf @method('DELETE')
                    <button class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50">Revocar</button>
                </form>
            </div>
        @empty
            <p class="py-2 text-sm text-slate-500">No hay conexiones con token.</p>
        @endforelse
        @foreach ($oauth as $o)
            <div class="flex items-center justify-between py-3 text-sm">
                <div>
                    <p class="font-medium text-slate-900">{{ $o['client'] }} <span class="ml-1 rounded-full bg-indigo-50 px-2 py-0.5 text-xs text-indigo-700">OAuth</span></p>
                    <p class="text-xs text-slate-500">Autorizado {{ \Illuminate\Support\Carbon::parse($o['last'])->diffForHumans() }}</p>
                </div>
                <form method="POST" action="{{ route('settings.mcp.oauth.revoke', $o['client_id']) }}" onsubmit="return confirm('¿Revocar el acceso de esta aplicación?')">
                    @csrf @method('DELETE')
                    <button class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50">Revocar</button>
                </form>
            </div>
        @endforeach
    </div>
</div>

<div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <h3 class="font-semibold text-slate-900">Actividad reciente</h3>
    <div class="mt-4 overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="text-xs uppercase text-slate-500"><tr><th class="py-2">Cuándo</th><th>Conexión</th><th>Herramienta</th><th>Resultado</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($activity as $a)
                    <tr>
                        <td class="py-2 text-slate-500">{{ \Illuminate\Support\Carbon::parse($a['at'])->timezone(config('app.timezone'))->format('d/m H:i') }}</td>
                        <td>{{ $a['connection'] }}</td>
                        <td class="font-mono text-xs">{{ $a['tool'] }}</td>
                        <td class="{{ str_starts_with($a['result'], 'ok') ? 'text-emerald-700' : 'text-red-600' }}">{{ $a['result'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-3 text-slate-500">Todavía no hay actividad.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
document.querySelectorAll('[data-copy]').forEach(btn => btn.addEventListener('click', () => {
    const el = document.querySelector(btn.dataset.copy);
    navigator.clipboard.writeText(el.value ?? el.textContent).then(() => {
        const label = btn.textContent; btn.textContent = 'Copiado'; setTimeout(() => btn.textContent = label, 1500);
    });
}));
</script>
@endsection
