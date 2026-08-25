<?php

namespace Tests\Feature;

use App\Repositories\FileStore;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WompiCheckoutTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.disks.local.root' => storage_path('framework/testing/disks/local')]);
        Mail::fake();

        foreach ([
            'clientes.json',
            'facturas.json',
            'proyectos.json',
            'cotizaciones.json',
            'documentos.json',
            'document_folders.json',
            'mensajes.json',
            'settings.json',
            'portal_access_logs.json',
            'timelines.json',
        ] as $file) {
            Storage::disk('local')->put($file, '[]');
        }

        (new FileStore('clientes.json'))->create([
            'id' => 'client-1',
            'nombre' => 'Cliente Wompi',
            'contacto_email' => 'cliente@example.com',
        ]);

        (new FileStore('facturas.json'))->create([
            'id' => 'invoice-1',
            'cliente_id' => 'client-1',
            'numero' => 'INV-0001',
            'estado' => 'Pendiente',
            'moneda' => 'COP',
            'total' => 100000,
        ]);

        (new FileStore('facturas.json'))->create([
            'id' => 'wompiinvoice1',
            'cliente_id' => 'client-1',
            'numero' => 'INV-0002',
            'estado' => 'Pendiente',
            'moneda' => 'COP',
            'total' => 25000,
        ]);
    }

    public function test_checkout_does_not_redirect_to_wompi_without_integrity_secret(): void
    {
        (new FileStore('settings.json'))->create([
            'id' => 'settings',
            'payment_gateway' => 'wompi',
            'wompi_mode' => 'live',
            'wompi_public_key' => 'pub_prod_example',
            'wompi_currency' => 'COP',
        ]);

        $invoiceUrl = route('facturas.public', 'invoice-1');
        $response = $this->from($invoiceUrl)->get(route('public.pay.checkout', 'invoice-1'));

        $response->assertRedirect($invoiceUrl);
        $response->assertSessionHasErrors([
            'pago' => 'Wompi no esta configurado completamente: falta el Secreto de Integridad.',
        ]);
        $this->assertStringNotContainsString('checkout.wompi.co', (string) $response->headers->get('Location'));
    }

    public function test_checkout_redirect_includes_signature_when_integrity_secret_is_configured(): void
    {
        $integritySecret = 'prod_integrity_example';
        (new FileStore('settings.json'))->create([
            'id' => 'settings',
            'payment_gateway' => 'wompi',
            'wompi_mode' => 'live',
            'wompi_public_key' => 'pub_prod_example',
            'wompi_integrity_secret' => 'ENC:'.Crypt::encryptString($integritySecret),
            'wompi_event_secret' => 'ENC:'.Crypt::encryptString('prod_events_example'),
            'wompi_currency' => 'COP',
        ]);

        $response = $this->get(route('public.pay.checkout', 'invoice-1'));
        $location = (string) $response->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $response->assertRedirect();
        $this->assertStringStartsWith('https://checkout.wompi.co/p/?', $location);
        $this->assertArrayHasKey('signature:integrity', $query);
        $this->assertSame(
            hash('sha256', $query['reference'].$query['amount-in-cents'].$query['currency'].$integritySecret),
            $query['signature:integrity']
        );
    }

    public function test_checkout_stops_when_saved_integrity_secret_cannot_be_decrypted(): void
    {
        (new FileStore('settings.json'))->create([
            'id' => 'settings',
            'payment_gateway' => 'wompi',
            'wompi_mode' => 'live',
            'wompi_public_key' => 'pub_prod_example',
            'wompi_integrity_secret' => 'ENC:invalid-encrypted-value',
            'wompi_currency' => 'COP',
        ]);

        $invoiceUrl = route('facturas.public', 'invoice-1');
        $response = $this->from($invoiceUrl)->get(route('public.pay.checkout', 'invoice-1'));

        $response->assertRedirect($invoiceUrl);
        $response->assertSessionHasErrors([
            'pago' => 'Wompi no esta configurado completamente: falta el Secreto de Integridad.',
        ]);
    }

    public function test_checkout_does_not_start_without_the_wompi_event_secret(): void
    {
        (new FileStore('settings.json'))->create([
            'id' => 'settings',
            'payment_gateway' => 'wompi',
            'wompi_mode' => 'live',
            'wompi_public_key' => 'pub_prod_example',
            'wompi_integrity_secret' => 'ENC:'.Crypt::encryptString('prod_integrity_example'),
            'wompi_currency' => 'COP',
        ]);

        $invoiceUrl = route('facturas.public', 'invoice-1');
        $response = $this->from($invoiceUrl)->get(route('public.pay.checkout', 'invoice-1'));

        $response->assertRedirect($invoiceUrl);
        $response->assertSessionHasErrors([
            'pago' => 'Wompi no esta configurado completamente: falta el Secreto de Eventos.',
        ]);
    }

    public function test_webhook_rejects_an_approved_transaction_without_a_valid_signature(): void
    {
        (new FileStore('settings.json'))->create([
            'id' => 'settings',
            'wompi_mode' => 'live',
            'wompi_event_secret' => 'ENC:'.Crypt::encryptString('prod_events_example'),
        ]);

        $response = $this->postJson(route('webhooks.wompi'), [
            'event' => 'transaction.updated',
            'environment' => 'prod',
            'data' => [
                'transaction' => [
                    'id' => 'transaction-1',
                    'status' => 'APPROVED',
                    'amount_in_cents' => 2500000,
                    'currency' => 'COP',
                    'reference' => 'INV-wompiinvoice1-ABC123',
                ],
            ],
        ]);

        $response->assertUnauthorized();
        $this->assertSame('Pendiente', (new FileStore('facturas.json'))->find('wompiinvoice1')['estado']);
    }

    public function test_redirect_cannot_mark_invoice_paid_without_a_wompi_transaction_id(): void
    {
        (new FileStore('settings.json'))->create([
            'id' => 'settings',
            'wompi_mode' => 'live',
            'wompi_public_key' => 'pub_prod_example',
        ]);

        $response = $this->get(route('public.wompi.success', [
            'invoiceId' => 'wompiinvoice1',
            'invoice_id' => 'wompiinvoice1',
            'reference' => 'INV-wompiinvoice1-ABC123',
            'status' => 'APPROVED',
        ]));

        $response->assertRedirect(route('facturas.public', 'wompiinvoice1'));
        $response->assertSessionHasErrors('pago');
        $this->assertSame('Pendiente', (new FileStore('facturas.json'))->find('wompiinvoice1')['estado']);
    }

    public function test_valid_signed_webhook_marks_the_matching_invoice_paid(): void
    {
        $eventSecret = 'prod_events_example';
        $this->storeWompiSettings($eventSecret);
        $payload = $this->signedWompiEvent($eventSecret);

        $response = $this
            ->withHeader('X-Event-Checksum', $payload['signature']['checksum'])
            ->postJson(route('webhooks.wompi'), $payload);

        $response->assertOk()->assertJson(['ok' => true, 'processed' => true]);
        $invoice = (new FileStore('facturas.json'))->find('wompiinvoice1');
        $this->assertSame('Pagada', $invoice['estado']);
        $this->assertStringContainsString('transaction-1', $invoice['pagos'][0]['nota']);
    }

    public function test_signed_webhook_rejects_an_amount_that_does_not_match_the_invoice(): void
    {
        $eventSecret = 'prod_events_example';
        $this->storeWompiSettings($eventSecret);
        $payload = $this->signedWompiEvent($eventSecret, ['amount_in_cents' => 100]);

        $response = $this
            ->withHeader('X-Event-Checksum', $payload['signature']['checksum'])
            ->postJson(route('webhooks.wompi'), $payload);

        $response->assertUnprocessable()->assertJson(['ok' => false, 'error' => 'transaction_mismatch']);
        $this->assertSame('Pendiente', (new FileStore('facturas.json'))->find('wompiinvoice1')['estado']);
    }

    public function test_redirect_checks_wompi_api_before_marking_invoice_paid(): void
    {
        $this->storeWompiSettings('prod_events_example');
        Http::fake([
            'https://production.wompi.co/v1/transactions/transaction-1' => Http::response([
                'data' => [
                    'id' => 'transaction-1',
                    'status' => 'DECLINED',
                    'amount_in_cents' => 2500000,
                    'currency' => 'COP',
                    'reference' => 'INV-wompiinvoice1-ABC123',
                ],
            ]),
        ]);

        $response = $this->get(route('public.wompi.success', [
            'invoiceId' => 'wompiinvoice1',
            'invoice_id' => 'wompiinvoice1',
            'reference' => 'INV-wompiinvoice1-ABC123',
            'id' => 'transaction-1',
        ]));

        $response->assertRedirect(route('facturas.public', 'wompiinvoice1'));
        $response->assertSessionHasErrors('pago');
        $this->assertSame('Pendiente', (new FileStore('facturas.json'))->find('wompiinvoice1')['estado']);
        Http::assertSentCount(1);
    }

    public function test_redirect_marks_invoice_paid_only_after_wompi_api_confirms_it(): void
    {
        $this->storeWompiSettings('prod_events_example');
        Http::fake([
            'https://production.wompi.co/v1/transactions/transaction-1' => Http::response([
                'data' => [
                    'id' => 'transaction-1',
                    'status' => 'APPROVED',
                    'amount_in_cents' => 2500000,
                    'currency' => 'COP',
                    'reference' => 'INV-wompiinvoice1-ABC123',
                ],
            ]),
        ]);

        $response = $this->get(route('public.wompi.success', [
            'invoiceId' => 'wompiinvoice1',
            'invoice_id' => 'wompiinvoice1',
            'reference' => 'INV-wompiinvoice1-ABC123',
            'id' => 'transaction-1',
        ]));

        $response->assertRedirect(route('facturas.public', 'wompiinvoice1'));
        $response->assertSessionHas('msg_ok');
        $invoice = (new FileStore('facturas.json'))->find('wompiinvoice1');
        $this->assertSame('Pagada', $invoice['estado']);
        $this->assertStringContainsString('transaction-1', $invoice['pagos'][0]['nota']);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer pub_prod_example'));
    }

    public function test_repeated_valid_webhook_is_idempotent(): void
    {
        $eventSecret = 'prod_events_example';
        $this->storeWompiSettings($eventSecret);
        $payload = $this->signedWompiEvent($eventSecret);

        $this->postJson(route('webhooks.wompi'), $payload)->assertOk();
        $this->postJson(route('webhooks.wompi'), $payload)->assertOk();

        $invoice = (new FileStore('facturas.json'))->find('wompiinvoice1');
        $this->assertSame('Pagada', $invoice['estado']);
        $this->assertCount(1, $invoice['pagos']);
    }

    public function test_signed_webhook_supports_existing_invoice_ids_with_hyphens(): void
    {
        $eventSecret = 'prod_events_example';
        $this->storeWompiSettings($eventSecret);
        $payload = $this->signedWompiEvent($eventSecret, [
            'amount_in_cents' => 10000000,
            'reference' => 'INV-invoice-1-XYZ789',
        ]);

        $this->postJson(route('webhooks.wompi'), $payload)
            ->assertOk()
            ->assertJson(['ok' => true, 'processed' => true]);

        $this->assertSame('Pagada', (new FileStore('facturas.json'))->find('invoice-1')['estado']);
    }

    private function storeWompiSettings(string $eventSecret): void
    {
        (new FileStore('settings.json'))->create([
            'id' => 'settings',
            'payment_gateway' => 'wompi',
            'wompi_mode' => 'live',
            'wompi_public_key' => 'pub_prod_example',
            'wompi_integrity_secret' => 'ENC:'.Crypt::encryptString('prod_integrity_example'),
            'wompi_event_secret' => 'ENC:'.Crypt::encryptString($eventSecret),
            'wompi_currency' => 'COP',
        ]);
    }

    private function signedWompiEvent(string $eventSecret, array $transactionOverrides = []): array
    {
        $transaction = array_merge([
            'id' => 'transaction-1',
            'status' => 'APPROVED',
            'amount_in_cents' => 2500000,
            'currency' => 'COP',
            'reference' => 'INV-wompiinvoice1-ABC123',
        ], $transactionOverrides);
        $timestamp = 1787592598000;
        $checksum = hash(
            'sha256',
            $transaction['id'].$transaction['status'].$transaction['amount_in_cents'].$timestamp.$eventSecret
        );

        return [
            'event' => 'transaction.updated',
            'environment' => 'prod',
            'data' => ['transaction' => $transaction],
            'signature' => [
                'properties' => [
                    'transaction.id',
                    'transaction.status',
                    'transaction.amount_in_cents',
                ],
                'checksum' => $checksum,
            ],
            'timestamp' => $timestamp,
        ];
    }
}
