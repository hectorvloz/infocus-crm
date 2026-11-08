<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\FileStore;
use App\Support\Ai\AiService;
use App\Support\Ai\AiToolBridge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EnviarmeArchivoTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    private User $admin;

    private array $aiReplies = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.disks.local.root' => storage_path('framework/testing/disks/local'), 'app.url' => 'https://crm.test']);
        Storage::fake('public');
        foreach (['mcp_activity.json', 'clientes.json', 'leads.json', 'facturas.json', 'gastos.json', 'proyectos.json', 'reuniones.json',
            'contratos.json', 'settings.json', 'roles.json', 'mis_notas.json', 'header_reminders.json', 'ai_memories.json', 'ai_action_logs.json',
            'timeline.json', 'users.json', 'whatsapp_chats.json', 'ai_attachments.json', 'whatsapp_textmebot_rate.json'] as $file) {
            Storage::disk('local')->put($file, '[]');
        }
        \Illuminate\Support\Sleep::fake();
        $this->admin = User::factory()->create(['role' => 'admin', 'name' => 'Héctor']);
        $this->settings('textmebot');
        Http::fake(function ($request) {
            if (str_contains($request->url(), 'api.textmebot.com') || str_contains($request->url(), 'graph.facebook.com')) {
                return Http::response(['status' => 'ok']);
            }
            $next = array_shift($this->aiReplies);

            return Http::response(is_callable($next) ? $next($request) : $next);
        });
    }

    private function settings(string $provider): void
    {
        $store = new FileStore('settings.json');
        $data = [
            'id' => 'settings',
            'ai_enabled' => true, 'ai_provider' => 'deepseek', 'ai_model' => 'auto', 'ai_tools_enabled' => true,
            'ai_api_keys' => ['deepseek' => 'ENC:' . Crypt::encryptString('test-key')],
            'whatsapp_enabled' => true, 'whatsapp_provider' => $provider, 'whatsapp_owner' => '+573001112233', 'whatsapp_user_id' => (string) $this->admin->id,
            'whatsapp_textmebot_apikey' => 'ENC:' . Crypt::encryptString('tmb-key'), 'whatsapp_textmebot_webhook_key' => 'clave',
            'whatsapp_meta_phone_id' => '123', 'whatsapp_meta_token' => 'ENC:' . Crypt::encryptString('meta-token'),
            'whatsapp_meta_app_secret' => 'ENC:' . Crypt::encryptString('secret'), 'whatsapp_meta_version' => 'v23.0',
        ];
        $store->find('settings') ? $store->update('settings', $data) : $store->create($data);
    }

    private function toolCall(string $name, array $arguments): array
    {
        return ['choices' => [['message' => ['role' => 'assistant', 'content' => '', 'tool_calls' => [[
            'id' => 'call_1', 'type' => 'function', 'function' => ['name' => $name, 'arguments' => json_encode($arguments)],
        ]]]]]];
    }

    /** @return array<string, string> */
    private function textmebotQuery(): array
    {
        $request = Http::recorded()->map(fn ($p) => $p[0])->first(fn ($r) => str_contains($r->url(), 'api.textmebot.com'));
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

        return $query;
    }

    private function receipt(): string
    {
        Storage::disk('public')->put('comprobantes/recibo.png', base64_decode(self::PNG));
        (new FileStore('gastos.json'))->create(['id' => 'g1', 'concepto' => 'Hosting H1', 'monto' => 90000, 'moneda' => 'COP', 'fecha' => '2026-11-07', 'comprobante_path' => 'comprobantes/recibo.png']);

        return 'g1';
    }

    public function test_assistant_sends_a_receipt_to_my_whatsapp_through_a_signed_link(): void
    {
        $this->receipt();
        $this->aiReplies = [
            $this->toolCall('enviarme_archivo', ['tipo' => 'gasto', 'id' => 'g1']),
            ['choices' => [['message' => ['role' => 'assistant', 'content' => 'Te mandé el comprobante.']]]],
        ];
        $this->actingAs($this->admin);

        $reply = (new AiService())->reply('Mándame el comprobante del gasto de hosting');

        $this->assertSame(['enviarme_archivo'], $reply['tools_used']);
        $query = $this->textmebotQuery();
        $this->assertSame('+573001112233', $query['recipient']);
        $this->assertStringStartsWith('https://crm.test/adjuntos/temporal/ADJ', $query['file']);

        $path = substr($query['file'], strlen('https://crm.test'));
        auth()->logout();
        $this->get($path)->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->get(preg_replace('/signature=[^&]+/', 'signature=falsa', $path))->assertForbidden();
        $this->travel(16)->minutes();
        $this->get($path)->assertForbidden();
    }

    public function test_pdf_documents_go_as_document_with_filename(): void
    {
        $this->actingAs($this->admin);
        (new FileStore('ai_attachments.json'))->save([]);
        $file = (new \App\Support\Ai\AiAttachments())->save('%PDF-1.4 cotizacion', 'cotizacion.pdf', 'application/pdf', 'chat');

        $result = (new AiToolBridge())->call('enviarme_archivo', ['tipo' => 'adjunto', 'id' => $file['id'], 'mensaje' => 'La cotización']);

        $this->assertStringStartsWith('Te envié «cotizacion.pdf»', $result);
        $query = $this->textmebotQuery();
        $this->assertSame('cotizacion.pdf', $query['filename']);
        $this->assertSame('La cotización', $query['text']);
        $this->assertStringContainsString('/adjuntos/temporal/' . $file['id'], $query['document']);
    }

    public function test_official_providers_need_the_24_hour_window(): void
    {
        $this->settings('meta');
        $this->receipt();
        $this->actingAs($this->admin);

        $closed = (new AiToolBridge())->call('enviarme_archivo', ['tipo' => 'gasto', 'id' => 'g1']);
        $this->assertStringContainsString('24 horas', $closed);
        Http::assertNothingSent();

        (new FileStore('whatsapp_chats.json'))->create(['id' => 'owner', 'messages' => [], 'seen' => [], 'last_inbound_at' => now()->subHour()->toISOString()]);
        $open = (new AiToolBridge())->call('enviarme_archivo', ['tipo' => 'gasto', 'id' => 'g1']);
        $this->assertStringStartsWith('Te envié', $open);
        $sent = Http::recorded()->map(fn ($p) => $p[0])->first(fn ($r) => str_contains($r->url(), 'graph.facebook.com'));
        $this->assertSame('image', $sent['type']);
        $this->assertStringContainsString('/adjuntos/temporal/', $sent['image']['link']);
    }

    public function test_only_the_whatsapp_user_can_receive_files(): void
    {
        $this->receipt();
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $result = (new AiToolBridge())->call('enviarme_archivo', ['tipo' => 'gasto', 'id' => 'g1']);

        $this->assertStringContainsString('Solo el usuario conectado a WhatsApp', $result);
        Http::assertNothingSent();
    }

    public function test_reports_missing_records_clearly(): void
    {
        $this->actingAs($this->admin);
        (new FileStore('gastos.json'))->create(['id' => 'g2', 'concepto' => 'Sin recibo', 'monto' => 1, 'moneda' => 'COP']);

        $this->assertStringContainsString('no tiene comprobante', (new AiToolBridge())->call('enviarme_archivo', ['tipo' => 'gasto', 'id' => 'g2']));
        $this->assertStringContainsString('No encontré la factura', (new AiToolBridge())->call('enviarme_archivo', ['tipo' => 'factura', 'id' => 'INV-9999']));
    }

    public function test_invoice_pdf_is_generated_and_sent_by_number(): void
    {
        $this->actingAs($this->admin);
        (new FileStore('clientes.json'))->create(['id' => 'c1', 'empresa' => 'Somos Domus', 'contacto_nombre' => 'Laura', 'contacto_email' => 'laura@domus.test']);
        (new FileStore('facturas.json'))->create([
            'id' => 'f1', 'numero' => 'INV-0064', 'cliente_id' => 'c1', 'cliente' => 'Somos Domus', 'fecha' => '2026-10-16', 'vencimiento' => null,
            'moneda' => 'COP', 'estado' => 'Pendiente', 'items' => [['descripcion' => 'Hosting H1', 'cantidad' => 1, 'precio' => 130000, 'total' => 130000]],
            'subtotal' => 130000, 'impuesto' => 0, 'total' => 130000, 'pagos' => [],
        ]);

        $result = (new AiToolBridge())->call('enviarme_archivo', ['tipo' => 'factura', 'id' => 'inv-0064']);

        $this->assertStringStartsWith('Te envié «factura_INV-0064.pdf»', $result);
        $this->assertSame('factura_INV-0064.pdf', $this->textmebotQuery()['filename']);
    }
}
