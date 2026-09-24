<?php

namespace App\Support\Ai;

use App\Repositories\FileStore;
use Illuminate\Support\Carbon;

class InvoiceDraft
{
    public function prepare(array $fields): array
    {
        $clients = collect((new FileStore('clientes.json'))->all());
        $clientId = trim((string) ($fields['client_id'] ?? ''));
        $client = $clientId !== ''
            ? $clients->first(fn ($item) => (string) ($item['id'] ?? '') === $clientId)
            : null;
        if (!$client && $clientId === '') {
            $clientName = trim((string) ($fields['client'] ?? ''));
            if ($clientName !== '') {
                $matches = $clients->filter(fn ($item) => mb_strtolower(trim((string) ($item['empresa'] ?? ''))) === mb_strtolower($clientName));
                $client = $matches->count() === 1 ? $matches->first() : null;
            }
        }
        if (!$client) {
            throw new \InvalidArgumentException('Necesito un cliente existente e inequívoco para preparar la factura.');
        }
        $declaredClient = trim((string) ($fields['client'] ?? ''));
        if ($declaredClient !== '' && mb_strtolower($declaredClient) !== mb_strtolower(trim((string) ($client['empresa'] ?? '')))) {
            throw new \InvalidArgumentException('El nombre y el ID del cliente no coinciden. Revisa la propuesta.');
        }
        $projectId = trim((string) ($fields['project_id'] ?? ''));
        if ($projectId !== '') {
            $project = (new FileStore('proyectos.json'))->find($projectId);
            if (!$project || (string) ($project['cliente_id'] ?? '') !== (string) $client['id']) {
                throw new \InvalidArgumentException('El proyecto indicado no pertenece al cliente de la factura.');
            }
        }

        $currency = strtoupper(trim((string) ($fields['currency'] ?? '')));
        $due = trim((string) ($fields['due_date'] ?? ''));
        $taxRate = $fields['tax_rate'] ?? null;
        if (!preg_match('/^[A-Z]{3}$/', $currency) || !$this->validDate($due) || !is_numeric($taxRate) || (int) $taxRate != $taxRate || $taxRate < 0 || $taxRate > 100) {
            throw new \InvalidArgumentException('Confirma moneda ISO, vencimiento y porcentaje de impuesto (0 a 100).');
        }
        $issueDate = Carbon::today()->toDateString();
        if ($due < $issueDate) {
            throw new \InvalidArgumentException('El vencimiento no puede ser anterior a hoy.');
        }

        $rawItems = $fields['items'] ?? [];
        if (!is_array($rawItems) || count($rawItems) < 1 || count($rawItems) > 30) {
            throw new \InvalidArgumentException('Indica entre 1 y 30 conceptos con cantidad y precio.');
        }
        $items = [];
        $subtotalCents = 0;
        foreach ($rawItems as $raw) {
            if (!is_array($raw)) {
                throw new \InvalidArgumentException('Cada concepto necesita descripción, cantidad y precio.');
            }
            $description = trim((string) ($raw['description'] ?? $raw['descripcion'] ?? $raw['name'] ?? ''));
            $quantity = $raw['quantity'] ?? $raw['cantidad'] ?? null;
            $price = $raw['price'] ?? $raw['precio'] ?? null;
            if ($description === '' || mb_strlen($description) > 500 || !is_numeric($quantity) || (float) $quantity <= 0 || !is_numeric($price) || !preg_match('/^\d+(?:\.\d{1,2})?$/', (string) $price)) {
                throw new \InvalidArgumentException('Revisa la descripción, cantidad y precio de cada concepto.');
            }
            $quantity = (float) $quantity;
            if ($quantity > 100000 || $price > 100000000) {
                throw new \InvalidArgumentException('Hay una cantidad o precio fuera de rango.');
            }
            $priceCents = (int) round((float) $price * 100);
            $subtotalCents += (int) round($quantity * $priceCents);
            $items[] = ['descripcion' => $description, 'cantidad' => $quantity, 'precio' => $priceCents / 100];
        }
        $taxCents = (int) round($subtotalCents * ((int) $taxRate) / 100);
        $settings = (new FileStore('settings.json'))->find('settings') ?: [];
        $baseCurrency = strtoupper((string) ($settings['base_currency'] ?? 'USD'));
        $rate = $fields['rate'] ?? null;
        if ($currency !== $baseCurrency && (!is_numeric($rate) || (float) $rate <= 0)) {
            throw new \InvalidArgumentException("Indica la tasa de cambio de {$currency} a {$baseCurrency} para este borrador.");
        }

        return [
            'cliente_id' => (string) $client['id'],
            'cliente' => (string) ($client['empresa'] ?? ''),
            'proyecto_id' => $projectId ?: null,
            'fecha' => $issueDate,
            'vencimiento' => $due,
            'moneda' => $currency,
            'tasa' => $currency === $baseCurrency ? null : (float) $rate,
            'tax_rate' => (int) $taxRate,
            'items' => $items,
            'subtotal' => $subtotalCents / 100,
            'impuestos' => $taxCents / 100,
            'total' => ($subtotalCents + $taxCents) / 100,
            'total_base' => $currency === $baseCurrency ? null : round((($subtotalCents + $taxCents) / 100) * (float) $rate, 2),
            'estado' => 'En borrador',
            'recurrencia' => null,
        ];
    }

    private function validDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
