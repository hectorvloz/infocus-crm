@extends('layouts.settings')

@section('title', 'Plantillas de Correo')

@section('content')
<div class="mb-8">
    <h2 class="text-2xl font-bold text-slate-900">Plantillas de Correo</h2>
    <p class="text-slate-500">Edita por separado el contenido de tus plantillas y el estilo global del header/footer.</p>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <form action="{{ route('settings.templates.update') }}" method="POST" id="templatesForm">
        @csrf
        @method('PUT')

        <div class="p-5 border-b border-slate-100 bg-slate-50 flex flex-col md:flex-row items-center justify-between gap-3">
            <div class="inline-flex rounded-xl border border-slate-200 bg-white p-1 w-full md:w-auto">
                <button type="button" data-panel-btn="content" class="panel-btn flex-1 md:flex-none px-4 py-2 rounded-lg text-sm font-semibold text-slate-900 bg-lime-200">Contenido de plantillas</button>
                <button type="button" data-panel-btn="style" class="panel-btn flex-1 md:flex-none px-4 py-2 rounded-lg text-sm font-semibold text-slate-600">Header y Footer global</button>
            </div>
            <button type="submit" class="w-full md:w-auto px-6 py-2.5 rounded-xl text-sm font-bold text-slate-900 bg-[#ecfe88] hover:bg-[#d9ea76] transition-colors shadow-sm">
                Guardar Cambios
            </button>
        </div>

        <div id="panel-content" class="panel-section">
            <div class="grid grid-cols-1 xl:grid-cols-4">
                <div class="xl:col-span-3 p-5 md:p-6 border-b xl:border-b-0 xl:border-r border-slate-100">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Seleccionar plantilla</label>
                            <select id="templateSelector" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] text-sm">
                                <option value="invoice">Nueva Factura (Ventas)</option>
                                <option value="welcome">Bienvenida Nuevo Usuario (Usuarios)</option>
                                <option value="password_reset">Restablecer Contraseña (Seguridad)</option>
                                <option value="project_created">Nuevo Proyecto Asignado (Proyectos)</option>
                                <option value="weekly_hours_user">Resumen semanal de horas (Usuario)</option>
                                <option value="monthly_hours_user">Resumen mensual de horas (Usuario)</option>
                                <option value="payment_received">Pago recibido (Confirmación)</option>
                                <option value="invoice_paid">Factura pagada (Confirmación)</option>
                                <option value="invoice_due">Factura por vencer (Cliente)</option>
                                <option value="team_welcome">Bienvenida nuevo miembro</option>
                                <option value="role_permissions_changed">Cambio de rol/permisos</option>
                                <option value="system_critical_alert">Alerta crítica del sistema</option>
                                <option value="meeting_scheduled">Reunión programada (Reuniones)</option>
                                <option value="meeting_reminder">Recordatorio reunión (Reuniones)</option>
                                <option value="lead_meet_scheduled">Reunión Lead programada (Leads)</option>
                                <option value="lead_meet_reminder">Recordatorio reunión Lead (Leads)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Asunto del correo</label>
                            <input type="text" id="visible_subject" class="block w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] text-sm font-medium text-slate-900" placeholder="Asunto...">
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Cuerpo del mensaje</label>
                        <div class="rounded-xl border border-slate-300 overflow-hidden">
                            <div class="bg-slate-50 border-b border-slate-200 p-2 flex flex-wrap gap-2">
                                <button type="button" class="fmt-btn px-2.5 py-1 rounded-lg text-xs font-semibold border border-slate-300 bg-white text-slate-700" data-action="bold">Negrita</button>
                                <button type="button" class="fmt-btn px-2.5 py-1 rounded-lg text-xs font-semibold border border-slate-300 bg-white text-slate-700" data-action="italic">Itálica</button>
                                <button type="button" class="fmt-btn px-2.5 py-1 rounded-lg text-xs font-semibold border border-slate-300 bg-white text-slate-700" data-action="link">Link</button>
                                <button type="button" class="fmt-btn px-2.5 py-1 rounded-lg text-xs font-semibold border border-slate-300 bg-white text-slate-700" data-action="ul">Lista</button>
                                <button type="button" class="fmt-btn px-2.5 py-1 rounded-lg text-xs font-semibold border border-slate-300 bg-white text-slate-700" data-action="divider">Separador</button>
                            </div>
                            <textarea id="visible_editor" class="w-full min-h-[360px] p-4 text-sm text-slate-800 focus:outline-none resize-y" placeholder="Escribe el contenido HTML de tu plantilla..."></textarea>
                        </div>
                        <p class="text-xs text-slate-400 mt-2">Editor HTML ligero sin dependencias externas.</p>
                    </div>

                    <div class="hidden">
                        <input type="text" name="template_invoice_subject" id="input_subject_invoice" value="{{ $settings['template_invoice_subject'] ?? $settings['email_subject_invoice'] ?? 'Nueva factura #{folio} de {empresa}' }}">
                        <textarea name="template_invoice_body" id="input_body_invoice">{{ $settings['template_invoice_body'] ?? $settings['email_body_invoice'] ?? "Hola {cliente},\n\nAdjunto encontrarás la factura #{folio} por un total de {total}.\nLa fecha de vencimiento es {vencimiento}.\n\nPuedes realizar tu pago aquí: {link_pago}\n\nGracias por tu preferencia,\n{empresa}" }}</textarea>

                        <input type="text" name="template_welcome_subject" id="input_subject_welcome" value="{{ $settings['template_welcome_subject'] ?? 'Bienvenido a {empresa}' }}">
                        <textarea name="template_welcome_body" id="input_body_welcome">{{ $settings['template_welcome_body'] ?? "Hola {nombre},\n\nTu cuenta en {empresa} ha sido creada exitosamente.\n\nTus credenciales de acceso son:\nEmail: {email}\nContraseña: {password}\n\nIngresa aquí: {login_url}\n\nSaludos,\nEl equipo de {empresa}" }}</textarea>

                        <input type="text" name="template_password_reset_subject" id="input_subject_password_reset" value="{{ $settings['template_password_reset_subject'] ?? 'Restablecer contraseña - {empresa}' }}">
                        <textarea name="template_password_reset_body" id="input_body_password_reset">{{ $settings['template_password_reset_body'] ?? "Hola {nombre},\n\nHemos recibido una solicitud para restablecer tu contraseña.\n\nHaz clic en el siguiente enlace para continuar:\n{reset_url}\n\nSi no solicitaste esto, puedes ignorar este correo.\n\nSaludos,\n{empresa}" }}</textarea>

                        <input type="text" name="template_project_created_subject" id="input_subject_project_created" value="{{ $settings['template_project_created_subject'] ?? 'Nuevo Proyecto Asignado: {proyecto}' }}">
                        <textarea name="template_project_created_body" id="input_body_project_created">{{ $settings['template_project_created_body'] ?? "Hola {cliente},\n\nSe ha creado un nuevo proyecto: {proyecto}.\n\nPuedes ver los detalles y el progreso en el siguiente enlace:\n{link_proyecto}\n\nEstamos a tus órdenes,\n{empresa}" }}</textarea>

                        <input type="text" name="template_weekly_hours_user_subject" id="input_subject_weekly_hours_user" value="{{ $settings['template_weekly_hours_user_subject'] ?? 'Resumen semanal de horas - {semana_rango}' }}">
                        <textarea name="template_weekly_hours_user_body" id="input_body_weekly_hours_user">{{ $settings['template_weekly_hours_user_body'] ?? "Hola {usuario_nombre},\n\nEste es tu resumen semanal de horas trabajadas del {semana_inicio} al {semana_fin}.\n\nTotal de horas: {total_horas_semana}\nHoras facturables: {total_horas_facturables}\nHoras no facturables: {total_horas_no_facturables}\n\nDesglose por proyecto:\n{proyectos_desglose_html}\n\nSaludos,\n{empresa}" }}</textarea>

                        <input type="text" name="template_monthly_hours_user_subject" id="input_subject_monthly_hours_user" value="{{ $settings['template_monthly_hours_user_subject'] ?? 'Resumen mensual de horas - {mes_nombre}' }}">
                        <textarea name="template_monthly_hours_user_body" id="input_body_monthly_hours_user">{{ $settings['template_monthly_hours_user_body'] ?? "Hola {usuario_nombre},\n\nEste es tu resumen mensual de horas de {mes_nombre}.\n\nTotal de horas: {total_horas_mes}\nTotal proyectos: {total_proyectos}\n\nDesglose por proyecto:\n{proyectos_desglose_html}\n\nSaludos,\n{empresa}" }}</textarea>

                        <input type="text" name="template_payment_received_subject" id="input_subject_payment_received" value="{{ $settings['template_payment_received_subject'] ?? 'Pago recibido - Factura {folio}' }}">
                        <textarea name="template_payment_received_body" id="input_body_payment_received">{{ $settings['template_payment_received_body'] ?? "Hola {cliente},\n\nHemos confirmado la recepción de tu pago por {monto_pagado} correspondiente a la factura {folio}.\n\nFecha de pago: {fecha_pago}\nMétodo de pago: {metodo_pago}\nSaldo restante: {saldo_restante}\n\nGracias por tu pago.\n{empresa}" }}</textarea>

                        <input type="text" name="template_invoice_paid_subject" id="input_subject_invoice_paid" value="{{ $settings['template_invoice_paid_subject'] ?? 'Factura {folio} pagada' }}">
                        <textarea name="template_invoice_paid_body" id="input_body_invoice_paid">{{ $settings['template_invoice_paid_body'] ?? "Hola {cliente},\n\nTu factura {folio} ya está pagada.\n\nTotal: {total}\nFecha de pago: {fecha_pago}\nMétodo: {metodo_pago}\n\nGracias por tu pago.\n{empresa}" }}</textarea>

                        <input type="text" name="template_invoice_due_subject" id="input_subject_invoice_due" value="{{ $settings['template_invoice_due_subject'] ?? 'Tu factura {folio} vence en {dias_restantes} días' }}">
                        <textarea name="template_invoice_due_body" id="input_body_invoice_due">{{ $settings['template_invoice_due_body'] ?? "Hola {cliente},\n\nTe recordamos que la factura {folio} por {total} vence el {vencimiento}.\n\nDías restantes: {dias_restantes}\nEnlace de pago: {link_pago}\n\nSi ya realizaste el pago, puedes ignorar este mensaje.\n{empresa}" }}</textarea>

                        <input type="text" name="template_team_welcome_subject" id="input_subject_team_welcome" value="{{ $settings['template_team_welcome_subject'] ?? 'Bienvenido(a) al equipo de {empresa}' }}">
                        <textarea name="template_team_welcome_body" id="input_body_team_welcome">{{ $settings['template_team_welcome_body'] ?? "Hola {nombre},\n\n¡Bienvenido(a) al equipo de {empresa}!\n\nTu rol inicial es: {rol}\nCorreo de acceso: {email}\nClave temporal: {password}\nAcceso al sistema: {login_url}\n\nNos alegra tenerte con nosotros.\n{empresa}" }}</textarea>

                        <input type="text" name="template_role_permissions_changed_subject" id="input_subject_role_permissions_changed" value="{{ $settings['template_role_permissions_changed_subject'] ?? 'Actualización de rol/permisos en {empresa}' }}">
                        <textarea name="template_role_permissions_changed_body" id="input_body_role_permissions_changed">{{ $settings['template_role_permissions_changed_body'] ?? "Hola {nombre},\n\nTu rol ha sido actualizado.\n\nRol anterior: {rol_anterior}\nNuevo rol: {rol_nuevo}\nPermisos principales: {permisos_resumen}\nFecha de cambio: {fecha_cambio}\n\nSi tienes dudas, contacta al administrador.\n{empresa}" }}</textarea>

                        <input type="text" name="template_system_critical_alert_subject" id="input_subject_system_critical_alert" value="{{ $settings['template_system_critical_alert_subject'] ?? 'Alerta crítica: {tipo_alerta}' }}">
                        <textarea name="template_system_critical_alert_body" id="input_body_system_critical_alert">{{ $settings['template_system_critical_alert_body'] ?? "Hola {nombre_admin},\n\nSe detectó una alerta crítica en el sistema.\n\nTipo: {tipo_alerta}\nServicio: {servicio}\nDetalle: {detalle_error}\nFecha y hora: {fecha_hora}\n\nRevisa el módulo de integraciones/sistema para tomar acción.\n{empresa}" }}</textarea>

                        <input type="text" name="template_meeting_scheduled_subject" id="input_subject_meeting_scheduled" value="{{ $settings['template_meeting_scheduled_subject'] ?? 'Invitación: {reunion_titulo}' }}">
                        <textarea name="template_meeting_scheduled_body" id="input_body_meeting_scheduled">{{ $settings['template_meeting_scheduled_body'] ?? "<p>Hola <strong>{destinatario_nombre}</strong>,</p><p>Has sido invitado(a) a una reunión desde <strong>{empresa}</strong>.</p><p><strong>Título:</strong> {reunion_titulo}<br><strong>Cliente:</strong> {cliente}<br><strong>Fecha:</strong> {fecha_inicio}<br><strong>Finaliza:</strong> {fecha_fin}<br><strong>Duración:</strong> {duracion_min} min<br><strong>Ubicación:</strong> {ubicacion}</p><p>{descripcion}</p><p>{meet_button}</p><p>{calendar_button}</p>" }}</textarea>

                        <input type="text" name="template_meeting_reminder_subject" id="input_subject_meeting_reminder" value="{{ $settings['template_meeting_reminder_subject'] ?? 'Recordatorio: {reunion_titulo} en {minutos_antes} min' }}">
                        <textarea name="template_meeting_reminder_body" id="input_body_meeting_reminder">{{ $settings['template_meeting_reminder_body'] ?? "<p>Hola <strong>{destinatario_nombre}</strong>,</p><p>Este es un recordatorio de tu reunión en <strong>{minutos_antes} minutos</strong>.</p><p><strong>Título:</strong> {reunion_titulo}<br><strong>Cliente:</strong> {cliente}<br><strong>Inicio:</strong> {fecha_inicio}<br><strong>Fin:</strong> {fecha_fin}<br><strong>Duración:</strong> {duracion_min} min</p><p>{descripcion}</p><p>{meet_button}</p>" }}</textarea>

                        <input type="text" name="template_lead_meet_scheduled_subject" id="input_subject_lead_meet_scheduled" value="{{ $settings['template_lead_meet_scheduled_subject'] ?? 'Reunión programada: {actividad_titulo}' }}">
                        <textarea name="template_lead_meet_scheduled_body" id="input_body_lead_meet_scheduled">{{ $settings['template_lead_meet_scheduled_body'] ?? "<p>Hola <strong>{lead_nombre}</strong>,</p><p>Tu reunión fue programada desde <strong>{empresa}</strong>.</p><p><strong>Título:</strong> {actividad_titulo}<br><strong>Fecha:</strong> {fecha_inicio}<br><strong>Finaliza:</strong> {fecha_fin}<br><strong>Duración:</strong> {duracion_min} min</p><p>{descripcion}</p><p>{meet_button}</p>" }}</textarea>

                        <input type="text" name="template_lead_meet_reminder_subject" id="input_subject_lead_meet_reminder" value="{{ $settings['template_lead_meet_reminder_subject'] ?? 'Recordatorio: {actividad_titulo} en {minutos_antes} min' }}">
                        <textarea name="template_lead_meet_reminder_body" id="input_body_lead_meet_reminder">{{ $settings['template_lead_meet_reminder_body'] ?? "<p>Hola <strong>{destinatario_nombre}</strong>,</p><p>Este es un recordatorio de tu reunión en {minutos_antes} minutos.</p><p><strong>Título:</strong> {actividad_titulo}<br><strong>Fecha:</strong> {fecha_inicio}<br><strong>Finaliza:</strong> {fecha_fin}<br><strong>Duración:</strong> {duracion_min} min</p><p>{descripcion}</p><p>{meet_button}</p>" }}</textarea>
                    </div>
                </div>

                <div class="p-5 md:p-6 bg-slate-50">
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Variables (clic para insertar)</h4>

                    <div id="vars-invoice" class="vars-list grid grid-cols-2 xl:grid-cols-1 gap-2">
                        @foreach(['{cliente}', '{folio}', '{total}', '{vencimiento}', '{empresa}', '{link_pago}'] as $v)
                            <button type="button" onclick="insertVar('{{ $v }}')" class="w-full text-left px-3 py-2 rounded-lg bg-white border border-slate-200 text-xs font-mono text-slate-600 hover:border-lime-400 hover:text-lime-700 transition-colors shadow-sm truncate" title="{{ $v }}">{{ $v }}</button>
                        @endforeach
                    </div>

                    <div id="vars-welcome" class="vars-list grid grid-cols-2 xl:grid-cols-1 gap-2 hidden">
                        @foreach(['{nombre}', '{email}', '{password}', '{login_url}', '{empresa}'] as $v)
                            <button type="button" onclick="insertVar('{{ $v }}')" class="w-full text-left px-3 py-2 rounded-lg bg-white border border-slate-200 text-xs font-mono text-slate-600 hover:border-lime-400 hover:text-lime-700 transition-colors shadow-sm truncate" title="{{ $v }}">{{ $v }}</button>
                        @endforeach
                    </div>

                    <div id="vars-password_reset" class="vars-list grid grid-cols-2 xl:grid-cols-1 gap-2 hidden">
                        @foreach(['{nombre}', '{reset_url}', '{empresa}'] as $v)
                            <button type="button" onclick="insertVar('{{ $v }}')" class="w-full text-left px-3 py-2 rounded-lg bg-white border border-slate-200 text-xs font-mono text-slate-600 hover:border-lime-400 hover:text-lime-700 transition-colors shadow-sm truncate" title="{{ $v }}">{{ $v }}</button>
                        @endforeach
                    </div>

                    <div id="vars-project_created" class="vars-list grid grid-cols-2 xl:grid-cols-1 gap-2 hidden">
                        @foreach(['{cliente}', '{proyecto}', '{link_proyecto}', '{empresa}'] as $v)
                            <button type="button" onclick="insertVar('{{ $v }}')" class="w-full text-left px-3 py-2 rounded-lg bg-white border border-slate-200 text-xs font-mono text-slate-600 hover:border-lime-400 hover:text-lime-700 transition-colors shadow-sm truncate" title="{{ $v }}">{{ $v }}</button>
                        @endforeach
                    </div>

                    <div id="vars-weekly_hours_user" class="vars-list grid grid-cols-2 xl:grid-cols-1 gap-2 hidden">
                        @foreach(['{usuario_nombre}', '{semana_inicio}', '{semana_fin}', '{semana_rango}', '{total_horas_semana}', '{total_horas_facturables}', '{total_horas_no_facturables}', '{proyectos_desglose_html}', '{empresa}'] as $v)
                            <button type="button" onclick="insertVar('{{ $v }}')" class="w-full text-left px-3 py-2 rounded-lg bg-white border border-slate-200 text-xs font-mono text-slate-600 hover:border-lime-400 hover:text-lime-700 transition-colors shadow-sm truncate" title="{{ $v }}">{{ $v }}</button>
                        @endforeach
                    </div>

                    <div id="vars-monthly_hours_user" class="vars-list grid grid-cols-2 xl:grid-cols-1 gap-2 hidden">
                        @foreach(['{usuario_nombre}', '{mes_nombre}', '{total_horas_mes}', '{total_proyectos}', '{proyectos_desglose_html}', '{empresa}'] as $v)
                            <button type="button" onclick="insertVar('{{ $v }}')" class="w-full text-left px-3 py-2 rounded-lg bg-white border border-slate-200 text-xs font-mono text-slate-600 hover:border-lime-400 hover:text-lime-700 transition-colors shadow-sm truncate" title="{{ $v }}">{{ $v }}</button>
                        @endforeach
                    </div>

                    <div id="vars-payment_received" class="vars-list grid grid-cols-2 xl:grid-cols-1 gap-2 hidden">
                        @foreach(['{cliente}', '{folio}', '{monto_pagado}', '{fecha_pago}', '{metodo_pago}', '{saldo_restante}', '{empresa}'] as $v)
                            <button type="button" onclick="insertVar('{{ $v }}')" class="w-full text-left px-3 py-2 rounded-lg bg-white border border-slate-200 text-xs font-mono text-slate-600 hover:border-lime-400 hover:text-lime-700 transition-colors shadow-sm truncate" title="{{ $v }}">{{ $v }}</button>
                        @endforeach
                    </div>

                    <div id="vars-invoice_paid" class="vars-list grid grid-cols-2 xl:grid-cols-1 gap-2 hidden">
                        @foreach(['{cliente}', '{folio}', '{total}', '{fecha_pago}', '{metodo_pago}', '{empresa}', '{cta_buttons}'] as $v)
                            <button type="button" onclick="insertVar('{{ $v }}')" class="w-full text-left px-3 py-2 rounded-lg bg-white border border-slate-200 text-xs font-mono text-slate-600 hover:border-lime-400 hover:text-lime-700 transition-colors shadow-sm truncate" title="{{ $v }}">{{ $v }}</button>
                        @endforeach
                    </div>

                    <div id="vars-invoice_due" class="vars-list grid grid-cols-2 xl:grid-cols-1 gap-2 hidden">
                        @foreach(['{cliente}', '{folio}', '{total}', '{vencimiento}', '{dias_restantes}', '{link_pago}', '{empresa}'] as $v)
                            <button type="button" onclick="insertVar('{{ $v }}')" class="w-full text-left px-3 py-2 rounded-lg bg-white border border-slate-200 text-xs font-mono text-slate-600 hover:border-lime-400 hover:text-lime-700 transition-colors shadow-sm truncate" title="{{ $v }}">{{ $v }}</button>
                        @endforeach
                    </div>

                    <div id="vars-team_welcome" class="vars-list grid grid-cols-2 xl:grid-cols-1 gap-2 hidden">
                        @foreach(['{nombre}', '{rol}', '{email}', '{password}', '{login_url}', '{empresa}'] as $v)
                            <button type="button" onclick="insertVar('{{ $v }}')" class="w-full text-left px-3 py-2 rounded-lg bg-white border border-slate-200 text-xs font-mono text-slate-600 hover:border-lime-400 hover:text-lime-700 transition-colors shadow-sm truncate" title="{{ $v }}">{{ $v }}</button>
                        @endforeach
                    </div>

                    <div id="vars-role_permissions_changed" class="vars-list grid grid-cols-2 xl:grid-cols-1 gap-2 hidden">
                        @foreach(['{nombre}', '{rol_anterior}', '{rol_nuevo}', '{permisos_resumen}', '{fecha_cambio}', '{empresa}'] as $v)
                            <button type="button" onclick="insertVar('{{ $v }}')" class="w-full text-left px-3 py-2 rounded-lg bg-white border border-slate-200 text-xs font-mono text-slate-600 hover:border-lime-400 hover:text-lime-700 transition-colors shadow-sm truncate" title="{{ $v }}">{{ $v }}</button>
                        @endforeach
                    </div>

                    <div id="vars-system_critical_alert" class="vars-list grid grid-cols-2 xl:grid-cols-1 gap-2 hidden">
                        @foreach(['{nombre_admin}', '{tipo_alerta}', '{servicio}', '{detalle_error}', '{fecha_hora}', '{empresa}'] as $v)
                            <button type="button" onclick="insertVar('{{ $v }}')" class="w-full text-left px-3 py-2 rounded-lg bg-white border border-slate-200 text-xs font-mono text-slate-600 hover:border-lime-400 hover:text-lime-700 transition-colors shadow-sm truncate" title="{{ $v }}">{{ $v }}</button>
                        @endforeach
                    </div>

                    <div id="vars-meeting_scheduled" class="vars-list grid grid-cols-2 xl:grid-cols-1 gap-2 hidden">
                        @foreach(['{destinatario_nombre}', '{empresa}', '{reunion_titulo}', '{cliente}', '{fecha_inicio}', '{fecha_fin}', '{duracion_min}', '{ubicacion}', '{descripcion}', '{meet_url}', '{meet_button}', '{calendar_url}', '{calendar_button}'] as $v)
                            <button type="button" onclick="insertVar('{{ $v }}')" class="w-full text-left px-3 py-2 rounded-lg bg-white border border-slate-200 text-xs font-mono text-slate-600 hover:border-lime-400 hover:text-lime-700 transition-colors shadow-sm truncate" title="{{ $v }}">{{ $v }}</button>
                        @endforeach
                    </div>

                    <div id="vars-meeting_reminder" class="vars-list grid grid-cols-2 xl:grid-cols-1 gap-2 hidden">
                        @foreach(['{destinatario_nombre}', '{empresa}', '{reunion_titulo}', '{cliente}', '{fecha_inicio}', '{fecha_fin}', '{duracion_min}', '{minutos_antes}', '{ubicacion}', '{descripcion}', '{meet_url}', '{meet_button}'] as $v)
                            <button type="button" onclick="insertVar('{{ $v }}')" class="w-full text-left px-3 py-2 rounded-lg bg-white border border-slate-200 text-xs font-mono text-slate-600 hover:border-lime-400 hover:text-lime-700 transition-colors shadow-sm truncate" title="{{ $v }}">{{ $v }}</button>
                        @endforeach
                    </div>

                    <div id="vars-lead_meet_scheduled" class="vars-list grid grid-cols-2 xl:grid-cols-1 gap-2 hidden">
                        @foreach(['{lead_nombre}', '{empresa}', '{actividad_titulo}', '{fecha_inicio}', '{fecha_fin}', '{duracion_min}', '{descripcion}', '{meet_url}', '{meet_button}'] as $v)
                            <button type="button" onclick="insertVar('{{ $v }}')" class="w-full text-left px-3 py-2 rounded-lg bg-white border border-slate-200 text-xs font-mono text-slate-600 hover:border-lime-400 hover:text-lime-700 transition-colors shadow-sm truncate" title="{{ $v }}">{{ $v }}</button>
                        @endforeach
                    </div>

                    <div id="vars-lead_meet_reminder" class="vars-list grid grid-cols-2 xl:grid-cols-1 gap-2 hidden">
                        @foreach(['{destinatario_nombre}', '{lead_nombre}', '{empresa}', '{actividad_titulo}', '{fecha_inicio}', '{fecha_fin}', '{duracion_min}', '{minutos_antes}', '{descripcion}', '{meet_url}', '{meet_button}'] as $v)
                            <button type="button" onclick="insertVar('{{ $v }}')" class="w-full text-left px-3 py-2 rounded-lg bg-white border border-slate-200 text-xs font-mono text-slate-600 hover:border-lime-400 hover:text-lime-700 transition-colors shadow-sm truncate" title="{{ $v }}">{{ $v }}</button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div id="panel-style" class="panel-section hidden border-t border-slate-100">
            <div class="p-5 md:p-6 grid grid-cols-1 xl:grid-cols-3 gap-4">
                <div class="xl:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Texto pequeño del header</label>
                        <input type="text" id="mail_header_label" name="mail_header_label" value="{{ $settings['mail_header_label'] ?? 'Mensaje automatizado' }}" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] text-sm" placeholder="Mensaje automatizado">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Texto del footer</label>
                        <input type="text" id="mail_footer_note" name="mail_footer_note" value="{{ $settings['mail_footer_note'] ?? '' }}" class="block w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-[#ecfe88] focus:border-[#ecfe88] text-sm" placeholder="Mensaje legal o soporte para tus clientes">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Header inicio</label>
                            <input type="color" id="mail_header_gradient_from" name="mail_header_gradient_from" value="{{ $settings['mail_header_gradient_from'] ?? '#0f172a' }}" class="h-10 w-full border border-slate-300 rounded-xl bg-white p-1">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Header fin</label>
                            <input type="color" id="mail_header_gradient_to" name="mail_header_gradient_to" value="{{ $settings['mail_header_gradient_to'] ?? '#1580c6' }}" class="h-10 w-full border border-slate-300 rounded-xl bg-white p-1">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Acento Header</label>
                            <input type="color" id="mail_header_accent" name="mail_header_accent" value="{{ $settings['mail_header_accent'] ?? '#d7f171' }}" class="h-10 w-full border border-slate-300 rounded-xl bg-white p-1">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Texto Header</label>
                            <input type="color" id="mail_header_text_color" name="mail_header_text_color" value="{{ $settings['mail_header_text_color'] ?? '#ffffff' }}" class="h-10 w-full border border-slate-300 rounded-xl bg-white p-1">
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-2 md:col-span-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Fondo Footer</label>
                            <input type="color" id="mail_footer_bg" name="mail_footer_bg" value="{{ $settings['mail_footer_bg'] ?? '#f8fafc' }}" class="h-10 w-full border border-slate-300 rounded-xl bg-white p-1">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Texto Footer</label>
                            <input type="color" id="mail_footer_text_color" name="mail_footer_text_color" value="{{ $settings['mail_footer_text_color'] ?? '#64748b' }}" class="h-10 w-full border border-slate-300 rounded-xl bg-white p-1">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Color enlaces</label>
                            <input type="color" id="mail_link_color" name="mail_link_color" value="{{ $settings['mail_link_color'] ?? '#0b6fb8' }}" class="h-10 w-full border border-slate-300 rounded-xl bg-white p-1">
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Preview estilo</p>
                    <div class="rounded-xl overflow-hidden border border-slate-200 bg-white shadow-sm">
                        <div id="mail-preview-header" class="px-4 py-3" style="background:linear-gradient(135deg, {{ $settings['mail_header_gradient_from'] ?? '#0f172a' }} 0%, {{ $settings['mail_header_gradient_to'] ?? '#1580c6' }} 100%);">
                            <div id="mail-preview-eyebrow" class="text-[10px] uppercase tracking-[0.16em] font-bold" style="color: {{ $settings['mail_header_accent'] ?? '#d7f171' }};">{{ $settings['mail_header_label'] ?? 'Mensaje automatizado' }}</div>
                            <div id="mail-preview-brand" class="text-base font-bold mt-1" style="color: {{ $settings['mail_header_text_color'] ?? '#ffffff' }};">{{ $settings['company_name'] ?? 'Tu Empresa' }}</div>
                        </div>
                        <div class="px-4 py-3 text-[12px] text-slate-600">Cuerpo del correo con contenido dinámico...</div>
                        <div id="mail-preview-footer" class="px-4 py-3 border-t border-slate-200" style="background: {{ $settings['mail_footer_bg'] ?? '#f8fafc' }}; color: {{ $settings['mail_footer_text_color'] ?? '#64748b' }};">
                            <span id="mail-preview-footer-note">{{ $settings['mail_footer_note'] ?? 'Mensaje legal o información de soporte.' }}</span>
                            <div class="mt-1 text-[11px]" id="mail-preview-link" style="color: {{ $settings['mail_link_color'] ?? '#0b6fb8' }};">{{ $settings['invoice_website'] ?? 'tu-sitio.com' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    let currentKey = 'invoice';

    const subjectInput = document.getElementById('visible_subject');
    const editor = document.getElementById('visible_editor');
    const selector = document.getElementById('templateSelector');

    function syncVisibleToHidden() {
        const subject = document.getElementById('input_subject_' + currentKey);
        const body = document.getElementById('input_body_' + currentKey);
        if (subject) subject.value = subjectInput.value;
        if (body) body.value = editor.value;
    }

    function loadContent(key) {
        const savedSubject = document.getElementById('input_subject_' + key)?.value || '';
        const savedBody = document.getElementById('input_body_' + key)?.value || '';
        subjectInput.value = savedSubject;
        editor.value = savedBody;
    }

    function switchTemplate(key) {
        syncVisibleToHidden();
        currentKey = key;
        loadContent(key);
        document.querySelectorAll('.vars-list').forEach(el => el.classList.add('hidden'));
        const vars = document.getElementById('vars-' + key);
        if (vars) vars.classList.remove('hidden');
    }

    function insertAtCursor(text) {
        const start = editor.selectionStart;
        const end = editor.selectionEnd;
        const before = editor.value.substring(0, start);
        const selected = editor.value.substring(start, end);
        const after = editor.value.substring(end);
        editor.value = before + text.replace('{sel}', selected || '') + after;
        const cursor = before.length + text.length;
        editor.focus();
        editor.setSelectionRange(cursor, cursor);
        syncVisibleToHidden();
    }

    function wrapSelection(openTag, closeTag) {
        const start = editor.selectionStart;
        const end = editor.selectionEnd;
        const before = editor.value.substring(0, start);
        const selected = editor.value.substring(start, end) || 'texto';
        const after = editor.value.substring(end);
        editor.value = before + openTag + selected + closeTag + after;
        const newEnd = before.length + openTag.length + selected.length + closeTag.length;
        editor.focus();
        editor.setSelectionRange(newEnd, newEnd);
        syncVisibleToHidden();
    }

    function insertVar(text) {
        insertAtCursor(text);
    }

    document.querySelectorAll('.fmt-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const action = btn.dataset.action;
            if (action === 'bold') wrapSelection('<strong>', '</strong>');
            if (action === 'italic') wrapSelection('<em>', '</em>');
            if (action === 'link') wrapSelection('<a href="#">', '</a>');
            if (action === 'ul') insertAtCursor('<ul>\n  <li>{sel}</li>\n</ul>');
            if (action === 'divider') insertAtCursor('<hr style="border:none;border-top:1px solid #e2e8f0;margin:18px 0;">');
        });
    });

    subjectInput.addEventListener('input', syncVisibleToHidden);
    editor.addEventListener('input', syncVisibleToHidden);
    selector.addEventListener('change', e => switchTemplate(e.target.value));

    document.getElementById('templatesForm').addEventListener('submit', syncVisibleToHidden);

    document.querySelectorAll('[data-panel-btn]').forEach(btn => {
        btn.addEventListener('click', () => {
            const panel = btn.dataset.panelBtn;
            document.querySelectorAll('[data-panel-btn]').forEach(b => {
                b.classList.remove('bg-lime-200', 'text-slate-900');
                b.classList.add('text-slate-600');
            });
            btn.classList.add('bg-lime-200', 'text-slate-900');
            btn.classList.remove('text-slate-600');

            document.getElementById('panel-content').classList.toggle('hidden', panel !== 'content');
            document.getElementById('panel-style').classList.toggle('hidden', panel !== 'style');
        });
    });

    const styleBindings = [
        { input: 'mail_header_label', target: 'mail-preview-eyebrow', css: 'text' },
        { input: 'mail_footer_note', target: 'mail-preview-footer-note', css: 'text' },
        { input: 'mail_header_accent', target: 'mail-preview-eyebrow', css: 'color' },
        { input: 'mail_header_text_color', target: 'mail-preview-brand', css: 'color' },
        { input: 'mail_footer_bg', target: 'mail-preview-footer', css: 'backgroundColor' },
        { input: 'mail_footer_text_color', target: 'mail-preview-footer', css: 'color' },
        { input: 'mail_link_color', target: 'mail-preview-link', css: 'color' },
    ];

    function refreshHeaderGradient() {
        const from = document.getElementById('mail_header_gradient_from')?.value || '#0f172a';
        const to = document.getElementById('mail_header_gradient_to')?.value || '#1580c6';
        const header = document.getElementById('mail-preview-header');
        if (header) header.style.background = `linear-gradient(135deg, ${from} 0%, ${to} 100%)`;
    }

    styleBindings.forEach(({ input, target, css }) => {
        const inp = document.getElementById(input);
        const out = document.getElementById(target);
        if (!inp || !out) return;
        inp.addEventListener('input', () => {
            if (css === 'text') out.textContent = inp.value || out.textContent;
            if (css === 'color') out.style.color = inp.value;
            if (css === 'backgroundColor') out.style.backgroundColor = inp.value;
        });
    });

    const gradFrom = document.getElementById('mail_header_gradient_from');
    const gradTo = document.getElementById('mail_header_gradient_to');
    if (gradFrom) gradFrom.addEventListener('input', refreshHeaderGradient);
    if (gradTo) gradTo.addEventListener('input', refreshHeaderGradient);

    loadContent(currentKey);
    switchTemplate(currentKey);
</script>
@endsection
