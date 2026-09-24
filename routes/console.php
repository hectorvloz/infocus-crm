<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ai:prune-chat-images', function () {
    $this->info((new \App\Support\Ai\AiChatImageStore())->pruneExpired() . ' imágenes temporales eliminadas.');
})->purpose('Eliminar imágenes caducadas de los chats de IA');

Artisan::command('documents:secure-storage', function () {
    $result = \App\Support\DocumentStorage::migrateRegisteredDocuments();
    $this->info($result['migrated'].' documentos migrados a almacenamiento privado.');
    if ($result['failed'] > 0) {
        $this->warn($result['failed'].' documentos no pudieron migrarse.');
    }
    return $result['failed'] > 0 ? 1 : 0;
})->purpose('Mover adjuntos antiguos desde almacenamiento público a privado');

Schedule::command('crm:run-scheduled')->everyMinute()->withoutOverlapping();
Schedule::command('notifications:push')->everyMinute()->withoutOverlapping();
Schedule::command('social:publish-scheduled')->everyMinute()->withoutOverlapping();
Schedule::command('mail:weekly-hours-summary')->weeklyOn(1, '08:20');
Schedule::command('mail:monthly-hours-summary')->monthlyOn(1, '08:30');
Schedule::command('system:send-critical-alerts')->dailyAt('08:40');
Schedule::command('ai:prune-chat-images')->dailyAt('03:10')->withoutOverlapping();
Schedule::command('documents:secure-storage')->hourly()->withoutOverlapping();
