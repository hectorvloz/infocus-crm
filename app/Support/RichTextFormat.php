<?php

namespace App\Support;

/**
 * Convierte el formato tipo Markdown que escriben los agentes (MCP) al HTML del
 * editor de Infocus: títulos, negrita, cursiva, tachado, resaltado, listas,
 * casillas, citas, separadores y enlaces. Si ya llega HTML, solo lo sanea.
 */
class RichTextFormat
{
    public const HIGHLIGHT = '#fef08a';

    public static function toHtml(?string $text): string
    {
        $text = (string) $text;
        if (trim($text) === '') {
            return '';
        }
        if ($text !== strip_tags($text)) {
            return SafeRichText::sanitize($text);
        }

        $html = [];
        $paragraph = [];
        $list = null; // ['ul'|'ol', items[]]

        $flushParagraph = function () use (&$paragraph, &$html) {
            if ($paragraph) {
                $html[] = '<p>' . implode('<br>', $paragraph) . '</p>';
                $paragraph = [];
            }
        };
        $flushList = function () use (&$list, &$html) {
            if ($list) {
                $html[] = "<{$list[0]}>" . implode('', array_map(fn ($i) => "<li>{$i}</li>", $list[1])) . "</{$list[0]}>";
                $list = null;
            }
        };
        $addItem = function (string $type, string $item) use (&$list, $flushList) {
            if (!$list || $list[0] !== $type) {
                $flushList();
                $list = [$type, []];
            }
            $list[1][] = self::inline($item);
        };

        foreach (preg_split('/\R/', $text) as $raw) {
            $line = rtrim($raw);
            $clean = trim($line);

            if ($clean === '') {
                $flushParagraph();
                $flushList();
                continue;
            }
            if (preg_match('/^(-{3,}|\*{3,}|_{3,})$/', $clean)) {
                $flushParagraph();
                $flushList();
                $html[] = '<hr>';
                continue;
            }
            if (preg_match('/^(#{1,3})\s+(.+)$/', $clean, $m)) {
                $flushParagraph();
                $flushList();
                $n = strlen($m[1]);
                $html[] = "<h{$n}>" . self::inline(rtrim($m[2], " #")) . "</h{$n}>";
                continue;
            }
            if (preg_match('/^(?:[-*•]\s+)?\[( |x|X)?\]\s+(.+)$/', $clean, $m)) {
                $flushParagraph();
                $flushList();
                $checked = strtolower((string) ($m[1] ?? '')) === 'x';
                $html[] = '<div class="note-checkline' . ($checked ? ' is-checked' : '') . '"><input type="checkbox" class="note-checkbox" contenteditable="false"'
                    . ($checked ? ' checked' : '') . '><span>' . self::inline($m[2]) . '</span></div>';
                continue;
            }
            if (preg_match('/^[-*•]\s+(.+)$/', $clean, $m)) {
                $flushParagraph();
                $addItem('ul', $m[1]);
                continue;
            }
            if (preg_match('/^\d+[.)]\s+(.+)$/', $clean, $m)) {
                $flushParagraph();
                $addItem('ol', $m[1]);
                continue;
            }
            if (preg_match('/^>\s?(.*)$/', $clean, $m)) {
                $flushParagraph();
                $flushList();
                $html[] = '<blockquote>' . self::inline($m[1]) . '</blockquote>';
                continue;
            }
            $flushList();
            $paragraph[] = self::inline($clean);
        }
        $flushParagraph();
        $flushList();

        return SafeRichText::sanitize(implode('', $html));
    }

    private static function inline(string $text): string
    {
        $links = [];
        // [texto](url) y URLs sueltas se guardan aparte para no tocarlas con el resto del formato.
        $text = preg_replace_callback('/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/', function ($m) use (&$links) {
            $links[] = '<a href="' . e($m[2]) . '">' . e($m[1]) . '</a>';

            return "\x00" . (count($links) - 1) . "\x00";
        }, $text);
        $text = preg_replace_callback('/https?:\/\/[^\s<>()]+[^\s<>().,;:!?]/', function ($m) use (&$links) {
            $links[] = '<a href="' . e($m[0]) . '">' . e($m[0]) . '</a>';

            return "\x00" . (count($links) - 1) . "\x00";
        }, $text);

        $text = e($text);
        $text = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $text);
        $text = preg_replace('/__(.+?)__/u', '<strong>$1</strong>', $text);
        $text = preg_replace('/==(.+?)==/u', '<span style="background-color: ' . self::HIGHLIGHT . '">$1</span>', $text);
        $text = preg_replace('/~~(.+?)~~/u', '<s>$1</s>', $text);
        $text = preg_replace('/(?<![\w*])\*(?!\s)(.+?)(?<!\s)\*(?![\w*])/u', '<em>$1</em>', $text);
        $text = preg_replace('/(?<![\w_])_(?!\s)(.+?)(?<!\s)_(?![\w_])/u', '<em>$1</em>', $text);

        return preg_replace_callback("/\x00(\d+)\x00/", fn ($m) => $links[(int) $m[1]] ?? '', $text);
    }
}
