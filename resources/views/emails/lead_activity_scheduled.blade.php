<div style="margin:0 0 16px;">
  <div style="display:inline-block;padding:6px 12px;border-radius:9999px;background:#eef8ca;color:#3f5b00;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;">
    {{ $isMeet ? 'Reunión Programada' : 'Llamada Programada' }}
  </div>
</div>

<h2 style="margin:0 0 10px;font-size:28px;line-height:1.15;color:#0f172a;">
  Hola {{ e($leadName) }}, tenemos una actividad agendada
</h2>

<p style="margin:0 0 18px;color:#475569;font-size:15px;line-height:1.65;">
  Desde <strong>{{ e($appName) }}</strong> programamos la actividad
  <strong>{{ e($title) }}</strong> para ti.
</p>

<div style="margin:0 0 20px;border:1px solid #e2e8f0;border-radius:16px;background:#f8fafc;padding:16px 18px;">
  <div style="margin:0 0 8px;color:#0f172a;font-size:15px;font-weight:700;">Detalles</div>
  <div style="margin:0;color:#475569;font-size:14px;line-height:1.7;">
    <div><strong>Fecha:</strong> {{ e($startAt) }}</div>
    <div><strong>Finaliza:</strong> {{ e($endAt) }}</div>
    <div><strong>Duración:</strong> {{ e((string) $durationMin) }} min</div>
    @if(!empty($phone))
      <div><strong>Teléfono de contacto:</strong> {{ e($phone) }}</div>
    @endif
  </div>
</div>

@if(!empty($description))
  <p style="margin:0 0 20px;color:#334155;font-size:14px;line-height:1.7;">
    {{ e($description) }}
  </p>
@endif

@if($isMeet && !empty($meetUrl))
  <div style="text-align:center;margin:24px 0 8px;">
    <a href="{{ e($meetUrl) }}" target="_blank" rel="noopener" style="display:inline-block;padding:14px 24px;border-radius:9999px;background:#10b981;color:#ffffff;font-size:15px;font-weight:800;text-decoration:none;">
      Unirme a Google Meet
    </a>
  </div>
@endif

<p style="margin:14px 0 0;color:#64748b;font-size:13px;line-height:1.6;">
  Si necesitas reprogramar, responde este correo y te ayudamos.
</p>
