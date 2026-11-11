@php
  $valorEtiquetas = old('etiquetas', implode(', ', \App\Support\LeadTags::of($lead ?? [])));
@endphp
<div class="md:col-span-2">
  <label for="leadEtiquetas" class="text-sm font-medium">Etiquetas</label>
  <input id="leadEtiquetas" name="etiquetas" value="{{ $valorEtiquetas }}" class="w-full rounded-lg border p-2.5" placeholder="Ej. RentaCar, Inmobiliarias" autocomplete="off">
  <div class="mt-1 text-xs text-slate-500">Sepáralas con comas. Sirven para filtrar los leads por nicho.</div>
  @if(!empty($etiquetas))
    <div class="mt-2 flex flex-wrap gap-1.5" data-etiquetas-sugeridas>
      @foreach($etiquetas as $etiqueta)
        <button type="button" data-etiqueta="{{ $etiqueta }}" class="px-2.5 py-1 rounded-full border border-slate-200 bg-white text-xs font-semibold text-slate-600 hover:bg-slate-50">+ {{ $etiqueta }}</button>
      @endforeach
    </div>
    <script>
      (() => {
        const input = document.getElementById('leadEtiquetas');
        const clave = (t) => t.trim().toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
        document.querySelectorAll('[data-etiquetas-sugeridas] [data-etiqueta]').forEach((btn) => {
          btn.addEventListener('click', () => {
            const actuales = input.value.split(',').map((t) => t.trim()).filter(Boolean);
            const nueva = btn.getAttribute('data-etiqueta');
            if (!actuales.some((t) => clave(t) === clave(nueva))) actuales.push(nueva);
            input.value = actuales.join(', ');
            input.focus();
          });
        });
      })();
    </script>
  @endif
</div>
