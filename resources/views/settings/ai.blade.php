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
        ['value' => 'deepseek-chat', 'label' => 'DeepSeek Chat'],
        ['value' => 'deepseek-reasoner', 'label' => 'DeepSeek Reasoner'],
      ],
    ];
    $selectedProvider = old('ai_provider', $settings['ai_provider'] ?? 'gemini');
    $selectedProvider = array_key_exists($selectedProvider, $providerLabels) ? $selectedProvider : 'gemini';
    $selectedModel = old('ai_model', $settings['ai_model'] ?? 'auto');
    $selectedModel = in_array($selectedModel, ['gemini-1.5-flash', 'gemini-1.5-pro'], true) ? 'auto' : $selectedModel;
    $providerModelValues = collect($modelOptions[$selectedProvider])->pluck('value')->all();
    $selectedModel = in_array($selectedModel, $providerModelValues, true) ? $selectedModel : 'auto';
  @endphp

  <div class="mb-8">
    <h1 class="text-4xl font-extrabold text-slate-900">Configuración IA</h1>
    <p class="mt-2 text-lg text-slate-500">Conecta el asistente del CRM con Gemini, OpenAI o DeepSeek por API.</p>
  </div>

  @php
    $activeAiTab = request('tab') === 'memoria' ? 'memoria' : 'conexion';
    $memoryGroups = [
      'client' => ['label' => 'Clientes', 'empty' => 'Todavía no hay memorias vinculadas a clientes.'],
      'user' => ['label' => 'De mí', 'empty' => 'Todavía no hay memorias personales.'],
      'company' => ['label' => 'Mi empresa', 'empty' => 'Todavía no hay memorias de empresa.'],
    ];
  @endphp

  <div class="mb-5 inline-flex rounded-2xl border border-slate-200 bg-white p-1 shadow-sm">
    <button type="button" data-ai-settings-tab="conexion" class="ai-settings-tab rounded-xl px-4 py-2 text-sm font-extrabold {{ $activeAiTab === 'conexion' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-50' }}">Conexión</button>
    <button type="button" data-ai-settings-tab="memoria" class="ai-settings-tab rounded-xl px-4 py-2 text-sm font-extrabold {{ $activeAiTab === 'memoria' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-50' }}">Memoria</button>
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
      <p class="mt-1">La IA guarda preferencias cuando dices cosas como “recuerda”, “ten en cuenta”, “este cliente prefiere...” o “mi empresa usa...”. Si hay cliente activo en factura, proyecto o nota, se vincula a ese cliente. Puedes editar o borrar cualquier memoria aquí.</p>
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
        if (tab === 'memoria') url.searchParams.set('tab', 'memoria');
        else url.searchParams.delete('tab');
        window.history.replaceState({}, '', url.toString());
      });
    });
  </script>
@endsection
