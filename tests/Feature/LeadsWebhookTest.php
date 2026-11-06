<?php

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\FileStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Mail\GenericMail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeadsWebhookTest extends TestCase
{
    use RefreshDatabase;

    private array $payload = [
        'nombre' => 'Laura Gómez',
        'email' => 'Laura@Ejemplo.com',
        'telefono' => '+57 300 123 4567',
        'marca' => 'Café Laurel',
        'tipo_web' => 'Tienda virtual',
        'origen' => 'Landing asesoría',
        'utm' => ['utm_source' => 'meta', 'utm_campaign' => 'asesoria'],
        'reunion' => [
            'inicio' => '2026-11-10T10:30:00-05:00',
            'duracion_min' => 30,
            'meet_link' => 'https://meet.google.com/abc-defg-hij',
            'google_event_id' => 'evt123',
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.disks.local.root' => storage_path('framework/testing/disks/local')]);
        config(['services.leads_webhook.token' => 'secreto-de-prueba']);
        foreach (['leads.json', 'settings.json', 'reuniones.json', 'clientes.json', 'users.json'] as $file) {
            Storage::disk('local')->put($file, '[]');
        }
    }

    private function send(array $payload, ?string $token = 'secreto-de-prueba')
    {
        $headers = $token ? ['Authorization' => 'Bearer ' . $token] : [];
        return $this->postJson('/webhooks/leads', $payload, $headers);
    }

    public function test_rejects_missing_or_wrong_token(): void
    {
        $this->send($this->payload, null)->assertStatus(401);
        $this->send($this->payload, 'otro')->assertStatus(401);
        $this->assertCount(0, (new FileStore('leads.json'))->all());
    }

    public function test_rejects_when_token_is_not_configured(): void
    {
        config(['services.leads_webhook.token' => null]);
        $this->send($this->payload, '')->assertStatus(401);
    }

    public function test_creates_lead_with_meeting_in_agenda(): void
    {
        $this->send($this->payload)->assertStatus(201)->assertJson(['ok' => true, 'nuevo' => true]);

        $leads = (new FileStore('leads.json'))->all();
        $this->assertCount(1, $leads);
        $lead = $leads[0];
        $this->assertSame('Laura Gómez · Café Laurel', $lead['nombre']);
        $this->assertSame('laura@ejemplo.com', $lead['email']);
        $this->assertSame('Posible cliente', $lead['etapa']);
        $this->assertSame('Landing asesoría', $lead['origen']);
        $this->assertStringContainsString('Busca: Tienda virtual', $lead['notas']);
        $this->assertStringContainsString('utm_source=meta', $lead['notas']);

        $item = $lead['agenda'][0];
        $this->assertSame('reunion_meet', $item['tipo']);
        $this->assertSame('evt123', $item['google_event_id']);
        $this->assertSame('https://meet.google.com/abc-defg-hij', $item['meet_link']);
        $this->assertSame('2026-11-10T15:30:00.000000Z', $item['fecha_hora']);
    }

    public function test_same_booking_twice_is_not_duplicated(): void
    {
        $this->send($this->payload)->assertStatus(201);
        $this->send($this->payload)->assertOk()->assertJson(['duplicado' => true]);

        $leads = (new FileStore('leads.json'))->all();
        $this->assertCount(1, $leads);
        $this->assertCount(1, $leads[0]['agenda']);
    }

    public function test_existing_lead_gets_new_meeting_instead_of_new_lead(): void
    {
        (new FileStore('leads.json'))->create(['id' => 'lead-1', 'nombre' => 'Laura', 'email' => 'laura@ejemplo.com', 'etapa' => 'Contactado', 'agenda' => []]);

        $this->send($this->payload)->assertOk()->assertJson(['nuevo' => false, 'lead_id' => 'lead-1']);

        $lead = (new FileStore('leads.json'))->find('lead-1');
        $this->assertSame('Contactado', $lead['etapa']);
        $this->assertSame('+57 300 123 4567', $lead['telefono']);
        $this->assertCount(1, $lead['agenda']);
        $this->assertCount(1, $lead['notas_lista']);
    }

    public function test_validates_payload(): void
    {
        $this->send(['nombre' => '', 'email' => 'no-es-correo'])->assertStatus(422);
        $this->send(array_merge($this->payload, ['reunion' => ['meet_link' => 'x']]))->assertStatus(422);
    }

    public function test_meetings_page_does_not_duplicate_the_google_event_of_a_lead(): void
    {
        $this->send($this->payload)->assertStatus(201);
        (new FileStore('settings.json'))->create([
            'id' => 'settings',
            'google_calendar_enabled' => true,
            'google_calendar_access_token' => 'tok',
            'google_calendar_expires_at' => now()->addHour()->toISOString(),
        ]);
        Http::fake(['www.googleapis.com/*' => Http::response(['items' => [
            ['id' => 'evt123', 'summary' => 'Asesoría web gratis · Café Laurel', 'start' => ['dateTime' => '2026-11-10T10:30:00-05:00'], 'end' => ['dateTime' => '2026-11-10T11:00:00-05:00']],
            ['id' => 'otro', 'summary' => 'Evento personal', 'start' => ['dateTime' => '2026-11-10T15:00:00-05:00'], 'end' => ['dateTime' => '2026-11-10T16:00:00-05:00']],
        ]])]);

        $admin = User::factory()->create(['role' => 'admin']);
        $response = $this->actingAs($admin)->withSession(['user' => ['id' => $admin->id]])->get('/reuniones?week=2026-11-10');

        $response->assertOk();
        $ids = collect($response->viewData('weekMeetings'))->flatten(1)->pluck('id');
        $this->assertTrue($ids->contains(fn ($id) => str_starts_with($id, 'lead:')));
        $this->assertFalse($ids->contains('google:evt123'));
        $this->assertTrue($ids->contains('google:otro'));
    }

    public function test_team_gets_an_email_for_each_lead(): void
    {
        Mail::fake();
        config(['services.leads_webhook.notify' => 'proyecto@estudioindigo.test']);
        $this->send($this->payload)->assertStatus(201);

        Mail::assertSent(GenericMail::class, fn ($m) => $m->hasTo('proyecto@estudioindigo.test') && str_contains($m->subjectLine, 'Nuevo lead: Laura Gómez · Café Laurel'));
    }

    public function test_no_email_when_notify_is_not_configured(): void
    {
        Mail::fake();
        config(['services.leads_webhook.notify' => null]);
        $this->send($this->payload)->assertStatus(201);

        Mail::assertNothingSent();
    }
}
