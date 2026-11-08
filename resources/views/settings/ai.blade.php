@extends('layouts.settings')

@section('title', 'IA - Infocus CRM')

@section('content')
  @php
    $providerLabels = [
      'gemini' => 'Google Gemini',
      'openai' => 'OpenAI / ChatGPT',
      'deepseek' => 'DeepSeek',
    ];
    $modelOptions = [
      'gemini' => [
        ['value' => 'auto', 'label' => 'Automático recomendado'],
        ['value' => 'gemini-2.5-flash', 'label' => 'Gemini 2.5 Flash'],
        ['value' => 'gemini-2.5-flash-lite', 'label' => 'Gemini 2.5 Flash Lite'],
        ['value' => 'gemini-2.0-flash', 'label' => 'Gemini 2.0 Flash'],
      ],
      'openai' => [
        ['value' => 'auto', 'label' => 'Automático recomendado'],
        ['value' => 'gpt-4o-mini', 'label' => 'GPT-4o mini'],
        ['value' => 'gpt-4.1-mini', 'label' => 'GPT-4.1 mini'],
      ],
      'deepseek' => [
        ['value' => 'auto', 'label' => 'Automático recomendado'],
        ['value' => 'deepseek-flash', 'label' => 'DeepSeek V4.1 Flash'],
        ['value' => 'deepseek-flash-thinking', 'label' => 'DeepSeek V4.1 Flash · Razonamiento'],
      ],
    ];
    $selectedProvider = old('ai_provider', $settings['ai_provider'] ?? 'gemini');
    $selectedProvider = array_key_exists($selectedProvider, $providerLabels) ? $selectedProvider : 'gemini';
    $selectedModel = old('ai_model', $settings['ai_model'] ?? 'auto');
    $selectedModel = in_array($selectedModel, ['gemini-1.5-flash', 'gemini-1.5-pro'], true) ? 'auto' : $selectedModel;
    if ($selectedProvider === 'deepseek') {
      $selectedModel = match ($selectedModel) {
        'deepseek-chat' => 'deepseek-flash',
        'deepseek-reasoner' => 'deepseek-flash-thinking',
        default => $selectedModel,
      };
    }
    $providerModelValues = collect($modelOptions[$selectedProvider])->pluck('value')->all();
    $selectedModel = in_array($selectedModel, $providerModelValues, true) ? $selectedModel : 'auto';
  @endphp

  <div class="mb-8">
    <h1 class="text-4xl font-extrabold text-slate-900">Configuración IA</h1>
    <p class="mt-2 text-lg text-slate-500">Conecta el asistente del CRM con Gemini, OpenAI o DeepSeek por API.</p>
  </div>

  @php
    $activeAiTab = in_array(request('tab'), ['memoria', 'whatsapp'], true) ? request('tab') : 'conexion';
    $memoryGroups = [
      'project' => ['label' => 'Proyectos', 'empty' => 'Todavía no hay memorias vinculadas a proyectos.'],
      'client' => ['label' => 'Clientes', 'empty' => 'Todavía no hay memorias vinculadas a clientes.'],
      'user' => ['label' => 'De mí', 'empty' => 'Todavía no hay memorias personales.'],
      'company' => ['label' => 'Mi empresa', 'empty' => 'Todavía no hay memorias de empresa.'],
    ];
  @endphp

  <div class="mb-5 inline-flex rounded-2xl border border-slate-200 bg-white p-1 shadow-sm">
    <button type="button" data-ai-settings-tab="conexion" class="ai-settings-tab rounded-xl px-4 py-2 text-sm font-extrabold {{ $activeAiTab === 'conexion' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-50' }}">Conexión</button>
    <button type="button" data-ai-settings-tab="memoria" class="ai-settings-tab rounded-xl px-4 py-2 text-sm font-extrabold {{ $activeAiTab === 'memoria' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-50' }}">Memoria</button>
    <button type="button" data-ai-settings-tab="whatsapp" class="ai-settings-tab rounded-xl px-4 py-2 text-sm font-extrabold {{ $activeAiTab === 'whatsapp' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-50' }}">WhatsApp</button>
  </div>

  <form method="POST" action="{{ route('settings.ai.update') }}" data-ai-settings-panel="conexion" class="{{ $activeAiTab === 'conexion' ? '' : 'hidden' }} bg-white rounded-3xl border border-slate-200 shadow-sm p-6 md:p-8 space-y-7">
    @csrf
    @method('PUT')

    <div class="rounded-2xl border border-blue-100 bg-blue-50 px-5 py-4 text-blue-700">
      <div class="flex gap-3">
        <svg class="w-5 h-5 mt-1 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 21a9 9 0 100-18 9 9 0 000 18z"/>
        </svg>
        <p class="text-sm leading-relaxed">
          Recomendado para empezar: Gemini o DeepSeek con modelo automático. ChatGPT gratuito de la web no se puede vincular por API; OpenAI usa facturación separada.
        </p>
      </div>
    </div>

    <div class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 px-5 py-4">
      <div>
        <div class="font-extrabold text-slate-900">Activar asistente</div>
        <div class="text-sm text-slate-500">Si está apagado, el chat queda visible pero no llama al proveedor.</div>
      </div>
      <label class="relative inline-flex cursor-pointer items-center">
        <input type="checkbox" name="ai_enabled" value="1" class="peer sr-only" {{ !empty($settings['ai_enabled']) ? 'checked' : '' }}>
        <span class="h-8 w-14 rounded-full bg-slate-200 transition peer-checked:bg-[#ecfe88]"></span>
        <span class="absolute left-1 top-1 h-6 w-6 rounded-full bg-white shadow transition peer-checked:translate-x-6"></span>
      </label>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <div>
        <label class="block text-sm font-bold text-slate-700 mb-2">Proveedor</label>
        <select name="ai_provider" id="aiProvider" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 font-semibold">
          @foreach($providerLabels as $value => $label)
            <option value="{{ $value }}" {{ $selectedProvider === $value ? 'selected' : '' }}>{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="block text-sm font-bold text-slate-700 mb-2">Modelo</label>
        <select name="ai_model" id="aiModel" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 font-semibold">
          @foreach($modelOptions[$selectedProvider] as $option)
            <option value="{{ $option['value'] }}" {{ $selectedModel === $option['value'] ? 'selected' : '' }}>{{ $option['label'] }}</option>
          @endforeach
        </select>
        <p class="mt-2 text-sm text-slate-500">Automático usa el modelo estable del proveedor elegido.</p>
      </div>
    </div>

    <div>
      <label class="block text-sm font-bold text-slate-700 mb-2">API key</label>
      <input id="aiApiKeyInput" name="ai_api_key" type="password" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 font-semibold" placeholder="Dejar vacío para mantener la actual">
      <p class="mt-2 text-sm text-slate-500">Estado: <span id="aiApiKeyStatus" class="font-bold text-slate-700">{{ $settings['ai_api_key_preview'] ?? 'Sin configurar' }}</span></p>
    </div>

    <div class="rounded-2xl border border-slate-200 px-5 py-4">
      <label class="flex items-start gap-3 cursor-pointer">
        <input type="checkbox" name="ai_send_visible_context" value="1" class="mt-1 rounded text-lime-500 focus:ring-lime-300" {{ ($settings['ai_send_visible_context'] ?? false) ? 'checked' : '' }}>
        <span>
          <span class="block font-extrabold text-slate-900">Permitir contexto visible cuando lo pida</span>
          <span class="block text-sm text-slate-500">La IA solo recibe página actual o texto seleccionado si el mensaje lo solicita, filtrado antes de enviarse.</span>
        </span>
      </label>
    </div>

    <div class="rounded-2xl border border-slate-200 px-5 py-4">
      <label class="flex items-start gap-3 cursor-pointer">
        <input type="checkbox" name="ai_tools_enabled" value="1" class="mt-1 rounded text-lime-500 focus:ring-lime-300" {{ ($settings['ai_tools_enabled'] ?? false) ? 'checked' : '' }}>
        <span>
          <span class="block font-extrabold text-slate-900">Asistente con herramientas (beta)</span>
          <span class="block text-sm text-slate-500">La IA consulta los datos del CRM con las mismas herramientas del MCP antes de responder, y puede crear o editar recordatorios, notas, tareas y leads cuando se lo pidas (se puede deshacer). Para enviar correos, facturas o contratos, registrar pagos o programar reuniones te muestra la vista previa y solo lo hace si respondes "sí". Funciona con DeepSeek, OpenAI y Gemini.</span>
        </span>
      </label>
    </div>

    <div>
      <label class="block text-sm font-bold text-slate-700 mb-2">Instrucción interna</label>
      <textarea name="ai_system_prompt" rows="5" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 leading-relaxed" placeholder="Ej: Responde con tono cercano, prioriza pasos cortos, no hagas cambios sin confirmación.">{{ old('ai_system_prompt', $settings['ai_system_prompt'] ?? '') }}</textarea>
    </div>

    <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
      <a href="{{ route('dashboard') }}" class="rounded-2xl border border-slate-200 px-6 py-3 font-bold text-slate-600 hover:bg-slate-50">Cancelar</a>
      <button type="submit" class="rounded-2xl bg-[#ecfe88] px-7 py-3 font-extrabold text-slate-950 hover:bg-[#d9ef60]">Guardar IA</button>
    </div>
  </form>

  <section data-ai-settings-panel="memoria" class="{{ $activeAiTab === 'memoria' ? '' : 'hidden' }} space-y-5">
    <div class="rounded-3xl border border-fuchsia-100 bg-fuchsia-50 px-5 py-4 text-sm leading-relaxed text-fuchsia-900">
      <div class="font-black text-slate-900">Cómo funciona la memoria</div>
      <p class="mt-1">La IA guarda preferencias cuando dices “recuerda” o “ten en cuenta”. En un proyecto, las reglas de ese proyecto quedan vinculadas a él; las preferencias del cliente se vinculan al cliente y se pueden usar en sus otros proyectos. Puedes editar o borrar cualquier memoria aquí.</p>
    </div>

    @foreach($memoryGroups as $scope => $group)
      @php $items = $aiMemories[$scope] ?? []; @endphp
      <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex items-center justify-between gap-3">
          <h2 class="text-xl font-black text-slate-900">{{ $group['label'] }}</h2>
          <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600">{{ count($items) }}</span>
        </div>

        @forelse($items as $memory)
          <div class="mb-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 last:mb-0">
            <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
              <div class="min-w-0">
                <div class="truncate text-sm font-black text-slate-900">{{ $memory['entity_name'] ?? ($scope === 'company' ? 'Mi empresa' : 'Usuario') }}</div>
                <div class="text-xs font-semibold text-slate-400">Actualizada: {{ !empty($memory['updated_at']) ? \Carbon\Carbon::parse($memory['updated_at'])->format('d/m/Y H:i') : '—' }}</div>
              </div>
              <form method="POST" action="{{ route('settings.ai.memory.delete', $memory['id'] ?? '') }}" onsubmit="return confirm('¿Eliminar esta memoria?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-rose-200 bg-white text-rose-500 hover:bg-rose-50" title="Eliminar memoria" aria-label="Eliminar memoria">
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-8 0h10"/></svg>
                </button>
              </form>
            </div>
            <form method="POST" action="{{ route('settings.ai.memory.update', $memory['id'] ?? '') }}" class="space-y-2">
              @csrf
              @method('PUT')
              <textarea name="text" rows="3" maxlength="700" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold leading-relaxed text-slate-800 focus:border-fuchsia-300 focus:ring-fuchsia-200">{{ $memory['text'] ?? '' }}</textarea>
              <div class="flex justify-end">
                <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-xs font-black text-white hover:bg-slate-800">Guardar memoria</button>
              </div>
            </form>
          </div>
        @empty
          <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm font-semibold text-slate-400">{{ $group['empty'] }}</div>
        @endforelse
      </div>
    @endforeach
  </section>

  @php
    $waProvider = old('whatsapp_provider', $settings['whatsapp_provider'] ?? 'twilio');
    $waProviders = \App\Support\WhatsApp\WhatsAppChannel::PROVIDERS;
    $waInput = 'w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900';
    $waMono = $waInput . ' font-mono text-sm';
    $waTextmebotKey = (string) ($settings['whatsapp_textmebot_webhook_key'] ?? '');
    $waVerifyToken = (string) ($settings['whatsapp_meta_verify_token'] ?? '');
  @endphp
  <section data-ai-settings-panel="whatsapp" class="{{ $activeAiTab === 'whatsapp' ? '' : 'hidden' }} space-y-5">
    <div class="rounded-3xl border border-slate-200 bg-slate-50 px-5 py-4 text-sm leading-relaxed text-slate-700">
      <div class="font-black text-slate-900">Habla con el asistente por WhatsApp</div>
      <p class="mt-1">Infocus recibe tus mensajes y los contesta el mismo asistente con herramientas, con los permisos del usuario que elijas. Solo responde a tu número. Para correos, facturas, pagos, reuniones y contratos te manda la vista previa y lo hace solo si contestas "sí". Elige el proveedor y llena solo su sección.</p>
    </div>

    <form method="POST" action="{{ route('settings.ai.whatsapp.update') }}" class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 md:p-8 space-y-6">
      @csrf
      @method('PUT')

      <label class="flex items-start gap-3 cursor-pointer">
        <input type="checkbox" name="whatsapp_enabled" value="1" class="mt-1 rounded text-lime-500 focus:ring-lime-300" {{ ($settings['whatsapp_enabled'] ?? false) ? 'checked' : '' }}>
        <span>
          <span class="block font-extrabold text-slate-900">Activar WhatsApp</span>
          <span class="block text-sm text-slate-500">Requiere el "Asistente con herramientas" activo en la pestaña Conexión.</span>
        </span>
      </label>

      <div class="grid gap-5 md:grid-cols-2">
        <div class="md:col-span-2">
          <label for="waProvider" class="block text-sm font-bold text-slate-700 mb-2">Proveedor</label>
          <select id="waProvider" name="whatsapp_provider" class="{{ $waInput }} font-semibold">
            @foreach($waProviders as $value => $label)
              <option value="{{ $value }}" {{ $waProvider === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="block text-sm font-bold text-slate-700 mb-2">Tu WhatsApp (el único que puede hablarle)</label>
          <input name="whatsapp_owner" value="{{ old('whatsapp_owner', $settings['whatsapp_owner'] ?? '') }}" class="{{ $waInput }}" placeholder="+573001234567">
        </div>
        <div>
          <label class="block text-sm font-bold text-slate-700 mb-2">Responder como</label>
          <select name="whatsapp_user_id" class="{{ $waInput }} font-semibold">
            <option value="">Elegir usuario administrador</option>
            @foreach($whatsappAdmins ?? [] as $admin)
              <option value="{{ $admin->id }}" {{ (string) old('whatsapp_user_id', $settings['whatsapp_user_id'] ?? '') === (string) $admin->id ? 'selected' : '' }}>{{ $admin->name }} · {{ $admin->email }}</option>
            @endforeach
          </select>
          @error('whatsapp_user_id')<p class="mt-2 text-sm font-bold text-rose-600">{{ $message }}</p>@enderror
        </div>
      </div>

      {{-- Twilio --}}
      <details data-wa-provider="twilio" class="rounded-2xl border border-slate-200 px-5 py-4 {{ $waProvider === 'twilio' ? '' : 'hidden' }}" {{ $waProvider === 'twilio' ? 'open' : '' }}>
        <summary class="cursor-pointer font-extrabold text-slate-900">Twilio</summary>
        <div class="mt-4 grid gap-5 md:grid-cols-2">
          <div class="md:col-span-2 text-sm text-slate-600">
            En Twilio, en el número de WhatsApp o en el sandbox (requiere cuenta con upgrade), pon como webhook de mensajes entrantes, método POST:
            <code class="mt-2 block break-all rounded-xl bg-slate-50 px-3 py-2 font-mono text-xs text-slate-900 border border-slate-200">{{ url('/webhooks/whatsapp') }}</code>
          </div>
          <div>
            <label class="block text-sm font-bold text-slate-700 mb-2">Account SID</label>
            <input name="whatsapp_twilio_sid" value="{{ old('whatsapp_twilio_sid', $settings['whatsapp_twilio_sid'] ?? '') }}" class="{{ $waMono }}" placeholder="AC..." autocomplete="off">
            @error('whatsapp_twilio_sid')<p class="mt-2 text-sm font-bold text-rose-600">{{ $message }}</p>@enderror
          </div>
          <div>
            <label class="block text-sm font-bold text-slate-700 mb-2">Auth Token</label>
            <input name="whatsapp_twilio_token" type="password" class="{{ $waMono }}" placeholder="Dejar vacío para mantener el actual" autocomplete="new-password">
            <p class="mt-2 text-sm text-slate-500">Estado: <span class="font-bold text-slate-700">{{ $settings['whatsapp_token_preview'] ?? 'Sin configurar' }}</span></p>
          </div>
          <div>
            <label class="block text-sm font-bold text-slate-700 mb-2">Número de WhatsApp de Twilio</label>
            <input name="whatsapp_from" value="{{ old('whatsapp_from', $settings['whatsapp_from'] ?? '') }}" class="{{ $waInput }}" placeholder="+14155238886">
          </div>
          <div>
            <label class="block text-sm font-bold text-slate-700 mb-2">Plantilla para avisos (opcional)</label>
            <input name="whatsapp_template_sid" value="{{ old('whatsapp_template_sid', $settings['whatsapp_template_sid'] ?? '') }}" class="{{ $waMono }}" placeholder="HX..." autocomplete="off">
            @error('whatsapp_template_sid')<p class="mt-2 text-sm font-bold text-rose-600">{{ $message }}</p>@enderror
          </div>
          <p class="md:col-span-2 text-sm text-slate-500">Para avisos programados fuera de las 24 horas desde tu último mensaje: crea en Twilio (Content Template Builder) una plantilla de utilidad con el texto "Aviso de Infocus: @{{1}}" y pega su Content SID. Sin plantilla, esos avisos llegan por correo.</p>
        </div>
      </details>

      {{-- Meta --}}
      <details data-wa-provider="meta" class="rounded-2xl border border-slate-200 px-5 py-4 {{ $waProvider === 'meta' ? '' : 'hidden' }}" {{ $waProvider === 'meta' ? 'open' : '' }}>
        <summary class="cursor-pointer font-extrabold text-slate-900">Meta (WhatsApp Cloud API)</summary>
        <div class="mt-4 grid gap-5 md:grid-cols-2">
          <div class="md:col-span-2 text-sm text-slate-600 space-y-2">
            <p>En developers.facebook.com, en tu app &gt; WhatsApp &gt; Configuración, en Webhook pon esta URL y el token de verificación, y suscríbete al campo <strong>messages</strong>:</p>
            <code class="block break-all rounded-xl bg-slate-50 px-3 py-2 font-mono text-xs text-slate-900 border border-slate-200">{{ url('/webhooks/whatsapp/meta') }}</code>
            <p>Token de verificación: @if($waVerifyToken !== '')<code class="break-all rounded-lg bg-slate-50 px-2 py-1 font-mono text-xs text-slate-900 border border-slate-200">{{ $waVerifyToken }}</code>@else<span class="font-bold">se genera al guardar esta configuración.</span>@endif</p>
          </div>
          <div>
            <label class="block text-sm font-bold text-slate-700 mb-2">Phone Number ID</label>
            <input name="whatsapp_meta_phone_id" value="{{ old('whatsapp_meta_phone_id', $settings['whatsapp_meta_phone_id'] ?? '') }}" class="{{ $waMono }}" placeholder="Identificador del número" autocomplete="off">
            @error('whatsapp_meta_phone_id')<p class="mt-2 text-sm font-bold text-rose-600">{{ $message }}</p>@enderror
          </div>
          <div>
            <label class="block text-sm font-bold text-slate-700 mb-2">Access token</label>
            <input name="whatsapp_meta_token" type="password" class="{{ $waMono }}" placeholder="Dejar vacío para mantener el actual" autocomplete="new-password">
            <p class="mt-2 text-sm text-slate-500">Estado: <span class="font-bold text-slate-700">{{ $settings['whatsapp_meta_token_preview'] ?? 'Sin configurar' }}</span>. El temporal vence en 24 horas; para uso real crea uno permanente con un usuario del sistema.</p>
          </div>
          <div>
            <label class="block text-sm font-bold text-slate-700 mb-2">App Secret</label>
            <input name="whatsapp_meta_app_secret" type="password" class="{{ $waMono }}" placeholder="Dejar vacío para mantener el actual" autocomplete="new-password">
            <p class="mt-2 text-sm text-slate-500">Estado: <span class="font-bold text-slate-700">{{ $settings['whatsapp_meta_app_secret_preview'] ?? 'Sin configurar' }}</span>. Está en Configuración de la app &gt; Básica. Sirve para validar la firma de Meta.</p>
          </div>
          <div>
            <label class="block text-sm font-bold text-slate-700 mb-2">Versión de la Graph API</label>
            <input name="whatsapp_meta_version" value="{{ old('whatsapp_meta_version', $settings['whatsapp_meta_version'] ?? \App\Support\WhatsApp\MetaWhatsApp::DEFAULT_VERSION) }}" class="{{ $waMono }}" placeholder="{{ \App\Support\WhatsApp\MetaWhatsApp::DEFAULT_VERSION }}">
            @error('whatsapp_meta_version')<p class="mt-2 text-sm font-bold text-rose-600">{{ $message }}</p>@enderror
          </div>
          <div>
            <label class="block text-sm font-bold text-slate-700 mb-2">Plantilla para avisos (opcional)</label>
            <input name="whatsapp_meta_template_name" value="{{ old('whatsapp_meta_template_name', $settings['whatsapp_meta_template_name'] ?? '') }}" class="{{ $waMono }}" placeholder="aviso_infocus">
            @error('whatsapp_meta_template_name')<p class="mt-2 text-sm font-bold text-rose-600">{{ $message }}</p>@enderror
          </div>
          <div>
            <label class="block text-sm font-bold text-slate-700 mb-2">Idioma de la plantilla</label>
            <input name="whatsapp_meta_template_lang" value="{{ old('whatsapp_meta_template_lang', $settings['whatsapp_meta_template_lang'] ?? 'es') }}" class="{{ $waMono }}" placeholder="es">
            @error('whatsapp_meta_template_lang')<p class="mt-2 text-sm font-bold text-rose-600">{{ $message }}</p>@enderror
          </div>
          <p class="md:col-span-2 text-sm text-slate-500">La plantilla se crea en WhatsApp Manager, de categoría utilidad, con el cuerpo "Aviso de Infocus: @{{1}}". Sin plantilla, los avisos fuera de las 24 horas llegan por correo.</p>
        </div>
      </details>

      {{-- TextMeBot --}}
      <details data-wa-provider="textmebot" class="rounded-2xl border border-slate-200 px-5 py-4 {{ $waProvider === 'textmebot' ? '' : 'hidden' }}" {{ $waProvider === 'textmebot' ? 'open' : '' }}>
        <summary class="cursor-pointer font-extrabold text-slate-900">TextMeBot (no oficial)</summary>
        <div class="mt-4 grid gap-5 md:grid-cols-2">
          <div class="md:col-span-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            TextMeBot vincula un número de WhatsApp como si fuera WhatsApp Web. No es oficial: WhatsApp puede bloquear ese número y el servicio puede ver sus chats. Úsalo con un número aparte, no con tu WhatsApp de trabajo.
          </div>
          <div>
            <label class="block text-sm font-bold text-slate-700 mb-2">API key de TextMeBot</label>
            <input name="whatsapp_textmebot_apikey" type="password" class="{{ $waMono }}" placeholder="Dejar vacío para mantener la actual" autocomplete="new-password">
            <p class="mt-2 text-sm text-slate-500">Estado: <span class="font-bold text-slate-700">{{ $settings['whatsapp_textmebot_apikey_preview'] ?? 'Sin configurar' }}</span></p>
          </div>
          <div class="md:col-span-2 text-sm text-slate-600 space-y-2">
            <p>Para recibir mensajes, pídele a support@textmebot.com que configure este webhook (lleva una clave secreta: no la compartas en otro lado):</p>
            @if($waTextmebotKey !== '')
              <code class="block break-all rounded-xl bg-slate-50 px-3 py-2 font-mono text-xs text-slate-900 border border-slate-200">{{ url('/webhooks/whatsapp/textmebot/' . $waTextmebotKey) }}</code>
            @else
              <p class="font-bold">La URL se genera al guardar esta configuración.</p>
            @endif
            <p>Sin ventana de 24 horas ni plantillas: los avisos programados salen como mensaje normal.</p>
          </div>
        </div>
      </details>

      <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
        <button type="submit" class="rounded-2xl bg-[#ecfe88] px-7 py-3 font-extrabold text-slate-950 hover:bg-[#d9ef60]">Guardar WhatsApp</button>
      </div>
    </form>

    <form method="POST" action="{{ route('settings.ai.whatsapp.test') }}" class="flex flex-wrap items-center justify-between gap-3 rounded-3xl border border-slate-200 bg-white px-6 py-4 shadow-sm">
      @csrf
      <p class="text-sm text-slate-600">Envía un mensaje de prueba a tu WhatsApp con el proveedor guardado.</p>
      <button type="submit" class="rounded-2xl border border-slate-200 px-5 py-2.5 font-bold text-slate-700 hover:bg-slate-50">Enviar prueba</button>
    </form>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex items-center justify-between gap-3">
        <h2 class="text-lg font-black text-slate-900">Últimos mensajes recibidos</h2>
        <a href="{{ route('settings.ai', ['tab' => 'whatsapp']) }}" class="text-sm font-bold text-slate-500 hover:text-slate-900">Actualizar</a>
      </div>
      <p class="mt-1 text-sm text-slate-500">Si le escribes al número y no responde, aquí se ve si el mensaje llegó a Infocus y qué pasó con él.</p>
      @if(empty($whatsappEvents))
        <p class="mt-4 rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm font-semibold text-slate-400">Todavía no ha llegado ningún mensaje. Si ya le escribiste, el proveedor no está llamando al webhook.</p>
      @else
        <div class="mt-4 overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="text-xs uppercase tracking-wide text-slate-400">
              <tr><th class="py-2 pr-4">Hora</th><th class="py-2 pr-4">Proveedor</th><th class="py-2 pr-4">Resultado</th><th class="py-2 pr-4">De</th><th class="py-2">Detalle</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              @foreach($whatsappEvents as $event)
                <tr class="align-top">
                  <td class="py-2 pr-4 whitespace-nowrap text-slate-500">{{ \Illuminate\Support\Carbon::parse($event['at'])->setTimezone('America/Bogota')->format('d/m H:i:s') }}</td>
                  <td class="py-2 pr-4 text-slate-600">{{ $event['provider'] }}</td>
                  <td class="py-2 pr-4 font-bold {{ str_starts_with($event['result'], 'respondido') || $event['result'] === 'aceptado' || $event['result'] === 'llegó' ? 'text-emerald-700' : 'text-rose-700' }}">{{ $event['result'] }}</td>
                  <td class="py-2 pr-4 font-mono text-xs text-slate-600">{{ $event['from'] }}</td>
                  <td class="py-2 text-slate-600">{{ $event['detail'] }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>
  </section>

  <script>
    const infocusAiModels = @json($modelOptions);
    const selectedAiModel = @json($selectedModel);
    const infocusAiKeyPreviews = @json($settings['ai_api_key_previews'] ?? []);

    function rebuildAiModels(provider, preferredValue = 'auto') {
      const model = document.getElementById('aiModel');
      if (!model) return;
      const options = infocusAiModels[provider] || infocusAiModels.gemini || [];
      const values = options.map((option) => option.value);
      const nextValue = values.includes(preferredValue) ? preferredValue : 'auto';
      model.innerHTML = options.map((option) => {
        const selected = option.value === nextValue ? ' selected' : '';
        return `<option value="${option.value}"${selected}>${option.label}</option>`;
      }).join('');
      model.value = nextValue;
      model.dispatchEvent(new Event('change', { bubbles: true }));
    }

    document.getElementById('aiProvider')?.addEventListener('change', function () {
      rebuildAiModels(this.value, 'auto');
      const apiInput = document.getElementById('aiApiKeyInput');
      const apiStatus = document.getElementById('aiApiKeyStatus');
      if (apiInput) apiInput.value = '';
      if (apiStatus) apiStatus.textContent = infocusAiKeyPreviews[this.value] || 'Sin configurar';
    });

    rebuildAiModels(document.getElementById('aiProvider')?.value || 'gemini', selectedAiModel);

    document.getElementById('waProvider')?.addEventListener('change', function () {
      document.querySelectorAll('[data-wa-provider]').forEach((panel) => {
        const active = panel.dataset.waProvider === this.value;
        panel.classList.toggle('hidden', !active);
        panel.open = active;
      });
    });

    document.querySelectorAll('[data-ai-settings-tab]').forEach((button) => {
      button.addEventListener('click', () => {
        const tab = button.dataset.aiSettingsTab || 'conexion';
        document.querySelectorAll('[data-ai-settings-tab]').forEach((item) => {
          const active = item.dataset.aiSettingsTab === tab;
          item.classList.toggle('bg-slate-900', active);
          item.classList.toggle('text-white', active);
          item.classList.toggle('text-slate-600', !active);
          item.classList.toggle('hover:bg-slate-50', !active);
        });
        document.querySelectorAll('[data-ai-settings-panel]').forEach((panel) => {
          panel.classList.toggle('hidden', panel.dataset.aiSettingsPanel !== tab);
        });
        const url = new URL(window.location.href);
        if (tab === 'memoria' || tab === 'whatsapp') url.searchParams.set('tab', tab);
        else url.searchParams.delete('tab');
        window.history.replaceState({}, '', url.toString());
      });
    });
  </script>
@endsection
