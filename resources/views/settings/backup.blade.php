@extends('layouts.settings')

@section('title', 'Copias de Seguridad')

@section('content')
<div class="mb-8">
    <h2 class="text-2xl font-bold text-slate-900">Respaldos</h2>
    <p class="text-slate-500">Descarga una copia completa de tus datos y archivos.</p>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 md:p-8 text-center">
    <div class="w-20 h-20 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-6">
        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
    </div>
    
    <h3 class="text-xl font-bold text-slate-900 mb-2">Exportar Datos</h3>
    <p class="text-slate-500 mb-6 max-w-md mx-auto">Selecciona qué información deseas incluir en tu copia de seguridad. Cada respaldo incluye un manifiesto con conteos y fuentes para verificar que entró todo.</p>

    <form action="{{ route('settings.backup.download') }}" method="GET" class="max-w-lg mx-auto text-left">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
            <label class="flex items-start gap-3 p-4 min-h-[112px] rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition-colors">
                <input type="checkbox" name="types[]" value="clientes" checked class="w-5 h-5 mt-1 shrink-0 text-slate-900 border-slate-300 rounded focus:ring-slate-900">
                <div class="flex-1 leading-tight">
                    <div class="font-bold text-slate-900">Clientes</div>
                    <div class="text-xs text-slate-500">Base de datos de clientes</div>
                </div>
            </label>
            <label class="flex items-start gap-3 p-4 min-h-[112px] rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition-colors">
                <input type="checkbox" name="types[]" value="leads" checked class="w-5 h-5 mt-1 shrink-0 text-slate-900 border-slate-300 rounded focus:ring-slate-900">
                <div class="flex-1 leading-tight">
                    <div class="font-bold text-slate-900">Leads</div>
                    <div class="text-xs text-slate-500">Kanban comercial y mensajes</div>
                </div>
            </label>
            <label class="flex items-start gap-3 p-4 min-h-[112px] rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition-colors">
                <input type="checkbox" name="types[]" value="reuniones" checked class="w-5 h-5 mt-1 shrink-0 text-slate-900 border-slate-300 rounded focus:ring-slate-900">
                <div class="flex-1 leading-tight">
                    <div class="font-bold text-slate-900">Reuniones</div>
                    <div class="text-xs text-slate-500">Agenda semanal, invitados, Meet y ubicaciones</div>
                </div>
            </label>
            <label class="flex items-start gap-3 p-4 min-h-[112px] rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition-colors">
                <input type="checkbox" name="types[]" value="facturas" checked class="w-5 h-5 mt-1 shrink-0 text-slate-900 border-slate-300 rounded focus:ring-slate-900">
                <div class="flex-1 leading-tight">
                    <div class="font-bold text-slate-900">Facturación</div>
                    <div class="text-xs text-slate-500">Facturas, pagos y recurrencias</div>
                </div>
            </label>
            <label class="flex items-start gap-3 p-4 min-h-[112px] rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition-colors">
                <input type="checkbox" name="types[]" value="cotizaciones" checked class="w-5 h-5 mt-1 shrink-0 text-slate-900 border-slate-300 rounded focus:ring-slate-900">
                <div class="flex-1 leading-tight">
                    <div class="font-bold text-slate-900">Cotizaciones</div>
                    <div class="text-xs text-slate-500">Presupuestos enviados</div>
                </div>
            </label>
            <label class="flex items-start gap-3 p-4 min-h-[112px] rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition-colors">
                <input type="checkbox" name="types[]" value="correo" checked class="w-5 h-5 mt-1 shrink-0 text-slate-900 border-slate-300 rounded focus:ring-slate-900">
                <div class="flex-1 leading-tight">
                    <div class="font-bold text-slate-900">Correo</div>
                    <div class="text-xs text-slate-500">Plantillas e historial de correos enviados</div>
                </div>
            </label>
            <label class="flex items-start gap-3 p-4 min-h-[112px] rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition-colors">
                <input type="checkbox" name="types[]" value="proyectos" checked class="w-5 h-5 mt-1 shrink-0 text-slate-900 border-slate-300 rounded focus:ring-slate-900">
                <div class="flex-1 leading-tight">
                    <div class="font-bold text-slate-900">Proyectos</div>
                    <div class="text-xs text-slate-500">Proyectos, tareas, gastos y documentos</div>
                </div>
            </label>
            <label class="flex items-start gap-3 p-4 min-h-[112px] rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition-colors">
                <input type="checkbox" name="types[]" value="ajustes" checked class="w-5 h-5 mt-1 shrink-0 text-slate-900 border-slate-300 rounded focus:ring-slate-900">
                <div class="flex-1 leading-tight">
                    <div class="font-bold text-slate-900">Ajustes</div>
                    <div class="text-xs text-slate-500">Configuración, usuarios, roles y estado de notificaciones</div>
                </div>
            </label>
            <label class="flex items-start gap-3 p-4 min-h-[112px] rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition-colors">
                <input type="checkbox" name="types[]" value="mis_notas" checked class="w-5 h-5 mt-1 shrink-0 text-slate-900 border-slate-300 rounded focus:ring-slate-900">
                <div class="flex-1 leading-tight">
                    <div class="font-bold text-slate-900">Mis Notas</div>
                    <div class="text-xs text-slate-500">Notas del editor con título, contenido y cliente vinculado</div>
                </div>
            </label>
            <label class="flex items-start gap-3 p-4 min-h-[112px] rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition-colors">
                <input type="checkbox" name="types[]" value="notas_rapidas" checked class="w-5 h-5 mt-1 shrink-0 text-slate-900 border-slate-300 rounded focus:ring-slate-900">
                <div class="flex-1 leading-tight">
                    <div class="font-bold text-slate-900">Notas Rápidas</div>
                    <div class="text-xs text-slate-500">Notas del panel flotante de acceso rápido</div>
                </div>
            </label>
            <label class="flex items-start gap-3 p-4 min-h-[112px] rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition-colors">
                <input type="checkbox" name="types[]" value="uploads" checked class="w-5 h-5 mt-1 shrink-0 text-slate-900 border-slate-300 rounded focus:ring-slate-900">
                <div class="flex-1 leading-tight">
                    <div class="font-bold text-slate-900">Archivos</div>
                    <div class="text-xs text-slate-500">Logos e imágenes subidas</div>
                </div>
            </label>
        </div>

        <div class="text-center">
            <button type="submit" class="inline-flex items-center gap-2 px-8 py-4 rounded-xl text-base font-bold text-white bg-slate-900 hover:bg-slate-800 transition-colors shadow-lg shadow-slate-900/20">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Descargar Selección
            </button>
        </div>
    </form>
</div>

<div class="mt-8 bg-white rounded-2xl shadow-sm border border-slate-200 p-6 md:p-8 text-center">
    <div class="w-20 h-20 bg-amber-50 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-6">
        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m-4 0v9"/></svg>
    </div>
    
    <h3 class="text-xl font-bold text-slate-900 mb-2">Restaurar Copia de Seguridad</h3>
    <p class="text-slate-500 mb-6 max-w-md mx-auto">Sube un archivo .ZIP (respaldo completo) o archivos .JSON individuales (ej: clientes.json) para restaurar tus datos. <strong class="text-rose-500">¡Cuidado! Esto sobrescribirá los datos actuales.</strong></p>

    <form action="{{ route('settings.backup.restore') }}" method="POST" enctype="multipart/form-data" class="max-w-md mx-auto" data-confirm-message="¿Estás seguro? Esto reemplazará todos los datos actuales con los del respaldo." data-confirm-title="Restaurar respaldo">
        @csrf
        <div class="relative mb-4">
            <input type="file" name="backup_file" accept=".zip,.json" required class="block w-full text-sm text-slate-500 file:mr-4 file:py-3 file:px-6 file:rounded-full file:border-0 file:text-sm file:font-bold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 cursor-pointer">
            <p class="text-xs text-slate-400 mt-2">Formatos aceptados: .zip, .json</p>
        </div>
        <button type="submit" class="inline-flex items-center gap-2 px-8 py-3 rounded-xl text-sm font-bold text-slate-900 bg-[#ecfe88] hover:bg-[#d9f99d] transition-colors shadow-lg shadow-lime-900/10">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            Restaurar Datos
        </button>
    </form>
</div>
@endsection
