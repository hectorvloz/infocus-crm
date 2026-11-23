<?php

namespace Tests\Feature;

use App\Support\RichTextFormat;
use Tests\TestCase;

class RichTextFormatTest extends TestCase
{
    public function test_markdown_becomes_editor_html(): void
    {
        $html = RichTextFormat::toHtml(implode("\n", [
            '## Video 1 · El espadero',
            'Planos **reales** con *IA* y ==riesgo== ~~viejo~~.',
            '',
            '- Referencia: [búsqueda](https://www.tiktok.com/search?q=Bulin%2047%20IA)',
            '- Audio libre',
            '',
            '1. Grabar',
            '2. Editar',
            '',
            '- [ ] Guion',
            '- [x] Taller',
            '> Cita',
            '---',
            'Ver https://example.com/a.',
        ]));

        $this->assertStringContainsString('<h2>Video 1 · El espadero</h2>', $html);
        $this->assertStringContainsString('<strong>reales</strong>', $html);
        $this->assertStringContainsString('<em>IA</em>', $html);
        $this->assertStringContainsString('<span style="background-color: #fef08a">riesgo</span>', $html);
        $this->assertStringContainsString('<s>viejo</s>', $html);
        $this->assertStringContainsString('<ul><li>Referencia: <a href="https://www.tiktok.com/search?q=Bulin%2047%20IA" target="_blank" rel="noopener noreferrer">búsqueda</a></li><li>Audio libre</li></ul>', $html);
        $this->assertStringContainsString('<ol><li>Grabar</li><li>Editar</li></ol>', $html);
        $this->assertStringContainsString('<div class="note-checkline"><input type="checkbox" class="note-checkbox" contenteditable="false"><span>Guion</span></div>', $html);
        $this->assertStringContainsString('class="note-checkline is-checked"', $html);
        $this->assertStringContainsString('<blockquote>Cita</blockquote>', $html);
        $this->assertStringContainsString('<hr>', $html);
        $this->assertStringContainsString('<a href="https://example.com/a" target="_blank" rel="noopener noreferrer">https://example.com/a</a>.', $html);
    }

    public function test_html_is_sanitized_and_text_is_escaped(): void
    {
        $this->assertSame('<p>Hola</p>', RichTextFormat::toHtml('<p>Hola</p><script>alert(1)</script>'));
        $this->assertSame('<p>a &lt; b y snake_case_name</p>', RichTextFormat::toHtml('a < b y snake_case_name'));
        $this->assertSame('', RichTextFormat::toHtml("  \n "));
        $this->assertSame('<p>línea 1<br>línea 2</p><p>otro párrafo</p>', RichTextFormat::toHtml("línea 1\nlínea 2\n\notro párrafo"));
    }
}
