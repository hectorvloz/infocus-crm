<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectTimerSessionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.disks.local.root' => storage_path('framework/testing/disks/local')]);
        Storage::disk('local')->put('proyectos.json', json_encode([
            [
                'id' => 'project-1',
                'titulo' => 'Desarrollo Web',
                'cliente' => 'Mohammed',
                'tareas' => [['id' => 'task-1', 'texto' => 'Mejoras Dixon']],
                'time_logs' => [],
            ],
        ]));

        $this->admin = User::factory()->create(['role' => 'admin', 'name' => 'Hector']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function timer(string $action, bool $close = false)
    {
        return $this->actingAs($this->admin)
            ->withSession(['user' => ['id' => $this->admin->id]])
            ->postJson(route('api.proyectos.timer'), [
                'id' => 'project-1',
                'action' => $action,
                'tarea_id' => 'task-1',
                'close' => $close,
            ])->assertOk();
    }

    private function active(): ?array
    {
        return $this->actingAs($this->admin)
            ->withSession(['user' => ['id' => $this->admin->id]])
            ->getJson(route('api.header.active-timer'))
            ->assertOk()
            ->json('item');
    }

    public function test_pausing_and_resuming_keeps_the_session_total_past_one_hour(): void
    {
        Carbon::setTestNow('2026-11-06 10:00:00');
        $this->timer('start');

        Carbon::setTestNow('2026-11-06 10:55:30');
        $this->timer('stop');

        $paused = $this->active();
        $this->assertFalse($paused['running']);
        $this->assertSame(3330, $paused['current_seconds']);

        Carbon::setTestNow('2026-11-06 11:10:00');
        $this->timer('start');

        Carbon::setTestNow('2026-11-06 11:15:00');
        $running = $this->active();
        $this->assertTrue($running['running']);
        $this->assertSame(3330 + 300, $running['current_seconds']);
    }

    public function test_saving_closes_the_session_so_the_next_one_starts_at_zero(): void
    {
        Carbon::setTestNow('2026-11-06 10:00:00');
        $this->timer('start');

        Carbon::setTestNow('2026-11-06 10:20:00');
        $this->timer('stop');
        $this->timer('stop', true);

        $this->assertNull($this->active());

        Carbon::setTestNow('2026-11-06 11:00:00');
        $this->timer('start');

        Carbon::setTestNow('2026-11-06 11:00:10');
        $this->assertSame(10, $this->active()['current_seconds']);
    }
}
