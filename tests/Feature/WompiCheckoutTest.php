<?php

namespace Tests\Feature;

use App\Repositories\FileStore;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WompiCheckoutTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.disks.local.root' => storage_path('framework/testing/disks/local')]);

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
}
