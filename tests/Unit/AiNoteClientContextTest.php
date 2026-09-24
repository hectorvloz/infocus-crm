<?php

namespace Tests\Unit;

use App\Support\Ai\AiService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use Tests\TestCase;

class AiNoteClientContextTest extends TestCase
{
    public function test_current_note_uses_only_accessible_records_of_its_exact_client(): void
    {
        Storage::fake('local');
        Storage::put('clientes.json', json_encode([
            ['id' => 'c1', 'empresa' => 'Dproperty', 'ciudad' => 'Bogotá'],
            ['id' => 'c2', 'empresa' => 'Acme', 'ciudad' => 'Cali'],
        ]));
        Storage::put('mis_notas.json', json_encode([
            ['id' => 'n1', 'ownerKey' => 'u1', 'title' => 'Brief', 'plainText' => 'Campaña residencial', 'clientId' => 'c1'],
            ['id' => 'n2', 'ownerKey' => 'u1', 'title' => 'Acuerdo', 'plainText' => 'Usar fotos claras', 'clientId' => 'c1'],
            ['id' => 'n3', 'ownerKey' => 'u1', 'title' => 'Otro cliente', 'plainText' => 'Secreto Acme', 'clientId' => 'c2'],
            ['id' => 'n4', 'ownerKey' => 'u2', 'title' => 'Privada', 'plainText' => 'Secreto Dproperty', 'clientId' => 'c1'],
        ]));
        Storage::put('proyectos.json', json_encode([
            ['id' => 'p1', 'titulo' => 'Sitio Dproperty', 'descripcion' => 'Portal inmobiliario', 'cliente_id' => 'c1'],
            ['id' => 'p2', 'titulo' => 'Sitio Acme', 'descripcion' => 'Datos de Acme', 'cliente_id' => 'c2'],
        ]));
        Auth::shouldReceive('user')->andReturn((object) ['id' => 'u1', 'role' => 'admin']);
        Auth::shouldReceive('id')->andReturn('u1');

        $context = (new ReflectionMethod(new AiService(), 'currentPersonalNoteContext'))
            ->invoke(new AiService(), ['current_note' => ['id' => 'n1', 'client_id' => 'c2']]);

        $this->assertStringContainsString('Dproperty', $context);
        $this->assertStringContainsString('Usar fotos claras', $context);
        $this->assertStringContainsString('Portal inmobiliario', $context);
        $this->assertStringNotContainsString('Secreto Acme', $context);
        $this->assertStringNotContainsString('Secreto Dproperty', $context);
        $this->assertStringNotContainsString('Datos de Acme', $context);
    }

    public function test_invoice_lookup_from_note_defaults_to_the_same_client(): void
    {
        Storage::fake('local');
        Storage::put('clientes.json', json_encode([
            ['id' => 'c1', 'empresa' => 'Dproperty'],
            ['id' => 'c2', 'empresa' => 'Acme'],
        ]));
        Storage::put('mis_notas.json', json_encode([['id' => 'n1', 'ownerKey' => 'u1', 'clientId' => 'c1']]));
        Storage::put('facturas.json', json_encode([
            ['id' => 'f1', 'cliente_id' => 'c1', 'cliente' => 'Dproperty', 'numero' => 'INV-0001', 'total' => 100],
            ['id' => 'f2', 'cliente_id' => 'c2', 'cliente' => 'Acme', 'numero' => 'INV-0002', 'total' => 200],
        ]));
        Auth::shouldReceive('user')->andReturn((object) ['id' => 'u1', 'role' => 'admin']);
        Auth::shouldReceive('id')->andReturn('u1');

        $service = new AiService();
        $method = new ReflectionMethod($service, 'invoiceContext');
        $context = $method->invoke($service, ['current_note' => ['id' => 'n1']], 'Muéstrame las facturas');
        $this->assertStringContainsString('INV-0001', $context);
        $this->assertStringNotContainsString('INV-0002', $context);

        $crm = (new ReflectionMethod($service, 'buildCrmContext'))
            ->invoke($service, 'Muéstrame la factura de Acme', ['current_note' => ['id' => 'n1']]);
        $this->assertStringContainsString('INV-0002', $crm);
        $this->assertStringNotContainsString('INV-0001', $crm);
    }
}
