<?php

namespace App\Support\WhatsApp;

/**
 * Lee lo que manda el webhook de TextMeBot. Su documentación solo muestra los campos
 * de un mensaje de texto privado, así que lo de grupos y mensajes citados se busca en
 * los nombres de campo más comunes de las librerías de WhatsApp Web, y el registro
 * guarda los nombres de los campos que llegan para ajustarlo con mensajes reales.
 */
class TextMeBotPayload
{
    /** Llamar al bot en un grupo: "@infocus …", "infocus: …" o mencionando su número. */
    public const TRIGGER = '/^\s*(?:@infocus\b|infocus\s*[:,]|@\+?\d{7,15}\b)\s*/iu';

    private const GROUP_ID_FIELDS = ['from', 'to', 'chat', 'chat_id', 'chatId', 'group', 'group_id', 'groupId', 'remoteJid', 'remote_jid', 'origin'];

    private const SENDER_FIELDS = ['from', 'from_lid', 'participant', 'participant_lid', 'author', 'sender', 'sender_lid', 'from_number', 'phone'];

    private const QUOTED_FIELDS = ['quoted', 'quoted_message', 'quotedMessage', 'quoted_msg', 'quotedMsg', 'quoted_text', 'quotedText', 'reply_to', 'replyTo', 'reply', 'context'];

    public function __construct(private readonly array $data)
    {
    }

    public function isGroup(): bool
    {
        if (filter_var($this->data['is_group'] ?? $this->data['isGroup'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }
        foreach (self::GROUP_ID_FIELDS as $field) {
            $value = $this->data[$field] ?? null;
            if (is_string($value) && (str_ends_with(strtolower(trim($value)), '@g.us') || strtolower(trim($value)) === 'group')) {
                return true;
            }
        }

        return !empty($this->data['group_name']) || !empty($this->data['groupName']);
    }

    /** Identificadores con los que puede venir quien escribió (en grupos, el autor y no el grupo). */
    public function senders(): array
    {
        $ids = [];
        foreach (self::SENDER_FIELDS as $field) {
            $value = $this->data[$field] ?? null;
            if (is_string($value) && trim($value) !== '' && !str_ends_with(strtolower($value), '@g.us')) {
                $ids[] = trim($value);
            }
        }

        return array_values(array_unique($ids));
    }

    /** Texto del mensaje al que se respondió (citado), si TextMeBot lo incluye. */
    public function quotedText(): string
    {
        foreach (self::QUOTED_FIELDS as $field) {
            $text = self::textFrom($this->data[$field] ?? null);
            if ($text !== '') {
                return $text;
            }
        }

        return '';
    }

    public static function startsWithTrigger(string $text): bool
    {
        return (bool) preg_match(self::TRIGGER, $text);
    }

    public static function withoutTrigger(string $text): string
    {
        return trim((string) preg_replace(self::TRIGGER, '', $text, 1));
    }

    /** Nombres de los campos que llegaron (sin valores), para el registro. */
    public function fieldPaths(): string
    {
        $paths = [];
        $walk = function (array $data, string $prefix, int $depth) use (&$walk, &$paths) {
            foreach ($data as $key => $value) {
                $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;
                if (is_array($value) && $depth < 2) {
                    $walk($value, $path, $depth + 1);
                } else {
                    $paths[] = $path;
                }
            }
        };
        $walk($this->data, '', 0);

        return implode(', ', array_slice($paths, 0, 40));
    }

    private static function textFrom(mixed $value): string
    {
        if (is_string($value)) {
            $value = trim($value);

            return in_array(strtolower($value), ['', 'null', 'false'], true) ? '' : $value;
        }
        if (is_array($value)) {
            foreach (['message', 'text', 'body', 'conversation', 'caption', 'content', 'quoted_message', 'quotedMessage'] as $key) {
                $text = self::textFrom($value[$key] ?? null);
                if ($text !== '') {
                    return $text;
                }
            }
        }

        return '';
    }
}
