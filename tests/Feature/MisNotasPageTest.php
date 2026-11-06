<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MisNotasPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_ships_the_user_notes_so_it_does_not_wait_for_a_second_request(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create(['role' => 'admin']);
        Storage::disk('local')->put('clientes.json', json_encode([['id' => 'c1', 'empresa' => 'Cliente Uno']]));
        Storage::disk('local')->put('mis_notas.json', json_encode([
            ['id' => 'n1', 'ownerKey' => (string) $owner->id, 'title' => 'Mi nota', 'html' => '<p onclick="x()">Hola</p>', 'clientId' => 'c1'],
            ['id' => 'n2', 'ownerKey' => 'otra-persona', 'title' => 'Ajena', 'html' => '<p>No</p>'],
        ]));

        $html = $this->actingAs($owner)->withSession(['user' => ['id' => $owner->id]])
            ->get(route('mis-notas.index'))
            ->assertOk()
            ->assertViewHas('bootNotes', fn ($notes) => count($notes) === 1
                && $notes[0]['id'] === 'n1'
                && $notes[0]['clientId'] === 'c1'
                && !str_contains($notes[0]['html'], 'onclick'))
            ->getContent();

        $this->assertStringNotContainsString('Ajena', $html);
    }
}
