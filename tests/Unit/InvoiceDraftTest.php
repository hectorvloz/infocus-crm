<?php

namespace Tests\Unit;

use App\Support\Ai\InvoiceDraft;
use App\Support\Ai\AiActionExecutor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvoiceDraftTest extends TestCase
{
    public function test_calculates_totals_and_resolves_only_exact_client(): void
    {
        Storage::fake('local');
        Storage::put('clientes.json', json_encode([
            ['id' => 'c1', 'empresa' => 'Dproperty'],
            ['id' => 'c2', 'empresa' => 'Acme'],
        ]));
        Storage::put('settings.json', json_encode([['id' => 'settings', 'base_currency' => 'USD']]));

        $draft = (new InvoiceDraft())->prepare([
            'client_id' => 'c1',
            'currency' => 'USD',
            'due_date' => now()->addDays(15)->toDateString(),
            'tax_rate' => 10,
            'items' => [
                ['description' => 'Diseño', 'quantity' => 2, 'price' => '12.35'],
                ['description' => 'Hosting', 'quantity' => 1, 'price' => '5.00'],
            ],
        ]);

        $this->assertSame('c1', $draft['cliente_id']);
        $this->assertSame(29.7, $draft['subtotal']);
        $this->assertSame(2.97, $draft['impuestos']);
        $this->assertSame(32.67, $draft['total']);
        $this->assertSame('En borrador', $draft['estado']);
    }

    public function test_rejects_unknown_client_and_missing_financial_details(): void
    {
        Storage::fake('local');
        Storage::put('clientes.json', json_encode([['id' => 'c1', 'empresa' => 'Dproperty']]));

        $this->expectException(\InvalidArgumentException::class);
        (new InvoiceDraft())->prepare(['client_id' => 'wrong', 'currency' => 'USD']);
    }

    public function test_confirmed_draft_is_created_once_and_never_sent(): void
    {
        Storage::fake('local');
        Storage::put('clientes.json', json_encode([['id' => 'c1', 'empresa' => 'Dproperty']]));
        Storage::put('settings.json', json_encode([['id' => 'settings', 'base_currency' => 'USD']]));
        Storage::put('facturas.json', json_encode([['id' => 'existing', 'numero' => 'INV-0001', 'estado' => 'En borrador']]));
        Auth::shouldReceive('user')->andReturn((object) ['id' => 'u1', 'role' => 'admin']);
        Auth::shouldReceive('id')->andReturn('u1');
        $context = [
            'chat_id' => 'chat-1',
            'structured_action' => [
                'type' => 'create_invoice_draft',
                'fields' => [
                    'client_id' => 'c1', 'currency' => 'USD',
                    'due_date' => now()->addDays(7)->toDateString(), 'tax_rate' => 0,
                    'items' => [['description' => 'Diseño', 'quantity' => 1, 'price' => '100.00']],
                ],
            ],
        ];
        $executor = new AiActionExecutor();
        $first = $executor->execute('Factura propuesta: Diseño', $context);
        $second = $executor->execute('Factura propuesta: Diseño', $context);

        $this->assertTrue($first['ok']);
        $this->assertTrue($second['ok']);
        $invoices = json_decode(Storage::get('facturas.json'), true);
        $this->assertCount(2, $invoices);
        $this->assertSame('En borrador', $invoices[1]['estado']);
        $this->assertArrayNotHasKey('sent_at', $invoices[1]);
    }
}
