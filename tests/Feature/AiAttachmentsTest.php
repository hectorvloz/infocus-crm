<?php

namespace Tests\Feature;

use App\Mail\GenericMail;
use App\Mcp\Support\McpTokens;
use App\Models\User;
use App\Repositories\FileStore;
use App\Support\Ai\AiAttachments;
use App\Support\Ai\AiToolBridge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AiAttachmentsTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'twilio-test-token';

    private const OWNER = '+573001112233';

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    private User $admin;

    private array $aiReplies = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.disks.local.root' => storage_path('framework/testing/disks/local')]);
        Storage::fake('public');
        foreach (['mcp_activity.json', 'mcp_tokens.json', 'clientes.json', 'leads.json', 'facturas.json', 'gastos.json', 'proyectos.json', 'reuniones.json',
            'contratos.json', 'settings.json', 'roles.json', 'mis_notas.json', 'header_reminders.json', 'ai_memories.json', 'ai_action_logs.json',
            'timeline.json', 'users.json', 'mcp_pending_actions.json', 'email_templates.json', 'email_history.json', 'mcp_history.json',
            'whatsapp_chats.json', 'ai_attachments.json', 'ai_chats.json'] as $file) {
            Storage::disk('local')->put($file, '[]');
        }
        Mail::fake();
        $this->admin = User::factory()->create(['role' => 'admin', 'name' => 'Héctor']);
        (new FileStore('clientes.json'))->create(['id' => 'c1', 'empresa' => 'Somos Domus', 'contacto_nombre' => 'Laura Navarro', 'contacto_email' => 'laura@domus.test', 'estado' => 'Activo']);
        (new FileStore('settings.json'))->create([
            'id' => 'settings',
            'ai_enabled' => true,
            'ai_provider' => 'deepseek',
            'ai_model' => 'auto',
            'ai_api_keys' => ['deepseek' => 'ENC:' . Crypt::encryptString('test-key')],
            'ai_tools_enabled' => true,
            'whatsapp_enabled' => true,
            'whatsapp_twilio_sid' => 'A' . 'C' . str_repeat('0', 32),
            'whatsapp_twilio_token' => 'ENC:' . Crypt::encryptString(self::TOKEN),
            'whatsapp_from' => '+14155238886',
            'whatsapp_owner' => self::OWNER,
            'whatsapp_user_id' => (string) $this->admin->id,
        ]);

        Http::fake(function ($request) {
            $url = $request->url();
            if (str_contains($url, 'api.twilio.com') && str_contains($url, '/Media/')) {
                return str_contains($url, 'ME_PDF')
                    ? Http::response('%PDF-1.4 cotizacion', 200, ['Content-Type' => 'application/pdf'])
                    : Http::response(base64_decode(self::PNG), 200, ['Content-Type' => 'image/png']);
            }
            if (str_contains($url, 'api.twilio.com')) {
                return Http::response(['sid' => 'SM1'], 201);
            }
            $next = array_shift($this->aiReplies);

            return Http::response(is_callable($next) ? $next($request) : $next);
        });
    }

    private function incoming(string $body, string $sid, array $media = [])
    {
        $params = ['From' => 'whatsapp:' . self::OWNER, 'To' => 'whatsapp:+14155238886', 'Body' => $body, 'MessageSid' => $sid, 'NumMedia' => (string) count($media)];
        foreach ($media as $i => [$url, $mime]) {
            $params["MediaUrl{$i}"] = $url;
            $params["MediaContentType{$i}"] = $mime;
        }
        ksort($params);
        $data = url('/webhooks/whatsapp');
        foreach ($params as $k => $v) {
            $data .= $k . $v;
        }

        return $this->withHeader('X-Twilio-Signature', base64_encode(hash_hmac('sha1', $data, self::TOKEN, true)))->post('/webhooks/whatsapp', $params);
    }

    private function mediaUrl(string $id): string
    {
        return 'https://api.twilio.com/2010-04-01/Accounts/AC1/Messages/MM1/Media/' . $id;
    }

    private function text(string $content): array
    {
        return ['choices' => [['message' => ['role' => 'assistant', 'content' => $content]]]];
    }

    private function toolCall(string $name, array $arguments): array
    {
        return ['choices' => [['message' => ['role' => 'assistant', 'content' => '', 'tool_calls' => [[
            'id' => 'call_1', 'type' => 'function', 'function' => ['name' => $name, 'arguments' => json_encode($arguments)],
        ]]]]]];
    }

    /** Id ADJ... que Infocus le contó al modelo en el último mensaje del usuario. */
    private static function attachmentIdFrom($request): string
    {
        $user = collect($request['messages'])->where('role', 'user')->last();
        $text = is_array($user['content']) ? collect($user['content'])->where('type', 'text')->pluck('text')->implode(' ') : $user['content'];
        preg_match('/\b(ADJ[A-Z0-9]{8})\b/', $text, $m);

        return $m[1];
    }

    private function twilioMessages(): array
    {
        return Http::recorded()->map(fn ($p) => $p[0])
            ->filter(fn ($r) => str_contains($r->url(), 'api.twilio.com') && str_contains($r->url(), 'Messages.json'))->values()->all();
    }

    public function test_receipt_photo_by_whatsapp_becomes_an_expense_with_its_receipt(): void
    {
        $this->aiReplies = [
            function ($request) {
                $user = collect($request['messages'])->where('role', 'user')->last();
                $this->assertSame('image_url', collect($user['content'])->pluck('type')->last(), 'La foto le llega al modelo.');

                return $this->toolCall('registrar_gasto', ['concepto' => 'Hosting', 'monto' => 90000, 'moneda' => 'COP', 'comprobante_adjunto_id' => self::attachmentIdFrom($request)]);
            },
            $this->text('Registré el gasto de COP 90.000 con su comprobante.'),
        ];

        $this->incoming('Registra este recibo', 'SM_r', [[$this->mediaUrl('ME_IMG'), 'image/png']])->assertOk();

        $gasto = collect((new FileStore('gastos.json'))->all())->first();
        $this->assertSame(90000.0, (float) $gasto['monto']);
        $this->assertStringStartsWith('comprobantes/', $gasto['comprobante_path']);
        Storage::disk('public')->assertExists($gasto['comprobante_path']);
        $this->assertSame('Registré el gasto de COP 90.000 con su comprobante.', $this->twilioMessages()[0]['Body']);
    }

    public function test_pdf_from_whatsapp_is_sent_by_email_after_preview_and_yes(): void
    {
        $this->aiReplies = [
            function ($request) {
                $parts = collect(collect($request['messages'])->where('role', 'user')->last()['content']);
                $this->assertFalse($parts->contains('type', 'file'), 'DeepSeek no recibe el PDF crudo.');
                $this->assertStringContainsString('[PDF adjunto', $parts->where('type', 'text')->pluck('text')->implode(' '));

                return $this->toolCall('preparar_correo', ['cliente_id' => 'c1', 'asunto' => 'Cotización', 'mensaje' => 'Hola {cliente}, te comparto la cotización.', 'adjuntos' => [self::attachmentIdFrom($request)]]);
            },
            $this->text('Te preparé el correo con la cotización. ¿Lo envío?'),
        ];
        $this->incoming('Envíale esta cotización a Laura', 'SM_p1', [[$this->mediaUrl('ME_PDF'), 'application/pdf']])->assertOk();

        $preview = $this->twilioMessages()[0]['Body'];
        $this->assertMatchesRegularExpression('/Adjuntos: whatsapp-.*\.pdf/', $preview);
        Mail::assertNothingSent();

        $code = AiToolBridge::codesIn($preview)[0];
        $this->aiReplies = [$this->toolCall('confirmar_accion', ['codigo' => $code]), $this->text('Enviado con el adjunto.')];
        $this->incoming('Sí, envíalo', 'SM_p2')->assertOk();

        Mail::assertSent(GenericMail::class, function (GenericMail $mail) {
            $files = $mail->attachments();

            return $mail->hasTo('laura@domus.test') && count($files) === 1;
        });
    }

    public function test_media_outside_twilio_is_not_downloaded(): void
    {
        $this->aiReplies = [$this->text('Ok')];

        $this->incoming('', 'SM_x', [['https://evil.test/archivo.png', 'image/png']])->assertOk();

        $this->assertSame([], (new FileStore('ai_attachments.json'))->all());
        $this->assertStringContainsString('no lo pude abrir', $this->twilioMessages()[0]['Body']);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'evil.test'));
    }

    public function test_chat_accepts_documents_and_tells_the_model_their_id(): void
    {
        $this->aiReplies = [
            function ($request) {
                $this->assertMatchesRegularExpression('/ADJ[A-Z0-9]{8} «cotizacion\.pdf»/', json_encode($request['messages'], JSON_UNESCAPED_UNICODE));

                return $this->text('Es una cotización. ¿La envío a algún cliente?');
            },
        ];

        $this->actingAs($this->admin)->post(route('api.ai.chat'), [
            'message' => 'Mira este documento',
            'files' => [UploadedFile::fake()->createWithContent('cotizacion.pdf', '%PDF-1.4 prueba')],
        ], ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])->assertOk();

        $saved = collect((new FileStore('ai_attachments.json'))->all())->first();
        $this->assertSame('application/pdf', $saved['mime']);
        $this->assertSame('chat', $saved['source']);
    }

    public function test_claude_can_upload_a_file_through_the_mcp(): void
    {
        $token = (new McpTokens())->create($this->admin->id, 'Pruebas', 'completo', 30)['token'];
        $res = $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => [
            'name' => 'subir_adjunto',
            'arguments' => ['nombre' => 'logo.png', 'tipo' => 'image/png', 'contenido_base64' => self::PNG],
        ]], ['Accept' => 'application/json, text/event-stream', 'Authorization' => 'Bearer ' . $token])->assertOk()->json('result');

        $this->assertFalse($res['isError'] ?? false, json_encode($res));
        $this->assertMatchesRegularExpression('/Adjunto guardado: ADJ[A-Z0-9]{8} «logo\.png»/', $res['content'][0]['text']);
    }

    public function test_attachments_are_private_and_expire(): void
    {
        $this->actingAs($this->admin);
        $file = (new AiAttachments())->save('hola', 'nota.txt', 'text/plain', 'chat');
        $this->assertNotNull((new AiAttachments())->find($file['id']));
        $this->assertNull((new AiAttachments())->save('MZ', 'virus.exe', 'application/x-msdownload', 'chat'));

        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->assertNull((new AiAttachments())->find($file['id']), 'Otro usuario no lo ve.');

        $this->actingAs($this->admin);
        $this->travel(8)->days();
        $this->assertNull((new AiAttachments())->find($file['id']));
        $this->assertSame(1, (new AiAttachments())->prune());
        Storage::disk('local')->assertMissing($file['path']);
    }
}
