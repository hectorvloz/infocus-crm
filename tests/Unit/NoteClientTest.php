<?php

namespace Tests\Unit;

use App\Support\Ai\NoteClient;
use App\Http\Controllers\MisNotasController;
use ReflectionMethod;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NoteClientTest extends TestCase
{
    public function test_resolves_unique_legacy_name_but_never_guesses_ambiguous_names(): void
    {
        Storage::fake('local');
        Storage::put('clientes.json', json_encode([
            ['id' => 'c1', 'empresa' => 'Dproperty'],
            ['id' => 'c2', 'empresa' => 'Acme'],
            ['id' => 'c3', 'empresa' => 'Acme'],
        ]));

        $resolver = new NoteClient();
        $this->assertSame('c1', $resolver->resolve(['linkedClient' => 'Dproperty'])['id']);
        $this->assertNull($resolver->resolve(['linkedClient' => 'Acme']));
        $this->assertSame('c2', $resolver->resolve(['clientId' => 'c2', 'linkedClient' => 'Acme'])['id']);
        $this->assertNull($resolver->resolve(['clientId' => 'missing', 'linkedClient' => 'Dproperty']));
        $this->assertFalse($resolver->belongsTo(['linkedClient' => 'Acme'], 'c2'));

        $method = new ReflectionMethod(new MisNotasController(), 'clientIdFor');
        $this->assertSame('c2', $method->invoke(new MisNotasController(), ['linkedClient' => 'Acme', 'clientId' => 'c2'], []));
    }
}
