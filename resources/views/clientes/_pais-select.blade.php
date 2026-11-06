@php
    $paises = \App\Support\Countries::all();
    $paisActual = trim((string) ($pais ?? ''));
    $paisElegido = $paisActual !== '' ? $paisActual : \App\Support\Countries::DEFAULT;
@endphp
<select name="pais" class="form-input pl-9">
    <option value="">Sin país</option>
    @if($paisActual !== '' && !in_array($paisActual, $paises, true))
        <option value="{{ $paisActual }}" selected>{{ $paisActual }}</option>
    @endif
    @foreach($paises as $nombrePais)
        <option value="{{ $nombrePais }}" @selected($nombrePais === $paisElegido)>{{ $nombrePais }}</option>
    @endforeach
</select>
