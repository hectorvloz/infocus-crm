@extends('layouts.settings')

@section('title', 'Nuevo Usuario')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <h2 class="text-2xl font-bold text-slate-900">Agregar Usuario</h2>
        <a href="{{ route('settings.team.index') }}" class="px-3 py-2 text-sm text-slate-600 hover:text-slate-900 border border-slate-200 rounded-full hover:bg-white transition-colors">Cancelar</a>
    </div>

    <form action="{{ route('settings.team.store') }}" method="POST" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 md:p-8 space-y-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nombre Completo</label>
                <input type="text" name="name" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Correo Electrónico</label>
                <input type="email" name="email" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm" required>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Rol</label>
            <select name="role" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm">
                @foreach($roles as $role)
                    <option value="{{ $role['id'] }}">{{ $role['name'] }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-slate-400">Determina los permisos de acceso.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Contraseña</label>
            <input type="password" name="password" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm" required minlength="6">
            <p class="mt-1 text-xs text-slate-400">Mínimo 6 caracteres.</p>
        </div>

        <div class="pt-4 border-t border-slate-100 flex justify-end">
            <button type="submit" class="px-6 py-2.5 rounded-xl text-sm font-bold text-slate-900 bg-[#ecfe88] hover:bg-[#d9ea76] transition-colors shadow-sm">
                Crear Usuario
            </button>
        </div>
    </form>
</div>
@endsection
