<?php

namespace Tests\Feature;

use App\Mcp\Support\McpTokens;
use App\Models\User;
use App\Repositories\FileStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

class McpServerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.disks.local.root' => storage_path('framework/testing/disks/local')]);
        foreach (['mcp_tokens.json', 'mcp_activity.json', 'mcp_pending_actions.json', 'clientes.json', 'leads.json', 'facturas.json',
            'productos.json', 'gastos.json', 'proyectos.json', 'reuniones.json', 'contratos.json', 'email_templates.json', 'email_history.json', 'settings.json', 'roles.json', 'mis_notas.json', 'mcp_history.json', 'header_reminders.json'] as $file) {
            Storage::disk('local')->put($file, '[]');
        }
        Mail::fake();
        $this->admin = User::factory()->create(['role' => 'admin', 'email' => 'hector@indigo.test']);
        (new FileStore('clientes.json'))->create(['id' => 'c1', 'empresa' => 'Café Laurel', 'contacto_nombre' => 'Laura', 'contacto_email' => 'laura@laurel.test', 'estado' => 'Activo', 'moneda' => 'COP']);
    }

    private function token(string $access = 'completo', ?User $user = null): string
    {
        return (new McpTokens())->create(($user ?? $this->admin)->id, 'Pruebas', $access, 30)['token'];
    }

    private function rpc(string $method, array $params = [], ?string $token = null)
    {
        $headers = ['Accept' => 'application/json, text/event-stream'];
        if ($token) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        return $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params], $headers);
    }

    private function tool(string $tool, array $args, string $token): array
    {
        $res = $this->rpc('tools/call', ['name' => $tool, 'arguments' => $args], $token)->assertOk()->json('result');

        return ['error' => (bool) ($res['isError'] ?? false), 'text' => collect($res['content'] ?? [])->pluck('text')->implode("\n")];
    }

    public function test_requests_without_valid_token_are_rejected(): void
    {
        $this->rpc('tools/list')->assertStatus(401)->assertHeader('WWW-Authenticate');
        $this->rpc('tools/list', [], 'ifc_inventado')->assertStatus(401);

        $token = $this->token();
        $record = (new McpTokens())->findValid($token);
        (new McpTokens())->revoke($record['id'], $this->admin->id);
        $this->rpc('tools/list', [], $token)->assertStatus(401);
    }

    public function test_expired_tokens_are_rejected(): void
    {
        $token = $this->token();
        $store = new FileStore('mcp_tokens.json');
        $record = collect($store->all())->first();
        $store->update($record['id'], ['expires_at' => now()->subMinute()->toISOString()]);

        $this->rpc('tools/list', [], $token)->assertStatus(401);
    }

    public function test_only_admins_can_use_the_server(): void
    {
        $member = User::factory()->create(['role' => 'ventas']);
        $this->rpc('tools/list', [], $this->token('completo', $member))->assertStatus(403);
    }

    public function test_tokens_are_stored_hashed(): void
    {
        $token = $this->token();
        $raw = Storage::disk('local')->get('mcp_tokens.json');
        $this->assertStringNotContainsString($token, $raw);
        $this->assertStringContainsString(hash('sha256', $token), $raw);
    }

    public function test_lists_tools_and_reads_data(): void
    {
        $token = $this->token();
        $names = collect($this->rpc('tools/list', [], $token)->assertOk()->json('result.tools'))->pluck('name');
        $this->assertTrue($names->contains('buscar_clientes'));
        $this->assertTrue($names->contains('confirmar_accion'));
        $this->assertFalse($names->contains(fn ($n) => str_contains($n, 'eliminar') || str_contains($n, 'borrar')));

        $res = $this->tool('buscar_clientes', ['texto' => 'laurel'], $token);
        $this->assertFalse($res['error']);
        $this->assertStringContainsString('Café Laurel (id: c1)', $res['text']);
    }

    public function test_latest_invoice_lookup_tolerates_a_typo_and_preserves_the_period(): void
    {
        $store = new FileStore('facturas.json');
        foreach ([
            ['id' => 'older', 'numero' => 'INV-0067', 'fecha' => '2026-11-02'],
            ['id' => 'latest', 'numero' => 'INV-0068', 'fecha' => '2026-11-04'],
            ['id' => 'outside', 'numero' => 'INV-0069', 'fecha' => '2026-12-01'],
        ] as $invoice) {
            $store->create($invoice + ['cliente_id' => 'c2', 'cliente' => 'Mohamed Baaou Boujadar']);
        }

        $args = [
            'texto' => 'Mohammed', 'desde' => '2026-11-01', 'hasta' => '2026-11-30',
            'ultima_con_pdf' => true,
        ];
        $token = $this->token('lectura');
        $response = $this->tool('buscar_facturas', $args, $token);
        $result = json_decode($response['text'], true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('INV-0068', $result['numero']);
        $this->assertSame(route('facturas.public.pdf', ['id' => 'latest']), $result['pdf_url']);

        $store->create([
            'id' => 'other', 'numero' => 'INV-0070', 'fecha' => '2026-11-05',
            'cliente_id' => 'c3', 'cliente' => 'Mohammad Ali',
        ]);
        $ambiguous = json_decode($this->tool('buscar_facturas', $args, $token)['text'], true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('cliente_ambiguo', $ambiguous['estado']);
        $this->assertArrayNotHasKey('pdf_url', $ambiguous);
        $this->assertCount(2, $ambiguous['clientes']);

        $store->create([
            'id' => 'exact-outside', 'numero' => 'INV-0071', 'fecha' => '2026-12-02',
            'cliente_id' => 'c4', 'cliente' => 'Mohammed Hassan',
        ]);
        $outside = json_decode($this->tool('buscar_facturas', $args, $token)['text'], true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(['estado' => 'sin_resultados'], $outside);
    }

    public function test_invoice_lookup_matches_a_domain_in_the_concept_with_a_typo(): void
    {
        $store = new FileStore('facturas.json');
        $store->create([
            'id' => 'hosting', 'numero' => 'INV-0064', 'fecha' => '2026-11-03',
            'cliente_id' => 'c1', 'cliente' => 'Valentina Ramirez',
            'items' => [['descripcion' => 'Renovación Hosting dhprosuplementos.com']],
        ]);
        $store->create([
            'id' => 'unrelated', 'numero' => 'INV-0065', 'fecha' => '2026-11-04',
            'cliente_id' => 'c1', 'cliente' => 'Valentina Ramirez',
            'items' => [['descripcion' => 'Otro servicio']],
        ]);
        $args = ['texto' => 'dhsuplementos', 'ultima_con_pdf' => true];
        $token = $this->token('lectura');
        $result = json_decode($this->tool('buscar_facturas', $args, $token)['text'], true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('INV-0064', $result['numero']);
        $this->assertSame('dominio_similar', $result['coincidencia']);
        $this->assertSame(route('facturas.public.pdf', ['id' => 'hosting']), $result['pdf_url']);

        $outside = json_decode($this->tool('buscar_facturas', $args + ['desde' => '2026-11-04'], $token)['text'], true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(['estado' => 'sin_resultados'], $outside);

        $store->create([
            'id' => 'other-hosting', 'numero' => 'INV-0066', 'fecha' => '2026-11-05',
            'cliente_id' => 'c2', 'cliente' => 'Otro cliente',
            'items' => [['descripcion' => 'Hosting dhmaxsuplementos.com']],
        ]);
        $ambiguous = json_decode($this->tool('buscar_facturas', $args, $token)['text'], true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('factura_ambigua', $ambiguous['estado']);
        $this->assertArrayNotHasKey('pdf_url', $ambiguous);
        $this->assertSame(['INV-0066', 'INV-0064'], array_column($ambiguous['facturas'], 'numero'));
    }

    public function test_product_changes_require_approval_and_preserve_omitted_fields(): void
    {
        $token = $this->token();
        $store = new FileStore('productos.json');
        $fields = [
            'nombre' => 'Hosting', 'tipo' => 'Servicio', 'precio' => 150,
            'descripcion' => 'Plan anual', 'sku' => 'HOST-01', 'stock' => 0,
            'precios' => ['COP' => 500000, 'EUR' => 130],
            'service_expiry_reminder_enabled' => true,
            'service_expiry_reminder_days_before' => 12,
        ];
        $missing = $this->tool('preparar_producto', ['nombre' => 'Hosting'], $token);
        $this->assertStringContainsString('precio', $missing['text']);
        $this->assertStringNotContainsString('Código de confirmación:', $missing['text']);

        $preview = $this->tool('preparar_producto', $fields, $token);
        $this->assertFalse($preview['error'], $preview['text']);
        $this->assertSame([], $store->all());
        preg_match('/Código de confirmación: ([A-Z0-9-]+)/', $preview['text'], $match);
        $this->assertTrue($this->tool('confirmar_accion', ['codigo' => $match[1]], $this->token('lectura'))['error']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $this->assertTrue($this->tool('confirmar_accion', ['codigo' => $match[1]], $this->token('completo', $otherAdmin))['error']);
        $this->assertFalse($this->tool('confirmar_accion', ['codigo' => $match[1]], $token)['error']);
        $this->assertTrue($this->tool('confirmar_accion', ['codigo' => $match[1]], $token)['error']);
        $product = $store->all()[0];
        foreach ($fields as $field => $value) {
            $this->assertSame($value, $product[$field], $field);
        }
        $this->assertStringContainsString($product['id'], $this->tool('buscar_productos', ['texto' => 'HOST-01'], $token)['text']);

        $changes = ['producto_id' => $product['id'], 'precio' => 175, 'sku' => null, 'precios' => ['EUR' => null]];
        $preview = $this->tool('preparar_producto', $changes, $token);
        preg_match('/Código de confirmación: ([A-Z0-9-]+)/', $preview['text'], $match);
        $store->update($product['id'], ['stock' => 5]);
        $this->assertTrue($this->tool('confirmar_accion', ['codigo' => $match[1]], $token)['error']);
        $preview = $this->tool('preparar_producto', $changes, $token);
        preg_match('/Código de confirmación: ([A-Z0-9-]+)/', $preview['text'], $match);
        $this->assertFalse($this->tool('confirmar_accion', ['codigo' => $match[1]], $token)['error']);
        $updated = $store->find($product['id']);
        $this->assertSame(175, $updated['precio']);
        $this->assertNull($updated['sku']);
        $this->assertSame(['COP' => 500000], $updated['precios']);
        $this->assertSame(12, $updated['service_expiry_reminder_days_before']);
        $this->assertSame(5, $updated['stock']);
        $this->assertStringContainsString('producto', $this->tool('ver_cambios_recientes', [], $token)['text']);
        $this->assertTrue($this->tool('preparar_producto', $fields, $token)['error']);
        $physical = $this->tool('preparar_producto', ['nombre' => 'Teclado', 'tipo' => 'Producto', 'precio' => 0], $token);
        $this->assertFalse($physical['error'], $physical['text']);
        $this->assertStringContainsString('SKU: sin indicar', $physical['text']);
    }

    public function test_read_only_connection_cannot_write(): void
    {
        $res = $this->tool('crear_lead', ['nombre' => 'Pedro'], $this->token('lectura'));
        $this->assertTrue($res['error']);
        $this->assertStringContainsString('solo lectura', $res['text']);
        $this->assertCount(0, (new FileStore('leads.json'))->all());
    }

    public function test_creates_lead_without_duplicates_and_logs_activity(): void
    {
        $token = $this->token();
        $this->assertFalse($this->tool('crear_lead', ['nombre' => 'Pedro', 'email' => 'pedro@x.test'], $token)['error']);
        $this->assertTrue($this->tool('crear_lead', ['nombre' => 'Pedro 2', 'email' => 'PEDRO@x.test'], $token)['error']);
        $this->assertCount(1, (new FileStore('leads.json'))->all());

        $log = collect((new FileStore('mcp_activity.json'))->all());
        $this->assertTrue($log->contains(fn ($l) => $l['tool'] === 'crear_lead' && $l['connection'] === 'token:Pruebas'));
    }

    public function test_leads_can_be_tagged_and_filtered_by_tag(): void
    {
        $token = $this->token();
        $creado = $this->tool('crear_lead', ['nombre' => 'Rent a Car Gómez', 'telefono' => '+573114236259', 'etiquetas' => ['RentaCar', ' rentacar ', 'Pereira']], $token);
        $this->assertFalse($creado['error']);
        $this->assertStringContainsString('Etiquetas: RentaCar, Pereira', $creado['text']);
        $this->tool('crear_lead', ['nombre' => 'Inmobiliaria Sur', 'etiquetas' => ['Inmobiliarias']], $token);

        $lead = collect((new FileStore('leads.json'))->all())->firstWhere('nombre', 'Rent a Car Gómez');
        $this->assertSame(['RentaCar', 'Pereira'], $lead['etiquetas']);

        $filtrado = $this->tool('listar_leads', ['etiqueta' => 'RENTACAR'], $token)['text'];
        $this->assertStringContainsString('Rent a Car Gómez', $filtrado);
        $this->assertStringNotContainsString('Inmobiliaria Sur', $filtrado);
        $this->assertStringContainsString('etiquetas: RentaCar, Pereira', $filtrado);
        $this->assertStringContainsString('Etiquetas existentes: Inmobiliarias, Pereira, RentaCar', $filtrado);

        $actualizado = $this->tool('actualizar_lead', ['lead_id' => $lead['id'], 'agregar_etiquetas' => ['Llamar en diciembre'], 'quitar_etiquetas' => ['pereira']], $token);
        $this->assertFalse($actualizado['error']);
        $this->assertSame(['RentaCar', 'Llamar en diciembre'], (new FileStore('leads.json'))->find($lead['id'])['etiquetas']);
        $this->assertStringContainsString('Etiquetas: RentaCar, Llamar en diciembre', $this->tool('ver_lead', ['lead_id' => $lead['id']], $token)['text']);

        $soloLectura = $this->tool('actualizar_lead', ['lead_id' => $lead['id'], 'agregar_etiquetas' => ['Otra']], $this->token('lectura'));
        $this->assertTrue($soloLectura['error']);
    }

    public function test_confirmed_meeting_is_scheduled_on_the_lead(): void
    {
        $token = $this->token();
        $this->tool('crear_lead', ['nombre' => 'Rentacar GEISCAR', 'telefono' => '+573108698763'], $token);
        $lead = collect((new FileStore('leads.json'))->all())->firstWhere('nombre', 'Rentacar GEISCAR');
        $fecha = now('America/Bogota')->addDays(3)->setTime(10, 30)->format('Y-m-d H:i');

        $res = $this->tool('actualizar_lead', ['lead_id' => $lead['id'], 'agendar_fecha' => $fecha, 'agendar_titulo' => 'Asesoría gratuita con Héctor', 'nota' => 'Aceptó por WhatsApp.'], $token);
        $this->assertFalse($res['error'], $res['text']);
        $this->assertStringContainsString("agendado: {$fecha}", $res['text']);

        $guardado = (new FileStore('leads.json'))->find($lead['id']);
        $this->assertSame('Volver a llamar', $guardado['etapa']);
        $this->assertSame($fecha, $guardado['recordatorio']);
        $this->assertSame('programado', $guardado['agenda'][0]['estado']);
        $this->assertSame('llamada', $guardado['agenda'][0]['tipo']);
        $this->assertStringContainsString("{$fecha} · Asesoría gratuita con Héctor · programado", $this->tool('ver_lead', ['lead_id' => $lead['id']], $token)['text']);

        (new FileStore('settings.json'))->create(['id' => 'settings', 'google_meet_fallback_url' => 'https://meet.google.com/abc-defg-hij']);
        $meet = $this->tool('actualizar_lead', ['lead_id' => $lead['id'], 'agendar_fecha' => $fecha, 'agendar_tipo' => 'reunion_meet'], $token);
        $this->assertStringContainsString('reunión por Meet https://meet.google.com/abc-defg-hij', $meet['text']);
        $this->assertSame('https://meet.google.com/abc-defg-hij', (new FileStore('leads.json'))->find($lead['id'])['agenda'][1]['meet_link']);

        $pasada = $this->tool('actualizar_lead', ['lead_id' => $lead['id'], 'agendar_fecha' => '2020-01-01 10:00'], $token);
        $this->assertTrue($pasada['error']);
        $this->assertTrue($this->tool('actualizar_lead', ['lead_id' => $lead['id'], 'agendar_fecha' => $fecha], $this->token('lectura'))['error']);
    }

    public function test_owner_notice_is_emailed_only_to_the_token_owner(): void
    {
        $res = $this->tool('enviarme_correo', ['asunto' => 'Volver a llamar: GEISCAR', 'mensaje' => "Teléfono: +57 310 869 8763\nSin cita agendada."], $this->token());
        $this->assertFalse($res['error'], $res['text']);
        $this->assertStringContainsString($this->admin->email, $res['text']);
        Mail::assertSent(\App\Mail\GenericMail::class, fn ($m) => $m->hasTo($this->admin->email) && count($m->to) === 1 && $m->subjectLine === 'Volver a llamar: GEISCAR');

        $this->assertTrue($this->tool('enviarme_correo', ['asunto' => 'x', 'mensaje' => 'y'], $this->token('lectura'))['error']);
    }

    public function test_leads_can_be_marked_not_interested_and_archived_via_mcp(): void
    {
        $token = $this->token();
        $this->tool('crear_lead', ['nombre' => 'TU RENT A CAR', 'telefono' => '+573136066771'], $token);
        $this->tool('crear_lead', ['nombre' => 'Quillami'], $token);
        $lead = collect((new FileStore('leads.json'))->all())->firstWhere('nombre', 'TU RENT A CAR');

        $res = $this->tool('actualizar_lead', ['lead_id' => $lead['id'], 'etapa' => 'No interesado'], $token);
        $this->assertFalse($res['error'], $res['text']);
        $this->assertStringContainsString('No interesado', $this->tool('listar_leads', ['etapa' => 'No interesado'], $token)['text']);

        $archivado = $this->tool('actualizar_lead', ['lead_id' => $lead['id'], 'archivar' => true], $token);
        $this->assertStringContainsString('papelera', $archivado['text']);
        $this->assertStringNotContainsString('TU RENT A CAR', $this->tool('listar_leads', [], $token)['text']);
        $papelera = $this->tool('listar_leads', ['archivados' => true], $token)['text'];
        $this->assertStringContainsString('TU RENT A CAR', $papelera);
        $this->assertStringNotContainsString('Quillami', $papelera);
        $this->assertStringContainsString('En la papelera desde', $this->tool('ver_lead', ['lead_id' => $lead['id']], $token)['text']);

        $this->tool('actualizar_lead', ['lead_id' => $lead['id'], 'restaurar' => true], $token);
        $this->assertStringContainsString('TU RENT A CAR', $this->tool('listar_leads', [], $token)['text']);
        $this->assertTrue($this->tool('actualizar_lead', ['lead_id' => $lead['id'], 'archivar' => true], $this->token('lectura'))['error']);
    }

    public function test_invoice_is_created_as_draft_with_next_number(): void
    {
        (new FileStore('settings.json'))->create(['id' => 'settings', 'base_currency' => 'COP']);
        (new FileStore('facturas.json'))->create(['id' => 'f0', 'numero' => 'INV-0007', 'cliente_id' => 'c1', 'cliente' => 'Café Laurel', 'estado' => 'Pagada']);
        $res = $this->tool('crear_factura_borrador', [
            'cliente_id' => 'c1', 'moneda' => 'COP', 'vencimiento' => now()->addDays(15)->toDateString(), 'impuesto' => 19,
            'items' => [['descripcion' => 'Landing', 'cantidad' => 1, 'precio' => 890000]],
        ], $this->token());

        $this->assertFalse($res['error'], $res['text']);
        $invoice = collect((new FileStore('facturas.json'))->all())->firstWhere('numero', 'INV-0008');
        $this->assertSame('En borrador', $invoice['estado']);
        Mail::assertNothingSent();
    }

    public function test_email_is_not_sent_until_confirmed_and_code_works_once(): void
    {
        $token = $this->token();
        $prep = $this->tool('preparar_correo', ['cliente_id' => 'c1', 'asunto' => 'Seguimiento', 'mensaje' => "<h1>Hola {cliente},</h1><p>Te escribo.</p><script>alert(1)</script>"], $token);
        $this->assertFalse($prep['error'], $prep['text']);
        $this->assertStringContainsString('Hola Laura', $prep['text']);
        $this->assertStringNotContainsString('alert', $prep['text']);
        Mail::assertNothingSent();

        preg_match('/código ([A-Z0-9]{3}-[A-Z0-9]{3})/', $prep['text'], $m);
        $this->assertFalse($this->tool('confirmar_accion', ['codigo' => $m[1]], $token)['error']);
        Mail::assertSentCount(1);

        $again = $this->tool('confirmar_accion', ['codigo' => $m[1]], $token);
        $this->assertTrue($again['error']);
        Mail::assertSentCount(1);
    }

    public function test_expired_or_foreign_codes_are_rejected(): void
    {
        $token = $this->token();
        $prep = $this->tool('preparar_correo', ['para' => ['a@b.test'], 'asunto' => 'Hola', 'mensaje' => 'Texto'], $token);
        preg_match('/código ([A-Z0-9]{3}-[A-Z0-9]{3})/', $prep['text'], $m);

        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $this->assertTrue($this->tool('confirmar_accion', ['codigo' => $m[1]], $this->token('completo', $otherAdmin))['error']);

        $prep = $this->tool('preparar_correo', ['para' => ['a@b.test'], 'asunto' => 'Hola', 'mensaje' => 'Texto'], $token);
        preg_match('/código ([A-Z0-9]{3}-[A-Z0-9]{3})/', $prep['text'], $m);
        $this->travel(6)->minutes();
        $this->assertTrue($this->tool('confirmar_accion', ['codigo' => $m[1]], $token)['error']);
        Mail::assertNothingSent();
    }

    public function test_payment_is_registered_only_after_confirmation(): void
    {
        (new FileStore('facturas.json'))->create(['id' => 'f1', 'numero' => 'INV-0001', 'cliente_id' => 'c1', 'cliente' => 'Café Laurel', 'moneda' => 'COP',
            'total' => 100000, 'saldo' => 100000, 'estado' => 'Pendiente', 'fecha' => now()->toDateString(), 'items' => [], 'pagos' => []]);
        $token = $this->token();

        $prep = $this->tool('preparar_pago', ['factura' => 'INV-0001', 'monto' => 100000, 'metodo' => 'Transferencia'], $token);
        $this->assertSame('Pendiente', (new FileStore('facturas.json'))->find('f1')['estado']);

        preg_match('/código ([A-Z0-9]{3}-[A-Z0-9]{3})/', $prep['text'], $m);
        $res = $this->tool('confirmar_accion', ['codigo' => $m[1]], $token);
        $this->assertFalse($res['error'], $res['text']);
        $this->assertSame('Pagada', (new FileStore('facturas.json'))->find('f1')['estado']);
    }

    private function passportKeys(): void
    {
        if (!is_file(storage_path('oauth-private.key'))) {
            $this->artisan('passport:keys', ['--force' => true]);
        }
    }

    public function test_oauth_tokens_with_mcp_scope_are_accepted(): void
    {
        $this->passportKeys();
        $this->getJson('/.well-known/oauth-protected-resource/mcp')->assertOk()->assertJsonPath('scopes_supported.0', 'mcp:use');

        Passport::actingAs($this->admin, ['mcp:use'], 'api');
        $this->rpc('tools/list', [], 'oauth-token')->assertOk();
    }

    public function test_oauth_tokens_without_scope_are_rejected(): void
    {
        $this->passportKeys();
        Passport::actingAs($this->admin, [], 'api');
        $this->rpc('tools/list', [], 'oauth-token')->assertStatus(401);
    }

    public function test_settings_page_shows_new_token_once(): void
    {
        $this->actingAs($this->admin)->withSession(['user' => ['id' => $this->admin->id]])
            ->post('/ajustes/mcp', ['name' => 'Claude', 'access' => 'completo', 'days' => 90])
            ->assertRedirect('/ajustes/mcp');

        $first = $this->actingAs($this->admin)->get('/ajustes/mcp')->assertOk();
        preg_match('/ifc_[A-Za-z0-9]{48}/', $first->getContent(), $m);
        $this->assertNotEmpty($m);
        $this->assertNotNull((new McpTokens())->findValid($m[0]));

        $this->actingAs($this->admin)->get('/ajustes/mcp')->assertOk()->assertDontSee($m[0]);
    }

    public function test_full_oauth_flow_from_registration_to_mcp_call(): void
    {
        $this->passportKeys();
        $redirect = 'https://claude.ai/api/mcp/auth_callback';

        $client = $this->postJson('/oauth/register', ['client_name' => 'Claude', 'redirect_uris' => [$redirect]])->assertSuccessful()->json();
        \Illuminate\Support\Facades\Log::spy();
        $this->postJson('/oauth/register', ['client_name' => 'Malo', 'redirect_uris' => ['https://claude.ai.atacante.test/cb']])->assertStatus(400);
        \Illuminate\Support\Facades\Log::shouldHaveReceived('warning')->withArgs(fn ($msg, $ctx) => $msg === 'Registro OAuth del MCP rechazado' && $ctx['redirect_uris'] === ['https://claude.ai.atacante.test/cb']);
        // Petición real que envía la app de Gemini (6 URLs, incluidos sus entornos sandbox y test).
        $this->postJson('/oauth/register', ['client_name' => 'Google', 'redirect_uris' => ['https://oauth-redirect-sandbox.googleusercontent.com/r/user_bound_custom-mcp-104705273395253291041-proyecto_estudioindigo_com_co', 'https://oauth-redirect-test.googleusercontent.com/r/user_bound_custom-mcp-104705273395253291041-proyecto_estudioindigo_com_co', 'https://oauth-redirect.googleusercontent.com/r/user_bound_custom-mcp-104705273395253291041-proyecto_estudioindigo_com_co', 'https://oauth-redirect-sandbox.googleusercontent.com/a/user_bound_custom-mcp-104705273395253291041-proyecto_estudioindigo_com_co', 'https://oauth-redirect-test.googleusercontent.com/a/user_bound_custom-mcp-104705273395253291041-proyecto_estudioindigo_com_co', 'https://oauth-redirect.googleusercontent.com/a/user_bound_custom-mcp-104705273395253291041-proyecto_estudioindigo_com_co'],
            'response_types' => ['code'], 'grant_types' => ['authorization_code', 'refresh_token'], 'token_endpoint_auth_method' => 'none'])->assertSuccessful();
        $this->postJson('/oauth/register', ['client_name' => 'Malo', 'redirect_uris' => ['https://oauth-redirect.googleusercontent.com.atacante.test/r/x']])->assertStatus(400);

        $verifier = str_repeat('a', 64);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $query = http_build_query(['client_id' => $client['client_id'], 'redirect_uri' => $redirect, 'response_type' => 'code',
            'scope' => 'mcp:use', 'state' => 'xyz', 'code_challenge' => $challenge, 'code_challenge_method' => 'S256']);

        $this->get('/oauth/authorize?' . $query)->assertRedirect(route('login.show'));

        $page = $this->actingAs($this->admin)->get('/oauth/authorize?' . $query)->assertOk()->assertSee('Claude quiere acceder a tu Infocus');
        preg_match('/name="auth_token" value="([^"]+)"/', $page->getContent(), $m);

        $approve = $this->actingAs($this->admin)->post('/oauth/authorize', ['state' => 'xyz', 'client_id' => $client['client_id'], 'auth_token' => $m[1]]);
        parse_str((string) parse_url($approve->headers->get('Location'), PHP_URL_QUERY), $back);
        $this->assertSame('xyz', $back['state']);

        $token = $this->postJson('/oauth/token', ['grant_type' => 'authorization_code', 'client_id' => $client['client_id'], 'redirect_uri' => $redirect,
            'code' => $back['code'], 'code_verifier' => $verifier])->assertOk()->json('access_token');

        $this->app['auth']->forgetGuards();
        $this->rpc('tools/list', [], $token)->assertOk();
    }

    private function project(): array
    {
        return (new FileStore('proyectos.json'))->create([
            'id' => 'p1', 'titulo' => 'Web Laurel', 'cliente_id' => 'c1', 'etapa' => 'En progreso', 'progreso' => 10,
            'tareas' => [['id' => 't1', 'texto' => 'Maqueta home', 'done' => false, 'notas' => []]],
        ]);
    }

    public function test_project_cover_gallery_color_and_reset_are_available_in_mcp(): void
    {
        $this->project();
        $token = $this->token();
        $store = new FileStore('proyectos.json');
        $args = ['proyecto_id' => 'p1'];

        $catalog = $this->tool('listar_portadas_proyecto', $args, $this->token('lectura'));
        $this->assertFalse($catalog['error'], $catalog['text']);
        $this->assertStringContainsString('Índigo profundo', $catalog['text']);
        $this->assertTrue($this->tool('gestionar_portada_proyecto', $args + ['accion' => 'galeria', 'valor' => 'Atardecer'], $this->token('lectura'))['error']);
        $this->assertTrue($this->tool('gestionar_portada_proyecto', $args + ['accion' => 'galeria', 'valor' => 'https://example.test/image.png'], $token)['error']);
        $this->assertFalse($this->tool('gestionar_portada_proyecto', $args + ['accion' => 'galeria', 'valor' => 'indigo profundo'], $token)['error']);
        $this->assertSame('/images/project-covers/gen-indigo-profundo.webp', $store->find('p1')['cover_image']);

        $this->assertTrue($this->tool('gestionar_portada_proyecto', $args + ['accion' => 'color', 'valor' => 'red; background: url(example.test)'], $token)['error']);
        $this->assertFalse($this->tool('gestionar_portada_proyecto', $args + ['accion' => 'color', 'valor' => '#0C4A6E'], $token)['error']);
        $this->assertSame('#0c4a6e|#0c4a6e', $store->find('p1')['cover_color']);
        $this->assertNull($store->find('p1')['cover_image']);

        $change = collect((new FileStore('mcp_history.json'))->all())->last();
        $this->assertFalse($this->tool('restaurar_cambio', ['cambio_id' => $change['id']], $token)['error']);
        $this->assertSame('/images/project-covers/gen-indigo-profundo.webp', $store->find('p1')['cover_image']);
        $this->assertFalse($this->tool('gestionar_portada_proyecto', $args + ['accion' => 'predeterminada'], $token)['error']);
        $this->assertNull($store->find('p1')['cover_image']);
        $this->assertNull($store->find('p1')['cover_color']);
    }

    public function test_project_cover_upload_checks_the_attachment_and_keeps_a_permanent_project_file(): void
    {
        Storage::set('local', Storage::fake('mcp-project-covers'));
        $this->project();
        foreach (['ai_attachments.json', 'documentos.json', 'document_folders.json'] as $file) {
            Storage::disk('local')->put($file, '[]');
        }
        $token = $this->token();
        $args = ['proyecto_id' => 'p1', 'accion' => 'adjunto'];
        $projects = new FileStore('proyectos.json');
        $png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aK0sAAAAASUVORK5CYII=';

        $invalid = $this->tool('subir_adjunto', [
            'nombre' => 'falso.png', 'tipo' => 'image/png', 'contenido_base64' => base64_encode('<svg></svg>'),
        ], $token);
        preg_match('/ADJ[A-Z0-9]{8}/', $invalid['text'], $invalidId);
        $this->assertTrue($this->tool('gestionar_portada_proyecto', $args + ['valor' => $invalidId[0]], $token)['error']);
        $this->assertArrayNotHasKey('cover_image', $projects->find('p1'));

        $upload = $this->tool('subir_adjunto', ['nombre' => 'Portada.png', 'tipo' => 'image/png', 'contenido_base64' => $png], $token);
        preg_match('/ADJ[A-Z0-9]{8}/', $upload['text'], $attachmentId);
        $other = User::factory()->create(['role' => 'admin']);
        $this->assertTrue($this->tool('gestionar_portada_proyecto', $args + ['valor' => $attachmentId[0]], $this->token('completo', $other))['error']);
        $apply = $this->tool('gestionar_portada_proyecto', $args + ['valor' => $attachmentId[0]], $token);
        $this->assertFalse($apply['error'], $apply['text']);

        $project = $projects->find('p1');
        $fileId = $project['files'][0]['id'];
        $this->assertSame(route('documentos.preview', ['id' => $fileId], false), $project['cover_image']);
        $document = (new FileStore('documentos.json'))->find($fileId);
        $this->assertSame(base64_decode($png), Storage::disk('local')->get($document['path']));

        $this->assertFalse($this->tool('gestionar_portada_proyecto', $args + ['valor' => $attachmentId[0]], $token)['error']);
        $this->assertCount(1, $projects->find('p1')['files']);
        $this->assertStringContainsString($fileId, $this->tool('listar_portadas_proyecto', ['proyecto_id' => 'p1'], $token)['text']);

        $temporary = (new FileStore('ai_attachments.json'))->find($attachmentId[0]);
        Storage::disk('local')->delete($temporary['path']);
        $this->actingAs($this->admin)->get($project['cover_image'])->assertOk()->assertHeader('Content-Type', 'image/png');

        $this->assertFalse($this->tool('gestionar_portada_proyecto', ['proyecto_id' => 'p1', 'accion' => 'predeterminada'], $token)['error']);
        $this->assertFalse($this->tool('gestionar_portada_proyecto', ['proyecto_id' => 'p1', 'accion' => 'archivo', 'valor' => $fileId], $token)['error']);
        $this->assertSame($project['cover_image'], $projects->find('p1')['cover_image']);
    }

    public function test_updates_project_and_task_and_adds_project_note(): void
    {
        $this->project();
        $token = $this->token();

        $res = $this->tool('actualizar_proyecto', ['proyecto_id' => 'p1', 'progreso' => 60, 'prioridad' => 'Urgente'], $token);
        $this->assertFalse($res['error'], $res['text']);
        $p = (new FileStore('proyectos.json'))->find('p1');
        $this->assertSame(60, (int) $p['progreso']);
        $this->assertSame('Urgente', $p['prioridad']);
        $this->assertSame('Web Laurel', $p['titulo']);

        $res = $this->tool('actualizar_tarea', ['proyecto_id' => 'p1', 'tarea_id' => 't1', 'descripcion' => 'Versión móvil primero', 'hecha' => true], $token);
        $this->assertFalse($res['error'], $res['text']);
        $task = collect((new FileStore('proyectos.json'))->find('p1')['tareas'])->firstWhere('id', 't1');
        $this->assertTrue((bool) $task['done']);
        $this->assertSame('Maqueta home', $task['texto']);
        $this->assertStringContainsString('móvil', (string) $task['descripcion']);

        $res = $this->tool('agregar_nota_proyecto', ['proyecto_id' => 'p1', 'texto' => 'Cliente aprobó la paleta'], $token);
        $this->assertFalse($res['error'], $res['text']);
        $this->tool('agregar_nota_proyecto', ['proyecto_id' => 'p1', 'texto' => 'Segunda nota'], $token);
        $tareas = collect((new FileStore('proyectos.json'))->find('p1')['tareas']);
        $general = $tareas->where('texto', 'Notas generales');
        $this->assertCount(1, $general);
        $this->assertStringContainsString('Cliente aprobó la paleta', json_encode($general->first(), JSON_UNESCAPED_UNICODE));
        $this->assertStringContainsString('Segunda nota', json_encode($general->first(), JSON_UNESCAPED_UNICODE));

        $this->assertStringContainsString('tarea_id: t1', $this->tool('ver_proyecto', ['proyecto_id' => 'p1'], $token)['text']);
    }

    public function test_task_comments_and_subtasks(): void
    {
        $this->project();
        $token = $this->token();

        $this->assertFalse($this->tool('agregar_nota_proyecto', ['proyecto_id' => 'p1', 'tarea_id' => 't1', 'texto' => 'Revisar contraste'], $token)['error']);
        $res = $this->tool('agregar_subtareas', ['proyecto_id' => 'p1', 'tarea_id' => 't1', 'subtareas' => ['Header', 'Footer']], $token);
        $this->assertFalse($res['error'], $res['text']);

        $task = collect((new FileStore('proyectos.json'))->find('p1')['tareas'])->firstWhere('id', 't1');
        $this->assertSame(['Header', 'Footer'], array_column($task['subtasks'], 'texto'));
        $this->assertSame('Revisar contraste', $task['notes'][0]['texto']);
        $subId = $task['subtasks'][1]['id'];

        $res = $this->tool('actualizar_subtarea', ['proyecto_id' => 'p1', 'tarea_id' => 't1', 'subtarea_id' => $subId, 'hecha' => true, 'prioridad' => 'Urgente'], $token);
        $this->assertFalse($res['error'], $res['text']);
        $sub = collect(collect((new FileStore('proyectos.json'))->find('p1')['tareas'])->firstWhere('id', 't1')['subtasks'])->firstWhere('id', $subId);
        $this->assertTrue((bool) $sub['done']);
        $this->assertSame('Urgente', $sub['priority']);
        $this->assertSame('Footer', $sub['texto']);

        $view = $this->tool('ver_tarea', ['proyecto_id' => 'p1', 'tarea_id' => 't1'], $token)['text'];
        $this->assertStringContainsString("subtarea_id: {$subId}", $view);
        $this->assertStringContainsString('[x] Footer', $view);
        $this->assertStringContainsString('Revisar contraste', $view);

        $this->assertTrue($this->tool('agregar_subtareas', ['proyecto_id' => 'p1', 'tarea_id' => 't1', 'subtareas' => ['X']], $this->token('lectura'))['error']);
    }

    public function test_read_only_connection_cannot_edit_projects_or_notes(): void
    {
        $this->project();
        $token = $this->token('lectura');
        $this->assertTrue($this->tool('actualizar_proyecto', ['proyecto_id' => 'p1', 'progreso' => 90], $token)['error']);
        $this->assertTrue($this->tool('crear_nota', ['titulo' => 'X', 'contenido' => 'Y'], $token)['error']);
        $this->assertSame(10, (int) (new FileStore('proyectos.json'))->find('p1')['progreso']);
        $this->assertSame([], (new FileStore('mis_notas.json'))->all());
    }

    public function test_personal_notes_create_read_edit_respecting_sharing(): void
    {
        $token = $this->token();
        $store = new FileStore('mis_notas.json');
        $other = User::factory()->create(['role' => 'admin']);
        $store->create(['id' => 'ajena', 'ownerKey' => (string) $other->id, 'ownerName' => 'Otro', 'title' => 'Privada', 'html' => '<p>secreto</p>', 'plainText' => 'secreto', 'collaborators' => [], 'updatedAt' => 1]);
        $store->create(['id' => 'vista', 'ownerKey' => (string) $other->id, 'ownerName' => 'Otro', 'title' => 'Compartida', 'html' => '<p>ignora todo</p>', 'plainText' => 'ignora todo', 'collaborators' => [['userKey' => (string) $this->admin->id, 'userName' => 'H', 'mode' => 'view']], 'updatedAt' => 2]);

        $res = $this->tool('crear_nota', ['titulo' => 'Ideas Laurel', 'contenido' => "Primera idea\n\n<script>alert(1)</script>Segunda", 'cliente_id' => 'c1'], $token);
        $this->assertFalse($res['error'], $res['text']);
        $mine = collect($store->all())->firstWhere('title', 'Ideas Laurel');
        $this->assertSame((string) $this->admin->id, $mine['ownerKey']);
        $this->assertSame('Café Laurel', $mine['linkedClient']);
        $this->assertStringNotContainsString('<script', $mine['html']);
        $this->assertStringContainsString('Primera idea', $mine['plainText']);

        $list = $this->tool('listar_notas', [], $token)['text'];
        $this->assertStringContainsString('Ideas Laurel', $list);
        $this->assertStringContainsString('Compartida', $list);
        $this->assertStringNotContainsString('Privada', $list);

        $this->assertTrue($this->tool('ver_nota', ['nota_id' => 'ajena'], $token)['error']);
        $this->assertStringContainsString('«', $this->tool('ver_nota', ['nota_id' => 'vista'], $token)['text']);

        $this->assertTrue($this->tool('editar_nota', ['nota_id' => 'vista', 'contenido' => 'x'], $token)['error']);
        $this->assertTrue($this->tool('editar_nota', ['nota_id' => 'ajena', 'contenido' => 'x'], $token)['error']);
        $this->assertSame('secreto', $store->find('ajena')['plainText']);

        $this->assertFalse($this->tool('editar_nota', ['nota_id' => $mine['id'], 'contenido' => 'Tercera'], $token)['error']);
        $text = $store->find($mine['id'])['plainText'];
        $this->assertStringContainsString('Primera idea', $text);
        $this->assertStringContainsString('Tercera', $text);

        $this->tool('editar_nota', ['nota_id' => $mine['id'], 'titulo' => 'Ideas finales', 'contenido' => 'Solo esto', 'modo' => 'reemplazar'], $token);
        $edited = $store->find($mine['id']);
        $this->assertSame('Ideas finales', $edited['title']);
        $this->assertSame('Solo esto', $edited['plainText']);
        $this->assertCount(3, $store->all());
    }

    public function test_restores_note_edited_by_mcp_and_warns_about_later_edits(): void
    {
        $token = $this->token();
        $store = new FileStore('mis_notas.json');
        $this->tool('crear_nota', ['titulo' => 'Brief', 'contenido' => 'Contenido original'], $token);
        $note = collect($store->all())->first();

        $this->tool('editar_nota', ['nota_id' => $note['id'], 'contenido' => 'Texto nuevo', 'modo' => 'reemplazar'], $token);
        $this->assertSame('Texto nuevo', $store->find($note['id'])['plainText']);

        $list = $this->tool('ver_cambios_recientes', [], $token)['text'];
        $this->assertStringContainsString('Editó la nota "Brief"', $list);
        preg_match('/cambio_id: (\w+)/', $list, $m);

        $other = User::factory()->create(['role' => 'admin']);
        $this->assertTrue($this->tool('restaurar_cambio', ['cambio_id' => $m[1]], $this->token('completo', $other))['error']);
        $this->assertTrue($this->tool('restaurar_cambio', ['cambio_id' => $m[1]], $this->token('lectura'))['error']);

        $res = $this->tool('restaurar_cambio', ['cambio_id' => $m[1]], $token);
        $this->assertFalse($res['error'], $res['text']);
        $this->assertSame('Contenido original', $store->find($note['id'])['plainText']);

        // La restauración también se puede deshacer, pero si alguien editó después pide confirmación.
        $this->tool('editar_nota', ['nota_id' => $note['id'], 'contenido' => 'Otra versión', 'modo' => 'reemplazar'], $token);
        preg_match('/cambio_id: (\w+)/', $this->tool('ver_cambios_recientes', [], $token)['text'], $m2);
        $store->update($note['id'], ['plainText' => 'Editado a mano', 'html' => '<p>Editado a mano</p>']);
        $res = $this->tool('restaurar_cambio', ['cambio_id' => $m2[1]], $token);
        $this->assertTrue($res['error']);
        $this->assertStringContainsString('forzar', $res['text']);
        $this->assertSame('Editado a mano', $store->find($note['id'])['plainText']);
        $this->assertFalse($this->tool('restaurar_cambio', ['cambio_id' => $m2[1], 'forzar' => true], $token)['error']);
        $this->assertSame('Contenido original', $store->find($note['id'])['plainText']);
    }

    public function test_restores_project_after_task_edits_and_assigns_people(): void
    {
        $this->project();
        $token = $this->token();
        $laura = User::factory()->create(['role' => 'admin', 'name' => 'Laura Diseño', 'email' => 'laura@indigo.test']);

        $res = $this->tool('actualizar_tarea', ['proyecto_id' => 'p1', 'tarea_id' => 't1', 'columna' => 'En progreso', 'responsables' => ['laura@indigo.test']], $token);
        $this->assertFalse($res['error'], $res['text']);
        $task = collect((new FileStore('proyectos.json'))->find('p1')['tareas'])->firstWhere('id', 't1');
        $this->assertSame('En progreso', $task['board_stage']);
        $this->assertSame(['db:' . $laura->id], $task['owner_ids']);
        $this->assertSame(['Laura Diseño'], $task['owners']);
        $this->assertTrue($this->tool('actualizar_tarea', ['proyecto_id' => 'p1', 'tarea_id' => 't1', 'responsables' => ['Nadie Inventado']], $token)['error']);

        $this->tool('agregar_subtareas', ['proyecto_id' => 'p1', 'tarea_id' => 't1', 'subtareas' => ['A']], $token);
        preg_match_all('/cambio_id: (\w+)/', $this->tool('ver_cambios_recientes', ['texto' => 'Maqueta'], $token)['text'], $m);
        $this->assertCount(2, $m[1]);
        // Restaurar el más antiguo (con forzar, porque después se agregaron subtareas) deja la tarea como al inicio.
        $this->assertFalse($this->tool('restaurar_cambio', ['cambio_id' => end($m[1]), 'forzar' => true], $token)['error']);
        $task = collect((new FileStore('proyectos.json'))->find('p1')['tareas'])->firstWhere('id', 't1');
        $this->assertArrayNotHasKey('owner_ids', $task);
        $this->assertSame([], $task['subtasks'] ?? []);
    }

    public function test_converts_lead_into_client(): void
    {
        $token = $this->token();
        (new FileStore('leads.json'))->create(['id' => 'l1', 'nombre' => 'Pedro Ruiz', 'email' => 'pedro@panaderia.test', 'telefono' => '300', 'etapa' => 'Contactado', 'empresa' => 'Panadería Ruiz']);

        $res = $this->tool('convertir_lead_cliente', ['lead_id' => 'l1', 'moneda' => 'COP'], $token);
        $this->assertFalse($res['error'], $res['text']);
        $client = collect((new FileStore('clientes.json'))->all())->firstWhere('empresa', 'Panadería Ruiz');
        $this->assertSame('pedro@panaderia.test', $client['contacto_email']);
        $lead = (new FileStore('leads.json'))->find('l1');
        $this->assertSame('Cliente', $lead['etapa']);
        $this->assertSame($client['id'], $lead['cliente_id']);
        $this->assertTrue($this->tool('convertir_lead_cliente', ['lead_id' => 'l1'], $token)['error']);
        $this->assertCount(2, (new FileStore('clientes.json'))->all());
    }

    public function test_reminders_are_created_listed_and_completed(): void
    {
        $this->project();
        $token = $this->token();
        $this->assertFalse($this->tool('crear_recordatorio', ['texto' => 'Escribirle a Laura', 'fecha' => now()->addDays(2)->toDateString(), 'prioridad' => 'Urgente'], $token)['error']);
        $this->assertFalse($this->tool('crear_recordatorio', ['texto' => 'Revisar maqueta', 'proyecto_id' => 'p1', 'tarea_id' => 't1'], $token)['error']);

        $row = (new FileStore('header_reminders.json'))->find('user:' . \Illuminate\Support\Str::slug('hector@indigo.test', '-'));
        $this->assertCount(2, $row['payload']['items']);
        $this->assertSame('task', collect($row['payload']['items'])->firstWhere('text', 'Revisar maqueta')['link']['type']);

        $list = $this->tool('ver_recordatorios', [], $token)['text'];
        $this->assertStringContainsString('Escribirle a Laura', $list);
        preg_match('/Escribirle a Laura.*recordatorio_id: (\w+)/', $list, $m);
        $this->assertFalse($this->tool('completar_recordatorio', ['recordatorio_id' => $m[1]], $token)['error']);
        $this->assertStringNotContainsString('Escribirle a Laura', $this->tool('ver_recordatorios', [], $token)['text']);
    }

    public function test_reminder_links_to_a_project_column_and_can_be_changed(): void
    {
        $project = $this->project();
        (new FileStore('proyectos.json'))->update($project['id'], ['task_stages' => ['Por hacer', 'Revisión final']]);
        $token = $this->token();
        $store = new FileStore('header_reminders.json');

        $this->assertFalse($this->tool('crear_recordatorio', [
            'texto' => 'Revisar entregables', 'proyecto_id' => 'p1', 'columna' => 'Revisión final',
        ], $token)['error']);

        $item = $store->all()[0]['payload']['items'][0];
        $key = $store->all()[0]['id'];
        $this->assertSame('list', $item['link']['type']);
        $this->assertSame('Revisión final', $item['link']['id']);
        $this->assertStringContainsString('/proyectos?open_project=p1&open_list=Revisi%C3%B3n%20final', $this->tool('ver_recordatorios', [], $token)['text']);

        $edit = ['recordatorio_id' => $item['id'], 'proyecto_id' => 'p1'];
        $this->assertTrue($this->tool('editar_recordatorio', $edit + ['columna' => 'No existe'], $token)['error']);
        $this->assertTrue($this->tool('editar_recordatorio', $edit + ['columna' => 'Por hacer', 'tarea_id' => 't1'], $token)['error']);
        $this->assertSame($item['link'], $store->find($key)['payload']['items'][0]['link']);

        $this->assertFalse($this->tool('editar_recordatorio', $edit + ['tarea_id' => 't1'], $token)['error']);
        $this->assertSame('task', $store->find($key)['payload']['items'][0]['link']['type']);
        $this->assertStringContainsString('open_task=t1', $this->tool('ver_recordatorios', [], $token)['text']);

        $this->assertFalse($this->tool('editar_recordatorio', ['recordatorio_id' => $item['id'], 'proyecto_id' => ''], $token)['error']);
        $this->assertNull($store->find($key)['payload']['items'][0]['link']);
    }

    public function test_meetings_need_confirmation(): void
    {
        $token = $this->token();
        (new FileStore('leads.json'))->create(['id' => 'l1', 'nombre' => 'Pedro Ruiz', 'email' => 'pedro@panaderia.test', 'etapa' => 'Contactado']);
        $day = now()->addDays(3)->toDateString();

        $prev = $this->tool('preparar_reunion', ['lead_id' => 'l1', 'titulo' => 'Llamada inicial', 'fecha' => $day, 'hora' => '10:00', 'tipo' => 'llamada'], $token);
        $this->assertFalse($prev['error'], $prev['text']);
        $this->assertStringContainsString('pedro@panaderia.test', $prev['text']);
        $this->assertSame([], (new FileStore('leads.json'))->find('l1')['agenda'] ?? []);
        preg_match('/confirmación: (\w{3}-\w{3})/', $prev['text'], $m);
        $res = $this->tool('confirmar_accion', ['codigo' => $m[1]], $token);
        $this->assertFalse($res['error'], $res['text']);
        $this->assertSame('Llamada inicial', (new FileStore('leads.json'))->find('l1')['agenda'][0]['titulo']);

        $prev = $this->tool('preparar_reunion', ['cliente_id' => 'c1', 'titulo' => 'Revisión web', 'fecha' => $day, 'hora' => '15:00', 'duracion_min' => 45, 'tipo' => 'llamada'], $token);
        $this->assertStringContainsString('laura@laurel.test', $prev['text']);
        preg_match('/confirmación: (\w{3}-\w{3})/', $prev['text'], $m);
        $res = $this->tool('confirmar_accion', ['codigo' => $m[1]], $token);
        $this->assertFalse($res['error'], $res['text']);
        $meeting = collect((new FileStore('reuniones.json'))->all())->firstWhere('titulo', 'Revisión web');
        $this->assertSame('15:45', $meeting['hora_fin']);

        $this->assertTrue($this->tool('preparar_reunion', ['lead_id' => 'l1', 'titulo' => 'X', 'fecha' => now()->subDay()->toDateString(), 'hora' => '10:00'], $token)['error']);
    }

    public function test_weekly_summary(): void
    {
        $token = $this->token();
        (new FileStore('proyectos.json'))->create(['id' => 'p2', 'titulo' => 'Branding Sol', 'vencimiento' => now()->subDays(3)->toDateString(), 'progreso' => 40,
            'tareas' => [['id' => 't9', 'texto' => 'Logo final', 'done' => false, 'due_date' => now()->subDay()->toDateString()],
                ['id' => 't8', 'texto' => 'Manual', 'done' => false, 'due_date' => now()->addDays(2)->toDateString()]]]);
        (new FileStore('leads.json'))->save([['id' => 'l9', 'nombre' => 'Olvidado', 'etapa' => 'Contactado', 'created_at' => now()->subDays(20)->toISOString(), 'updated_at' => now()->subDays(20)->toISOString()]]);

        $text = $this->tool('resumen_semanal', [], $token)['text'];
        $this->assertMatchesRegularExpression('/Tareas vencidas \(1\):\n- Logo final/', $text);
        $this->assertMatchesRegularExpression('/vencen esta semana \(1\):\n- Manual/', $text);
        $this->assertStringContainsString('Branding Sol', $text);
        $this->assertStringContainsString('Olvidado', $text);
    }

    public function test_guides_are_available_as_tool_and_prompt(): void
    {
        $token = $this->token();
        $guide = $this->tool('ver_guia', ['tema' => 'planear-proyecto'], $token)['text'];
        $this->assertStringContainsString('Planear proyecto', $guide);
        $this->assertStringContainsString('Wireframes', $guide);
        $this->assertStringContainsString('Brief creativo', $this->tool('ver_guia', ['tema' => 'necesito armar el brief del cliente'], $token)['text']);

        $prompts = $this->rpc('prompts/list', [], $token)->assertOk()->json('result.prompts');
        $this->assertContains('guia_indigo', array_column($prompts, 'name'));
        $messages = $this->rpc('prompts/get', ['name' => 'guia_indigo', 'arguments' => ['guia' => 'leads', 'pedido' => 'ordena mis leads']], $token)->assertOk()->json('result.messages');
        $text = json_encode($messages, JSON_UNESCAPED_UNICODE);
        $this->assertStringContainsString('Clasificar y mover leads', $text);
        $this->assertStringContainsString('ordena mis leads', $text);
    }

    public function test_creates_full_project_and_rejects_duplicates(): void
    {
        // El almacenamiento recupera datos de respaldo si el archivo está vacío; un registro lo evita.
        Storage::disk('local')->put('proyectos.json', json_encode([['id' => 'p0', 'titulo' => 'Otro proyecto', 'tareas' => []]]));
        $token = $this->token();
        $res = $this->tool('crear_proyecto', [
            'titulo' => 'Web Laurel', 'cliente_id' => 'c1', 'descripcion' => 'Sitio corporativo',
            'tareas' => [
                ['titulo' => 'Brief y referencias', 'columna' => 'Por hacer', 'descripcion' => 'Reunión inicial', 'subtareas' => ['Cuestionario', 'Accesos']],
                ['titulo' => 'Wireframes de home', 'columna' => 'En proceso'],
            ],
        ], $token);
        $this->assertFalse($res['error'], $res['text']);

        $project = collect((new FileStore('proyectos.json'))->all())->firstWhere('titulo', 'Web Laurel');
        $this->assertSame(['Por hacer', 'En proceso', 'Revisión', 'Terminado'], $project['task_stages']);
        $this->assertCount(2, $project['tareas']);
        $this->assertCount(2, $project['tareas'][0]['subtasks']);
        $this->assertSame('En proceso', $project['tareas'][1]['board_stage']);

        $this->assertTrue($this->tool('crear_proyecto', ['titulo' => 'web laurel'], $token)['error']);
        $this->assertCount(2, (new FileStore('proyectos.json'))->all());
    }

    public function test_contract_from_template_to_sent(): void
    {
        $token = $this->token();
        $res = $this->tool('crear_contrato_desde_plantilla', ['plantilla' => 'sitio_web', 'cliente_id' => 'c1', 'monto' => 4500000], $token);
        $this->assertFalse($res['error'], $res['text']);
        $this->assertStringContainsString('[PLAZO]', $res['text']);
        $contract = collect((new FileStore('contratos.json'))->all())->first();
        $this->assertStringContainsString('Café Laurel', $contract['contenido']);
        $this->assertStringContainsString('COP 4.500.000', $contract['contenido']);
        $this->assertStringNotContainsString('[CLIENTE]', $contract['contenido']);

        $res = $this->tool('completar_contrato', ['contrato_id' => $contract['id'], 'datos' => ['[PLAZO]' => '8 semanas', 'DOMINIO' => 'cafelaurel.co']], $token);
        $this->assertFalse($res['error'], $res['text']);
        $contract = (new FileStore('contratos.json'))->find($contract['id']);
        $this->assertStringContainsString('8 semanas', $contract['contenido']);
        $this->assertStringContainsString('cafelaurel.co', $contract['contenido']);
        $this->assertStringContainsString('Datos pendientes:', $this->tool('ver_contrato', ['contrato_id' => $contract['id']], $token)['text']);

        $prev = $this->tool('preparar_envio_contrato', ['contrato_id' => $contract['id']], $token)['text'];
        $this->assertStringContainsString('laura@laurel.test', $prev);
        Mail::assertNothingSent();
        preg_match('/confirmación: (\w{3}-\w{3})/', $prev, $m);
        $res = $this->tool('confirmar_accion', ['codigo' => $m[1]], $token);
        $this->assertFalse($res['error'], $res['text']);
        $this->assertSame('Enviado', (new FileStore('contratos.json'))->find($contract['id'])['estado']);
        Mail::assertSent(\App\Mail\GenericMail::class, fn ($mail) => $mail->hasTo('laura@laurel.test') && ($mail->attachment['mime'] ?? '') === 'application/pdf');
        $this->assertStringContainsString('Enlace del cliente', $this->tool('ver_contrato', ['contrato_id' => $contract['id']], $token)['text']);

        $this->assertTrue($this->tool('crear_contrato_desde_plantilla', ['plantilla' => 'nda', 'cliente_id' => 'c1'], $this->token('lectura'))['error']);
    }

    public function test_reminders_with_categories_lists_and_edits(): void
    {
        $token = $this->token();
        $this->assertFalse($this->tool('organizar_recordatorios', ['accion' => 'crear_categoria', 'nombre' => 'Espadas'], $token)['error']);
        $this->assertFalse($this->tool('crear_recordatorio', ['texto' => 'Black Friday', 'categoria' => 'Espadas', 'prioridad' => 'Urgente', 'fecha' => now()->addDays(3)->toDateString()], $token)['error']);
        $this->assertFalse($this->tool('crear_recordatorio', ['texto' => 'Ideas de ventas', 'categoria' => 'Variado', 'lista' => 'Ideas'], $token)['error']);

        $view = $this->tool('ver_recordatorios', [], $token)['text'];
        $this->assertMatchesRegularExpression('/Espadas \(1\):\n- \[ \] Black Friday/', $view);
        $this->assertStringContainsString('Variado › Ideas (1)', $view);
        $this->assertStringNotContainsString('Ideas de ventas', $this->tool('ver_recordatorios', ['categoria' => 'Espadas'], $token)['text']);

        preg_match('/Black Friday.*recordatorio_id: (\w+)/', $view, $m);
        $res = $this->tool('editar_recordatorio', ['recordatorio_id' => $m[1], 'texto' => 'Campaña Black Friday', 'fecha' => '', 'categoria' => 'Variado', 'lista' => 'Ideas'], $token);
        $this->assertFalse($res['error'], $res['text']);
        $view = $this->tool('ver_recordatorios', [], $token)['text'];
        $this->assertStringContainsString('Variado › Ideas (2)', $view);
        $this->assertStringContainsString('Campaña Black Friday · Urgente', $view);

        $this->assertFalse($this->tool('organizar_recordatorios', ['accion' => 'renombrar_categoria', 'nombre' => 'Variado', 'nuevo_nombre' => 'General'], $token)['error']);
        $this->assertStringContainsString('General › Ideas (2)', $this->tool('ver_recordatorios', [], $token)['text']);
        $this->assertTrue($this->tool('organizar_recordatorios', ['accion' => 'crear_categoria', 'nombre' => 'espadas'], $token)['error']);
    }

    public function test_mcp_reorders_and_removes_reminder_lists_with_explicit_content_handling(): void
    {
        $token = $this->token();
        foreach ([
            ['Primera', 'Uno', 'Conservar uno'],
            ['Primera', 'Dos', 'Conservar dos'],
            ['Segunda', 'Uno', 'Borrar uno'],
            ['Segunda', 'Dos', 'Borrar dos'],
        ] as [$category, $list, $text]) {
            $this->assertFalse($this->tool('crear_recordatorio', ['texto' => $text, 'categoria' => $category, 'lista' => $list], $token)['error']);
        }
        $current = fn () => (new FileStore('header_reminders.json'))->all()[0]['payload'];
        $organize = fn (array $args) => $this->tool('organizar_recordatorios', $args, $token);

        $this->assertFalse($organize(['accion' => 'reordenar_categoria', 'nombre' => 'Segunda', 'posicion' => 1])['error']);
        $this->assertSame(['Segunda', 'Primera'], array_column($current()['categories'], 'title'));
        $this->assertFalse($organize(['accion' => 'reordenar_lista', 'nombre' => 'Dos', 'categoria' => 'Primera', 'posicion' => 1])['error']);
        $this->assertSame(['Dos', 'Uno'], array_column(array_slice($current()['sections'], 0, 2), 'title'));

        $remove = ['accion' => 'eliminar_lista', 'nombre' => 'Uno', 'categoria' => 'Primera'];
        $this->assertTrue($organize($remove)['error']);
        $this->assertFalse($organize($remove + ['contenido' => 'mover_a_raiz'])['error']);
        $this->assertFalse($organize(['accion' => 'eliminar_categoria', 'nombre' => 'Primera', 'contenido' => 'mover_a_raiz'])['error']);
        $this->assertFalse($organize(['accion' => 'eliminar_categoria', 'nombre' => 'Segunda', 'contenido' => 'eliminar'])['error']);
        $payload = $current();
        $this->assertSame(['Sin lista'], array_column($payload['categories'], 'title'));
        $this->assertCount(1, $payload['sections']);
        $this->assertSame(['Conservar dos', 'Conservar uno'], array_column($payload['items'], 'text'));
        $this->assertSame(array_fill(0, 2, $payload['sections'][0]['id']), array_column($payload['items'], 'sectionId'));

        $change = collect((new FileStore('mcp_history.json'))->all())->last();
        $this->assertFalse($this->tool('restaurar_cambio', ['cambio_id' => $change['id']], $token)['error']);
        $this->assertCount(4, $current()['items']);
    }

    public function test_lists_tasks_across_projects(): void
    {
        Storage::disk('local')->put('proyectos.json', json_encode([
            ['id' => 'p1', 'titulo' => 'Web Laurel', 'tareas' => [
                ['id' => 't1', 'texto' => 'Logo final', 'done' => false, 'due_date' => now()->subDay()->toDateString(), 'owners' => ['Laura Diseño'], 'board_stage' => 'En proceso'],
                ['id' => 't2', 'texto' => 'Manual', 'done' => false, 'due_date' => now()->addDays(3)->toDateString(), 'board_stage' => 'Por hacer'],
                ['id' => 't3', 'texto' => 'Brief', 'done' => true, 'board_stage' => 'Terminado'],
            ]],
            ['id' => 'p2', 'titulo' => 'Archivado', 'archived' => true, 'tareas' => [['id' => 't9', 'texto' => 'Vieja', 'done' => false]]],
        ]));
        $token = $this->token();

        $pending = $this->tool('listar_tareas', [], $token)['text'];
        $this->assertStringContainsString('Logo final', $pending);
        $this->assertStringNotContainsString('Brief', $pending);
        $this->assertStringNotContainsString('Vieja', $pending);
        $this->assertStringContainsString('tarea_id: t1', $this->tool('listar_tareas', ['estado' => 'vencidas'], $token)['text']);
        $this->assertStringNotContainsString('Manual', $this->tool('listar_tareas', ['estado' => 'vencidas'], $token)['text']);
        $this->assertStringContainsString('Manual', $this->tool('listar_tareas', ['vencen_en_dias' => 7], $token)['text']);
        $this->assertStringContainsString('Logo final', $this->tool('listar_tareas', ['responsable' => 'laura'], $token)['text']);
    }
}
