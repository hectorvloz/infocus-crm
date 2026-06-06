@extends('layouts.app')
@section('title', 'Mensajes — ' . ($client['empresa'] ?? 'Cliente'))
@section('content')
<div class="max-w-3xl mx-auto space-y-5">

  {{-- Header --}}
  <div class="bg-white rounded-2xl shadow-sm border p-5">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <div class="text-xl font-extrabold text-slate-900">Mensajes del portal</div>
        <div class="text-sm text-slate-500">{{ $client['empresa'] ?? 'Cliente' }}</div>
      </div>
      <a href="{{ route('clientes.show', $client['id']) }}" class="px-4 py-2 rounded-full border text-sm text-slate-700 hover:bg-slate-50 transition-colors">
        ← Volver al cliente
      </a>
    </div>
  </div>

  @if(session('reply_ok'))
    <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 text-emerald-700 text-sm">✅ {{ session('reply_ok') }}</div>
  @endif

  {{-- Conversación --}}
  <div class="bg-white rounded-2xl shadow-sm border p-5">
    <div class="space-y-4 max-h-[500px] overflow-y-auto pr-1" id="chat-box">
      @forelse($all as $msg)
        @php $isTeam = ($msg['from'] ?? '') === 'team'; @endphp
        <div class="flex {{ $isTeam ? 'justify-end' : 'justify-start' }}">
          <div class="max-w-[80%]">
            <div class="text-xs text-slate-400 mb-1 {{ $isTeam ? 'text-right' : '' }}">
              {{ $isTeam ? ($msg['author'] ?? 'Equipo') : ($client['empresa'] ?? 'Cliente') }}
              · {{ \Carbon\Carbon::parse($msg['created_at'])->format('d/m/Y H:i') }}
            </div>
            <div class="{{ $isTeam ? 'bg-slate-800 text-white' : 'bg-lime-100 text-slate-900' }} rounded-2xl {{ $isTeam ? 'rounded-tr-sm' : 'rounded-tl-sm' }} px-4 py-3 text-sm leading-relaxed">
              {{ $msg['message'] }}
            </div>
          </div>
        </div>
      @empty
        <div class="text-center text-slate-400 py-12">
          <svg class="w-10 h-10 mx-auto mb-3 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
          </svg>
          <div class="font-medium">Sin mensajes aún</div>
          <div class="text-xs mt-1">El cliente no ha enviado mensajes desde el portal.</div>
        </div>
      @endforelse
    </div>
  </div>

  {{-- Responder --}}
  <div class="bg-white rounded-2xl shadow-sm border p-5">
    <div class="text-sm font-bold text-slate-700 mb-3">Responder como equipo</div>
    <form method="POST" action="{{ route('clientes.mensajes.reply', $client['id']) }}" class="flex gap-3">
      @csrf
      <textarea name="message" rows="2" class="flex-1 rounded-xl border border-slate-200 px-4 py-3 text-sm resize-none focus:ring-2 focus:ring-lime-300 outline-none" placeholder="Escribe una respuesta…" required></textarea>
      <button type="submit" class="px-5 py-2 rounded-xl bg-slate-900 text-white font-semibold text-sm self-end hover:bg-slate-800 transition-colors">Enviar</button>
    </form>
  </div>

</div>
<script>
  // Scroll al fondo del chat
  const box = document.getElementById('chat-box');
  if (box) box.scrollTop = box.scrollHeight;
</script>
@endsection
