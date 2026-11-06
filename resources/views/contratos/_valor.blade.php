{{-- Valor del contrato: moneda (por defecto la moneda base) y monto con vista previa formateada. --}}
@php
    $monedaActual = $moneda ?: $baseCurrency;
@endphp
<div class="bg-white p-6 rounded-2xl shadow-soft border border-slate-100 space-y-4">
    <div>
        <h3 class="font-bold text-slate-900">Valor del contrato</h3>
        <p class="text-xs text-slate-500 mt-0.5">Opcional. Se usa para llenar [VALOR] en las plantillas.</p>
    </div>

    <div>
        <label for="contractCurrency" class="block text-sm font-medium text-slate-600 mb-1">Moneda</label>
        <select id="contractCurrency" name="moneda" class="w-full rounded-xl border-slate-200 focus:ring-2 focus:ring-lime-300 focus:border-lime-300">
            @foreach($currencies as $code => $label)
                <option value="{{ $code }}" @selected($code === $monedaActual)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="contractAmount" class="block text-sm font-medium text-slate-600 mb-1">Monto total</label>
        <div class="relative">
            <span id="contractCurrencyPrefix" class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">{{ $monedaActual }}</span>
            <input id="contractAmount" type="number" step="any" min="0" name="monto" value="{{ $monto }}" placeholder="0" class="w-full pl-12 rounded-xl border-slate-200 focus:ring-2 focus:ring-lime-300 focus:border-lime-300">
        </div>
        <p id="contractAmountPreview" class="mt-1.5 text-xs font-semibold text-slate-500 min-h-[1rem]"></p>
    </div>
</div>
<script>
    window.formatContractMoney = function () {
        const currency = document.getElementById('contractCurrency').value;
        const amount = parseFloat(document.getElementById('contractAmount').value);
        if (!Number.isFinite(amount) || amount <= 0) return '';
        const decimals = currency === 'COP' ? 0 : 2;
        return `${currency} ${amount.toLocaleString(currency === 'COP' ? 'es-CO' : 'en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals })}`;
    };
    (function () {
        const update = () => {
            document.getElementById('contractCurrencyPrefix').textContent = document.getElementById('contractCurrency').value;
            document.getElementById('contractAmountPreview').textContent = window.formatContractMoney();
        };
        document.getElementById('contractCurrency').addEventListener('change', update);
        document.getElementById('contractAmount').addEventListener('input', update);
        update();
    })();
</script>
