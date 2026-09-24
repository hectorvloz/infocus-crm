<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

class SafeRichText
{
    private const ALLOWED_TAGS = [
        'p', 'div', 'br', 'h1', 'h2', 'h3', 'strong', 'b', 'em', 'i', 'u', 's',
        'strike', 'mark', 'ul', 'ol', 'li', 'blockquote', 'pre', 'code', 'hr',
        'span', 'a', 'img', 'input',
    ];

    private const DROP_WITH_CONTENT = [
        'script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'form',
        'meta', 'link', 'base', 'template', 'noscript',
    ];

    public static function sanitize(string $html): string
    {
        if ($html === '') {
            return '';
        }

        if (!class_exists(DOMDocument::class)) {
            return self::fallback($html);
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<?xml encoding="utf-8" ?><div id="safe-rich-text-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            return '';
        }

        $root = $document->getElementById('safe-rich-text-root');
        if (!$root) {
            return '';
        }

        self::cleanChildren($root);

        $clean = '';
        foreach ($root->childNodes as $child) {
            $clean .= $document->saveHTML($child);
        }

        return trim($clean);
    }

    private static function cleanChildren(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($node->tagName);
            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $node->parentNode?->removeChild($node);
                continue;
            }

            if (!in_array($tag, self::ALLOWED_TAGS, true)) {
                self::cleanChildren($node);
                while ($node->firstChild) {
                    $node->parentNode?->insertBefore($node->firstChild, $node);
                }
                $node->parentNode?->removeChild($node);
                continue;
            }

            self::cleanElement($node, $tag);
            self::cleanChildren($node);
        }
    }

    private static function cleanElement(DOMElement $element, string $tag): void
    {
        $attributes = [];
        foreach ($element->attributes as $attribute) {
            $attributes[] = $attribute->name;
        }

        foreach ($attributes as $name) {
            $lower = strtolower($name);
            $keep = in_array($lower, ['class', 'style'], true)
                || in_array($lower, ['data-placeholder', 'data-note-number', 'data-note-scale'], true)
                || ($tag === 'a' && in_array($lower, ['href', 'target', 'rel'], true))
                || ($tag === 'img' && in_array($lower, ['src', 'alt', 'loading'], true))
                || ($tag === 'input' && in_array($lower, ['type', 'checked', 'contenteditable'], true));

            if (!$keep) {
                $element->removeAttribute($name);
            }
        }

        self::cleanClass($element);
        self::cleanStyle($element, $tag);

        if ($tag === 'a') {
            $href = trim($element->getAttribute('href'));
            if (!self::safeHref($href)) {
                $element->removeAttribute('href');
                $element->removeAttribute('target');
                $element->removeAttribute('rel');
            } else {
                $element->setAttribute('target', '_blank');
                $element->setAttribute('rel', 'noopener noreferrer');
            }
        }

        if ($tag === 'img') {
            $src = trim($element->getAttribute('src'));
            if (!self::safeImageSource($src)) {
                $element->parentNode?->removeChild($element);
                return;
            }
            $element->setAttribute('loading', 'lazy');
        }

        if ($tag === 'input') {
            if (strtolower($element->getAttribute('type')) !== 'checkbox') {
                $element->parentNode?->removeChild($element);
                return;
            }
            $element->setAttribute('type', 'checkbox');
            $element->setAttribute('contenteditable', 'false');
        }
    }

    private static function cleanClass(DOMElement $element): void
    {
        $classes = preg_split('/\s+/', trim($element->getAttribute('class'))) ?: [];
        $classes = array_values(array_filter($classes, fn (string $class) =>
            preg_match('/^(note-[a-z0-9_-]+|is-empty|is-checked)$/i', $class) === 1
        ));

        $classes ? $element->setAttribute('class', implode(' ', $classes)) : $element->removeAttribute('class');
    }

    private static function cleanStyle(DOMElement $element, string $tag): void
    {
        $safe = [];
        foreach (explode(';', $element->getAttribute('style')) as $declaration) {
            [$property, $value] = array_pad(explode(':', $declaration, 2), 2, '');
            $property = strtolower(trim($property));
            $value = trim($value);
            if ($property === 'background-color' && preg_match('/^(#[0-9a-f]{3,8}|rgba?\([0-9.,%\s]+\)|[a-z]{3,20})$/i', $value)) {
                $safe[] = $property.': '.$value;
            }
            if ($tag === 'img' && $property === 'width' && preg_match('/^\d+(?:\.\d+)?%$/', $value)) {
                $safe[] = 'width: '.$value;
            }
            if ($tag === 'img' && $property === 'max-width' && $value === '100%') {
                $safe[] = 'max-width: 100%';
            }
        }

        $safe ? $element->setAttribute('style', implode('; ', $safe)) : $element->removeAttribute('style');
    }

    private static function safeHref(string $href): bool
    {
        return $href !== '' && (
            preg_match('#^https?://#i', $href) === 1
            || preg_match('#^(mailto|tel):#i', $href) === 1
            || preg_match('#^/(?!/)#', $href) === 1
            || str_starts_with($href, '#')
        );
    }

    private static function safeImageSource(string $src): bool
    {
        return preg_match('#^data:image/(png|jpeg|webp|gif);base64,[a-z0-9+/=\r\n]+$#i', $src) === 1
            || preg_match('#^/(?!/)[a-z0-9/_?&=%.-]+$#i', $src) === 1;
    }

    private static function fallback(string $html): string
    {
        $html = preg_replace('#<(script|style|iframe|object|embed|svg|math|form|template)[^>]*>.*?</\1\s*>#is', '', $html) ?? '';
        $html = strip_tags($html, '<p><div><br><h1><h2><h3><strong><b><em><i><u><s><strike><mark><ul><ol><li><blockquote><pre><code><hr><span><a>');
        return preg_replace('/\s(?:on\w+|style|srcdoc)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';
    }
}
