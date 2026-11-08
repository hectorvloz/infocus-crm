<?php

namespace App\Http\Controllers;

use App\Support\Ai\AiAttachments;
use Illuminate\Support\Facades\Storage;

/**
 * Sirve un adjunto del asistente por un enlace firmado que vence en minutos.
 * Lo usan Twilio, Meta y TextMeBot para descargar el archivo que el asistente
 * le manda al dueño por WhatsApp. Sin firma válida o vencida, 403.
 */
class AiAttachmentDownloadController extends Controller
{
    public function __invoke(string $id, string $name)
    {
        $item = (new AiAttachments())->findForSignedDownload($id);
        abort_if(!$item, 404);

        return response()->file(Storage::disk('local')->path((string) $item['path']), [
            'Content-Type' => (string) $item['mime'],
            'Content-Disposition' => 'inline; filename="' . addcslashes((string) $item['name'], '"\\') . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
