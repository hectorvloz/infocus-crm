@extends('layouts.app')
@section('title','Editar cliente')
@section('content')
  <div class="max-w-3xl bg-white rounded-2xl shadow border p-6">
    <div class="text-xl font-bold mb-4">Editar cliente</div>
    <form method="POST" action="{{ route('clientes.update',$cliente['id']) }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      @csrf
      <div class="md:col-span-2">
        <label class="text-sm font-medium">Empresa</label>
        <input name="empresa" value="{{ $cliente['empresa'] }}" class="form-input" required>
      </div>
      <div>
        <label class="text-sm font-medium">Propietario</label>
        <input name="propietario" value="{{ $cliente['propietario'] ?? '' }}" class="form-input">
      </div>
      <div>
        <label class="text-sm font-medium">NIT</label>
        <input name="nit" value="{{ $cliente['nit'] ?? '' }}" class="form-input">
      </div>
      <div>
        <label class="text-sm font-medium">Categoría</label>
        <input name="categoria" value="{{ $cliente['categoria'] ?? '' }}" class="form-input" placeholder="Default">
      </div>
      <div class="md:col-span-2 pt-2">
        <div class="text-sm font-semibold mb-2">Datos de contacto</div>
      </div>
      <div class="relative">
        <label class="text-sm font-medium">Nombre</label>
        <svg class="absolute left-3 top-9 h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5z"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
        <input name="contacto_nombre" value="{{ $cliente['contacto_nombre'] ?? '' }}" class="form-input pl-9">
      </div>
      <div class="relative">
        <label class="text-sm font-medium">Email</label>
        <svg class="absolute left-3 top-9 h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16v12H4z"/><path d="m22 6-10 7L2 6"/></svg>
        <input name="contacto_email" type="email" value="{{ $cliente['contacto_email'] ?? '' }}" class="form-input pl-9">
      </div>
      <div class="relative">
        <label class="text-sm font-medium">Teléfono</label>
        <svg class="absolute left-3 top-9 h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 3.1 6.18 2 2 0 0 1 5.11 4h3a2 2 0 0 1 2 1.72 12.05 12.05 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11l-1.27 1.27a16 16 0 0 0 6.36 6.36l1.27-1.27a2 2 0 0 1 2.11-.45 12.05 12.05 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
        <input name="contacto_telefono" value="{{ $cliente['contacto_telefono'] ?? '' }}" class="form-input pl-9">
      </div>
      <div class="relative">
        <label class="text-sm font-medium">Website</label>
        <svg class="absolute left-3 top-9 h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 0 20"/><path d="M12 2a15.3 15.3 0 0 0 0 20"/></svg>
        <input name="website" type="url" value="{{ $cliente['website'] ?? '' }}" class="form-input pl-9">
      </div>
      <div class="md:col-span-2 relative">
        <label class="text-sm font-medium">Dirección</label>
        <svg class="absolute left-3 top-9 h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2C8.14 2 5 5.14 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.86-3.14-7-7-7z"/><circle cx="12" cy="9" r="2.5"/></svg>
        <input name="direccion" value="{{ $cliente['direccion'] ?? '' }}" class="form-input pl-9">
      </div>
      <div class="relative">
        <label class="text-sm font-medium">Ciudad</label>
        <svg class="absolute left-3 top-9 h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 22h18"/><path d="M6 18V8l6-3 6 3v10"/></svg>
        <input name="ciudad" value="{{ $cliente['ciudad'] ?? '' }}" class="form-input pl-9">
      </div>
      <div>
        <label class="text-sm font-medium">Imagen de perfil</label>
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
          <div id="previewEdit" class="h-10 w-10 shrink-0 rounded-full bg-neutral-200 overflow-hidden">
            @if(!empty($cliente['avatar']))<img src="{{ $cliente['avatar'] }}" class="h-full w-full object-cover">@endif
          </div>
          <input type="file" name="avatar" accept="image/*" class="w-full min-w-0 rounded-lg border p-2.5 text-sm">
          @if(!empty($cliente['avatar']))
            <label class="inline-flex items-center gap-2 text-sm">
              <input type="checkbox" name="remove_avatar" value="1" class="rounded border"> Eliminar
            </label>
          @endif
        </div>
      </div>
      <div class="relative">
        <label class="text-sm font-medium">País</label>
        <svg class="absolute left-3 top-9 h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 3v16"/><path d="M5 3l14 2-4 2 4 2-14 2"/></svg>
        <input name="pais" value="{{ $cliente['pais'] ?? '' }}" class="form-input pl-9">
      </div>
      <div class="relative">
        <label class="text-sm font-medium">Código postal</label>
        <svg class="absolute left-3 top-9 h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/></svg>
        <input name="codigo_postal" value="{{ $cliente['codigo_postal'] ?? '' }}" class="form-input pl-9">
      </div>
      <div>
        <label class="text-sm font-medium">Moneda cliente</label>
        <select name="moneda" class="form-select">
          @foreach(($currencies ?? ['USD','EUR','MXN','COP','ARS','CLP','PEN','GBP','CAD','JPY','AUD','CNY','CHF','HKD','NZD','SEK','KRW','SGD','INR','BRL','RUB','ZAR','TRY']) as $cur)
            <option value="{{ $cur }}" @selected(($cliente['moneda'] ?? ($baseCurrency ?? 'USD')) === $cur)>{{ $cur }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="text-sm font-medium">Etiquetas</label>
        <input name="etiquetas" value="{{ $cliente['etiquetas'] ?? '' }}" class="form-input" placeholder="separadas por comas">
      </div>
      <div>
        <label class="text-sm font-medium">Estado</label>
        <select name="estado" class="form-select" required>
          @foreach(['Activo','Inactivo'] as $opt)
            <option @selected(($cliente['estado'] ?? '')===$opt)>{{ $opt }}</option>
          @endforeach
        </select>
      </div>
      @php $inv = $cliente['invoice_fields'] ?? ['nit'=>true,'direccion'=>true,'telefono'=>true,'email'=>true]; @endphp
      <div class="md:col-span-2 pt-2">
        <div class="text-sm font-semibold mb-2">Mostrar en factura</div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
          <label class="flex items-center gap-2 rounded-xl border bg-neutral-50 px-3 py-2 text-sm">
            <input type="checkbox" name="invoice_fields[nit]" value="1" @checked(($inv['nit'] ?? true))>
            <span>NIT</span>
          </label>
          <label class="flex items-center gap-2 rounded-xl border bg-neutral-50 px-3 py-2 text-sm">
            <input type="checkbox" name="invoice_fields[direccion]" value="1" @checked(($inv['direccion'] ?? true))>
            <span>Dirección</span>
          </label>
          <label class="flex items-center gap-2 rounded-xl border bg-neutral-50 px-3 py-2 text-sm">
            <input type="checkbox" name="invoice_fields[telefono]" value="1" @checked(($inv['telefono'] ?? true))>
            <span>Teléfono</span>
          </label>
          <label class="flex items-center gap-2 rounded-xl border bg-neutral-50 px-3 py-2 text-sm">
            <input type="checkbox" name="invoice_fields[email]" value="1" @checked(($inv['email'] ?? true))>
            <span>Email</span>
          </label>
        </div>
      </div>
      <div class="md:col-span-2 pt-2">
        <div class="flex items-center justify-between">
          <div class="text-sm font-semibold">Campos personalizados</div>
          <button type="button" id="addCustom" class="px-3 py-1.5 rounded-full bg-lime-300 text-slate-900 text-sm font-semibold">Añadir campo</button>
        </div>
        <div id="customFields" class="mt-2 space-y-2">
          @php $i=0; @endphp
          @if(!empty($cliente['custom_fields']) && is_array($cliente['custom_fields']))
            @foreach($cliente['custom_fields'] as $k=>$v)
            <div class="grid grid-cols-12 gap-2 custom">
              <input name="custom_keys[{{ $i }}]" value="{{ $k }}" class="col-span-5 rounded-lg border p-2.5" placeholder="Nombre del campo">
              <input name="custom_values[{{ $i }}]" value="{{ $v }}" class="col-span-6 rounded-lg border p-2.5" placeholder="Valor">
              <button type="button" class="col-span-1 px-3 py-2 rounded-lg border remove">✕</button>
            </div>
            @php $i++; @endphp
            @endforeach
          @else
            <div class="grid grid-cols-12 gap-2 custom">
              <input name="custom_keys[0]" class="col-span-5 rounded-lg border p-2.5" placeholder="Nombre del campo">
              <input name="custom_values[0]" class="col-span-6 rounded-lg border p-2.5" placeholder="Valor">
              <button type="button" class="col-span-1 px-3 py-2 rounded-lg border remove">✕</button>
            </div>
            @php $i=1; @endphp
          @endif
        </div>
      </div>
      <div class="md:col-span-2 flex justify-end gap-3">
        <a href="{{ route('clientes.index') }}" class="px-4 py-2 rounded-full border">Cancelar</a>
        <button class="px-4 py-2 rounded-full bg-lime-300 text-slate-900 font-semibold">Guardar</button>
      </div>
    </form>
  </div>
  <script>
    const container = document.getElementById('customFields');
    const addBtn = document.getElementById('addCustom');
    let cidx = document.querySelectorAll('#customFields .custom').length || 1;
    function bindRemove(btn){ btn.addEventListener('click', () => btn.closest('.custom').remove()); }
    document.querySelectorAll('.remove').forEach(b=>bindRemove(b));
    addBtn.addEventListener('click', () => {
      const row = document.createElement('div');
      row.className='grid grid-cols-12 gap-2 custom';
      row.innerHTML = `
        <input name="custom_keys[${cidx}]" class="col-span-5 rounded-lg border p-2.5" placeholder="Nombre del campo">
        <input name="custom_values[${cidx}]" class="col-span-6 rounded-lg border p-2.5" placeholder="Valor">
        <button type="button" class="col-span-1 px-3 py-2 rounded-lg border remove">✕</button>
      `;
      container.appendChild(row);
      bindRemove(row.querySelector('.remove'));
      cidx++;
    });
    const inputFile = document.querySelector('input[name="avatar"]');
    const preview = document.getElementById('previewEdit');
    if (inputFile && preview) {
      inputFile.addEventListener('change', e => {
        const f = e.target.files[0];
        if (!f) return;
        const r = new FileReader();
        r.onload = ev => {
          preview.innerHTML = '';
          preview.style.backgroundImage = `url('${ev.target.result}')`;
          preview.style.backgroundSize = 'cover';
        };
        r.readAsDataURL(f);
      });
    }
  </script>
@endsection
