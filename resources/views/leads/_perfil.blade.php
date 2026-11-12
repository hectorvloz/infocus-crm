@php $perfilLead = $lead ?? []; @endphp
<div class="md:col-span-2 rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
  <div class="text-sm font-bold text-slate-800">Perfil del negocio</div>
  <div class="text-xs text-slate-500 mt-0.5">Qué hacen, cómo llegó y dónde encontrarlo. Puedes pegar el enlace o escribir el usuario (@marca).</div>
  <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3">
    <div class="md:col-span-2">
      <label for="leadDescripcion" class="text-sm font-medium">Descripción</label>
      <textarea id="leadDescripcion" name="descripcion" rows="3" class="w-full rounded-lg border p-2.5 bg-white" placeholder="Ej. Alquiler de carros en Pereira, 12 vehículos. Llegó por Instagram; su web no tiene reservas en línea.">{{ old('descripcion', $perfilLead['descripcion'] ?? '') }}</textarea>
    </div>
    @foreach(\App\Support\LeadProfile::REDES as $campo => $red)
      <div>
        <label for="lead_{{ $campo }}" class="text-sm font-medium">{{ $red['label'] }}</label>
        <input id="lead_{{ $campo }}" name="{{ $campo }}" value="{{ old($campo, $perfilLead[$campo] ?? '') }}" class="w-full rounded-lg border p-2.5 bg-white" placeholder="{{ $campo === 'sitio_web' ? 'ejemplo.com' : '@usuario o enlace' }}" autocomplete="off">
      </div>
    @endforeach
  </div>
</div>
