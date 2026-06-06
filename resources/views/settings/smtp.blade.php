@extends('layouts.settings')

@section('title', 'Ajustes SMTP')

@section('content')
<div class="mb-8">
    <h2 class="text-2xl font-bold text-slate-900">Configuración SMTP</h2>
    <p class="text-slate-500">Configura tu servidor de correo para enviar notificaciones y facturas.</p>
</div>

@php
    $activeSmtpTab = in_array(request('tab'), ['usuarios', 'facturas'], true) ? request('tab') : 'empresa';
    $userSmtpSettings = is_array($settings['user_smtp'] ?? null) ? $settings['user_smtp'] : [];
    $invoiceSmtpEnabled = !empty($settings['invoice_smtp_enabled']);
@endphp

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 md:p-8">
    <div class="mb-6 inline-flex rounded-2xl border border-slate-200 bg-slate-50 p-1">
        <button type="button" data-smtp-tab="empresa" class="smtp-tab-btn px-5 py-2 rounded-xl text-sm font-extrabold transition {{ $activeSmtpTab === 'empresa' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:bg-white' }}">Empresa</button>
        <button type="button" data-smtp-tab="usuarios" class="smtp-tab-btn px-5 py-2 rounded-xl text-sm font-extrabold transition {{ $activeSmtpTab === 'usuarios' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:bg-white' }}">Usuarios</button>
        <button type="button" data-smtp-tab="facturas" class="smtp-tab-btn px-5 py-2 rounded-xl text-sm font-extrabold transition {{ $activeSmtpTab === 'facturas' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:bg-white' }}">Facturas</button>
    </div>

    <div data-smtp-panel="empresa" class="{{ $activeSmtpTab === 'empresa' ? '' : 'hidden' }}">
    <form action="{{ route('settings.smtp.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 mb-6">
            <div class="flex gap-3">
                <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-sm text-blue-700">Estos ajustes se usarán para enviar correos transaccionales. Si usas Gmail, asegúrate de habilitar "Contraseñas de aplicación".</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Host -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1">Servidor SMTP (Host)</label>
                <input type="text" name="smtp_host" value="{{ $settings['smtp_host'] ?? '' }}" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm" placeholder="smtp.gmail.com">
            </div>

            <!-- Port -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Puerto</label>
                <input type="number" name="smtp_port" value="{{ $settings['smtp_port'] ?? '587' }}" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm" placeholder="587">
            </div>

            <!-- Encryption -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Cifrado</label>
                <select name="smtp_encryption" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm">
                    <option value="tls" {{ ($settings['smtp_encryption'] ?? '') == 'tls' ? 'selected' : '' }}>TLS</option>
                    <option value="ssl" {{ ($settings['smtp_encryption'] ?? '') == 'ssl' ? 'selected' : '' }}>SSL</option>
                    <option value="none" {{ ($settings['smtp_encryption'] ?? '') == 'none' ? 'selected' : '' }}>Ninguno</option>
                </select>
            </div>

            <!-- Username -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Usuario / Correo</label>
                <input type="text" name="smtp_username" value="{{ $settings['smtp_username'] ?? '' }}" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm">
            </div>

            <!-- Password -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Contraseña</label>
                <input type="password" name="smtp_password" placeholder="{{ !empty($settings['smtp_password']) ? 'Dejar vacio para mantener la actual' : 'Ingresa contraseña SMTP' }}" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm">
            </div>
        </div>

        <div class="border-t border-slate-100 pt-6 mt-2">
            <h3 class="text-sm font-bold text-slate-900 mb-4 uppercase tracking-wider">Remitente</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- From Name -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nombre del Remitente</label>
                    <input type="text" name="mail_from_name" value="{{ $settings['mail_from_name'] ?? config('app.name') }}" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm">
                </div>

                <!-- From Address -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Correo del Remitente</label>
                    <input type="email" name="mail_from_address" value="{{ $settings['mail_from_address'] ?? '' }}" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-slate-100">
            <button type="button" data-open-test-email data-smtp-scope="empresa" class="text-blue-600 hover:text-blue-800 text-sm font-semibold flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Enviar correo de prueba
            </button>
            <button type="submit" class="px-6 py-2.5 rounded-xl text-sm font-bold text-slate-900 bg-[#ecfe88] hover:bg-[#d9ea76] transition-colors shadow-sm">
                Guardar Configuración SMTP
            </button>
        </div>
    </form>
    </div>

    <div data-smtp-panel="usuarios" class="{{ $activeSmtpTab === 'usuarios' ? '' : 'hidden' }}">
        <form action="{{ route('settings.smtp.update') }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            <input type="hidden" name="scope" value="users">

            <div class="bg-lime-50 border border-lime-200 rounded-xl p-4">
                <div class="flex gap-3">
                    <svg class="w-5 h-5 text-lime-700 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-sm font-semibold text-slate-700">Los usuarios usan el mismo servidor, puerto y cifrado de Empresa. Aquí solo defines el usuario/correo y contraseña SMTP de cada cuenta.</p>
                </div>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Usuario</th>
                            <th class="px-4 py-3 text-left text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Usuario / Correo SMTP</th>
                            <th class="px-4 py-3 text-left text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Contraseña</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse(($users ?? []) as $i => $user)
                            @php
                                $email = strtolower(trim((string) ($user['email'] ?? '')));
                                $row = $userSmtpSettings[$email] ?? [];
                            @endphp
                            <tr>
                                <td class="px-4 py-4">
                                    <div class="font-extrabold text-slate-900">{{ $user['name'] ?? $email }}</div>
                                    <div class="text-sm font-semibold text-slate-500">{{ $email }}</div>
                                    <input type="hidden" name="user_smtp[{{ $i }}][email]" value="{{ $email }}">
                                </td>
                                <td class="px-4 py-4">
                                    <input type="text" name="user_smtp[{{ $i }}][username]" value="{{ $row['username'] ?? $email }}" class="block w-full min-w-[260px] px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm">
                                </td>
                                <td class="px-4 py-4">
                                    <input type="password" name="user_smtp[{{ $i }}][password]" placeholder="{{ !empty($row['password'] ?? '') ? 'Dejar vacío para mantener la actual' : 'Contraseña SMTP del usuario' }}" class="block w-full min-w-[260px] px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm">
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-10 text-center text-sm font-semibold text-slate-400">No hay usuarios activos con correo.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end pt-4 border-t border-slate-100">
                <button type="submit" class="px-6 py-2.5 rounded-xl text-sm font-bold text-slate-900 bg-[#ecfe88] hover:bg-[#d9ea76] transition-colors shadow-sm">
                    Guardar SMTP de usuarios
                </button>
            </div>
        </form>
    </div>

    <div data-smtp-panel="facturas" class="{{ $activeSmtpTab === 'facturas' ? '' : 'hidden' }}">
        <form action="{{ route('settings.smtp.update') }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            <input type="hidden" name="scope" value="invoices">

            <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h3 class="text-sm font-extrabold uppercase tracking-[0.16em] text-slate-900">SMTP exclusivo para facturas</h3>
                    <p class="mt-1 text-sm font-semibold text-slate-500">Cuando esté activo, las facturas saldrán desde este correo. Los demás correos seguirán usando Empresa o Usuarios.</p>
                </div>
                <label class="inline-flex cursor-pointer items-center gap-3 rounded-full bg-white px-4 py-2 ring-1 ring-slate-200">
                    <input id="invoiceSmtpToggle" type="checkbox" name="invoice_smtp_enabled" value="1" class="peer sr-only" {{ $invoiceSmtpEnabled ? 'checked' : '' }}>
                    <span class="relative h-7 w-12 rounded-full bg-slate-300 transition peer-checked:bg-slate-900 after:absolute after:left-1 after:top-1 after:h-5 after:w-5 after:rounded-full after:bg-white after:transition peer-checked:after:translate-x-5"></span>
                    <span class="text-sm font-extrabold text-slate-800">Activar</span>
                </label>
            </div>

            <div id="invoiceSmtpFields" class="{{ $invoiceSmtpEnabled ? '' : 'opacity-50' }} space-y-6">
                <div class="bg-blue-50 border border-blue-100 rounded-xl p-4">
                    <div class="flex gap-3">
                        <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-sm text-blue-700">Usa aquí el correo que quieres como remitente de facturas. Si lo desactivas, las facturas vuelven a usar el SMTP de Empresa.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Servidor SMTP (Host)</label>
                        <input type="text" name="invoice_smtp_host" value="{{ $settings['invoice_smtp_host'] ?? '' }}" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm" placeholder="mail.tudominio.com">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Puerto</label>
                        <input type="number" name="invoice_smtp_port" value="{{ $settings['invoice_smtp_port'] ?? '465' }}" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm" placeholder="465">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Cifrado</label>
                        <select name="invoice_smtp_encryption" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm">
                            <option value="tls" {{ ($settings['invoice_smtp_encryption'] ?? '') == 'tls' ? 'selected' : '' }}>TLS</option>
                            <option value="ssl" {{ ($settings['invoice_smtp_encryption'] ?? 'ssl') == 'ssl' ? 'selected' : '' }}>SSL</option>
                            <option value="none" {{ ($settings['invoice_smtp_encryption'] ?? '') == 'none' ? 'selected' : '' }}>Ninguno</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Usuario / Correo</label>
                        <input type="text" name="invoice_smtp_username" value="{{ $settings['invoice_smtp_username'] ?? '' }}" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm" placeholder="facturas@tudominio.com">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Contraseña</label>
                        <input type="password" name="invoice_smtp_password" placeholder="{{ !empty($settings['invoice_smtp_password'] ?? '') ? 'Dejar vacío para mantener la actual' : 'Contraseña SMTP de facturas' }}" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm">
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-6">
                    <h3 class="text-sm font-bold text-slate-900 mb-4 uppercase tracking-wider">Remitente de facturas</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Nombre del Remitente</label>
                            <input type="text" name="invoice_mail_from_name" value="{{ $settings['invoice_mail_from_name'] ?? ($settings['mail_from_name'] ?? config('app.name')) }}" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Correo del Remitente</label>
                            <input type="email" name="invoice_mail_from_address" value="{{ $settings['invoice_mail_from_address'] ?? ($settings['invoice_smtp_username'] ?? '') }}" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm" placeholder="facturas@tudominio.com">
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                <button type="button" data-open-test-email data-smtp-scope="facturas" class="text-blue-600 hover:text-blue-800 text-sm font-semibold flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Probar SMTP de facturas
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl text-sm font-bold text-slate-900 bg-[#ecfe88] hover:bg-[#d9ea76] transition-colors shadow-sm">
                    Guardar SMTP de facturas
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Test Email Modal -->
<div id="test-email-modal" class="fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-hidden flex flex-col">
        <form action="{{ route('settings.smtp.test') }}" method="POST" class="flex h-full flex-col min-h-0">
            @csrf
            <input id="testSmtpScope" type="hidden" name="smtp_scope" value="{{ old('smtp_scope', 'empresa') }}">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between shrink-0">
                <div>
                    <h3 class="font-bold text-lg text-slate-900">Correo de Prueba</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Elige qué plantilla quieres probar</p>
                </div>
                <button type="button" onclick="document.getElementById('test-email-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-700 text-lg leading-none">✕</button>
            </div>
            <div class="p-5 space-y-4 shrink-0">
                {{-- Destinatario --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Enviar a:</label>
                    <input type="email" name="test_email" required
                        class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm"
                        placeholder="tu@email.com"
                        value="{{ old('test_email', auth()->user()->email ?? '') }}">
                </div>
            </div>
            <div class="px-5 pb-5 min-h-0 overflow-y-auto">
                {{-- Tipo de correo --}}
                <div class="grid grid-cols-1 gap-2">
                    @php
                    $emailTypes = [
                        ['value'=>'simple',    'icon'=>'📨', 'label'=>'Prueba simple',           'desc'=>'Confirma que el SMTP funciona'],
                        ['value'=>'factura',   'icon'=>'🧾', 'label'=>'Factura emitida',          'desc'=>'Notificación de nueva factura'],
                        ['value'=>'portal',    'icon'=>'🔑', 'label'=>'Acceso portal cliente',    'desc'=>'Credenciales de acceso al portal'],
                        ['value'=>'reset',     'icon'=>'🔒', 'label'=>'Restablecimiento de cuenta','desc'=>'Enlace para restablecer contraseña'],
                        ['value'=>'recordatorio','icon'=>'📌','label'=>'Recordatorio de tarea',   'desc'=>'Aviso de vencimiento de tarea'],
                        ['value'=>'recordatorio_servicio','icon'=>'⏰','label'=>'Vencimiento servicio/producto',   'desc'=>'Aviso previo de factura recurrente'],
                        ['value'=>'recurrente','icon'=>'🔁', 'label'=>'Factura recurrente',       'desc'=>'Notificación de cobro recurrente'],
                    ];
                    @endphp
                    @foreach($emailTypes as $i => $t)
                    <label class="flex items-center gap-3 px-4 py-3 rounded-xl border cursor-pointer transition-all hover:border-slate-400 has-[:checked]:border-slate-900 has-[:checked]:bg-slate-50">
                        <input type="radio" name="email_type" value="{{ $t['value'] }}" class="sr-only" {{ old('email_type', 'simple') === $t['value'] ? 'checked' : '' }}>
                        <span class="text-xl leading-none select-none">{{ $t['icon'] }}</span>
                        <span class="flex-1 min-w-0">
                            <span class="block text-sm font-semibold text-slate-800">{{ $t['label'] }}</span>
                            <span class="block text-xs text-slate-400">{{ $t['desc'] }}</span>
                        </span>
                        <span class="radio-dot w-4 h-4 rounded-full border-2 border-slate-300 flex items-center justify-center shrink-0">
                            <span class="w-2 h-2 rounded-full bg-slate-900 hidden"></span>
                        </span>
                    </label>
                    @endforeach
                </div>
            </div>
            <div class="p-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-2 shrink-0">
                <button type="button" onclick="document.getElementById('test-email-modal').classList.add('hidden')"
                    class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-200">Cancelar</button>
                <button type="submit"
                    class="px-5 py-2 rounded-xl text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 transition-colors">Enviar prueba</button>
            </div>
        </form>
    </div>
</div>

<script>
// Radio visual feedback
function syncTestEmailRadioDots() {
    document.querySelectorAll('#test-email-modal label').forEach(lbl => {
        const radio = lbl.querySelector('input[type="radio"]');
        const dot = lbl.querySelector('.radio-dot span');
        if (!dot || !radio) return;
        dot.classList.toggle('hidden', !radio.checked);
    });
}
document.querySelectorAll('#test-email-modal input[type="radio"]').forEach(radio => {
    radio.addEventListener('change', syncTestEmailRadioDots);
});
syncTestEmailRadioDots();

document.querySelectorAll('[data-open-test-email]').forEach((button) => {
    button.addEventListener('click', () => {
        const scopeInput = document.getElementById('testSmtpScope');
        if (scopeInput) scopeInput.value = button.dataset.smtpScope || 'empresa';
        document.getElementById('test-email-modal')?.classList.remove('hidden');
    });
});

const invoiceSmtpToggle = document.getElementById('invoiceSmtpToggle');
const invoiceSmtpFields = document.getElementById('invoiceSmtpFields');
if (invoiceSmtpToggle && invoiceSmtpFields) {
    const syncInvoiceSmtpFields = () => {
        invoiceSmtpFields.classList.toggle('opacity-50', !invoiceSmtpToggle.checked);
    };
    invoiceSmtpToggle.addEventListener('change', syncInvoiceSmtpFields);
    syncInvoiceSmtpFields();
}

document.querySelectorAll('[data-smtp-tab]').forEach((button) => {
    button.addEventListener('click', () => {
        const tab = button.dataset.smtpTab;
        document.querySelectorAll('[data-smtp-panel]').forEach((panel) => {
            panel.classList.toggle('hidden', panel.dataset.smtpPanel !== tab);
        });
        document.querySelectorAll('[data-smtp-tab]').forEach((tabButton) => {
            const active = tabButton.dataset.smtpTab === tab;
            tabButton.classList.toggle('bg-slate-900', active);
            tabButton.classList.toggle('text-white', active);
            tabButton.classList.toggle('shadow-sm', active);
            tabButton.classList.toggle('text-slate-600', !active);
            tabButton.classList.toggle('hover:bg-white', !active);
        });
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url.toString());
    });
});

@if($errors->has('test_email') || $errors->has('email_type'))
document.getElementById('test-email-modal').classList.remove('hidden');
@endif
</script>
@endsection
