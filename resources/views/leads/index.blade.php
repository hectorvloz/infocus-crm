@extends('layouts.app')
@section('title','Leads')
@section('content')
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="bg-white rounded-2xl shadow border p-6 lg:col-span-2">
      <div class="flex items-center justify-between mb-4">
        <div class="text-xl font-bold">Leads</div>
        <button class="px-4 py-2 rounded-full bg-lime-300 text-slate-900 font-semibold">Nuevo lead</button>
      </div>
      <div class="h-64 rounded-xl bg-neutral-100"></div>
    </div>
    <div class="grid gap-4">
      <div class="bg-white rounded-2xl shadow border p-6">Embudo</div>
      <div class="bg-white rounded-2xl shadow border p-6">Origen</div>
    </div>
  </div>
@endsection
