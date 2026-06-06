@extends('layouts.settings')

@section('title', 'Editar Usuario')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <h2 class="text-2xl font-bold text-slate-900">Editar Usuario</h2>
        <a href="{{ route('settings.team.index') }}" class="px-3 py-2 text-sm text-slate-600 hover:text-slate-900 border border-slate-200 rounded-full hover:bg-white transition-colors">Cancelar</a>
    </div>

    <form action="{{ route('settings.team.update', $user['id']) }}" method="POST" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 md:p-8 space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nombre Completo</label>
                <input type="text" name="name" value="{{ $user['name'] }}" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Correo Electrónico</label>
                <input type="email" name="email" value="{{ $user['email'] }}" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm" required>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Rol</label>
                <select name="role" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm">
                    @foreach($roles as $role)
                        <option value="{{ $role['id'] }}" {{ $user['role'] === $role['id'] ? 'selected' : '' }}>{{ $role['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center pt-6">
                <label class="inline-flex items-center">
                    <input type="checkbox" name="active" value="1" {{ ($user['active'] ?? true) ? 'checked' : '' }} class="form-checkbox text-lime-600 rounded border-gray-300 focus:ring-lime-500 h-5 w-5">
                    <span class="ml-2 text-sm text-slate-700 font-medium">Cuenta Activa</span>
                </label>
            </div>
        </div>

        <div class="border-t border-slate-100 pt-6 mt-2">
            <h4 class="font-bold text-sm text-slate-900 mb-4">Cambiar Contraseña</h4>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nueva Contraseña</label>
                <input type="password" name="password" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] sm:text-sm" placeholder="Dejar en blanco para mantener la actual">
            </div>
        </div>

        <div class="pt-4 border-t border-slate-100 flex justify-end">
            <button type="submit" class="px-6 py-2.5 rounded-xl text-sm font-bold text-slate-900 bg-[#ecfe88] hover:bg-[#d9ea76] transition-colors shadow-sm">
                Guardar Cambios
            </button>
        </div>
    </form>
</div>
@endsection
