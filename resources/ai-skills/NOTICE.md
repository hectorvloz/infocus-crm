# Skills del agente Infocus AI

Guías de trabajo que el agente carga según el pedido. Cada archivo `.md` tiene:

- `name`, `title`: identificador y nombre visible.
- `triggers`: palabras o frases que activan la skill (sin importar tildes ni mayúsculas).
- `sections`: rutas de Infocus donde la skill tiene prioridad (ej. `/leads`).
- `always: true`: se incluye en todos los pedidos (solo para reglas base, cortas).

Para agregar una skill basta con crear otro archivo con el mismo formato.

## Atribución

Estas skills están adaptadas, traducidas y reescritas para Infocus a partir de
las skills de `anthropics/knowledge-work-plugins` (https://github.com/anthropics/knowledge-work-plugins),
publicadas bajo la licencia Apache 2.0 (copia en `LICENSE-knowledge-work-plugins.txt`).
Se tomaron como referencia: productivity/task-management, productivity/memory-management,
product-management/write-spec, product-management/sprint-planning, operations/status-report,
marketing/draft-content, small-business/proposal-builder, sales/lead-triage,
small-business/speed-to-lead, sales/draft-outreach, sales/call-prep,
customer-support/draft-response, small-business/invoice-chase y sales/daily-briefing.
Cambios: contenido traducido al español, reducido, adaptado a las acciones del CRM Infocus
y al trabajo de un estudio creativo en Colombia.
