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

  <form method="POST" action="{{ route('settings.ai.update') }}" class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 md:p-8 space-y-7">
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
  </script>
@endsection
