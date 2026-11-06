{{-- Editor de contratos (Quill) con barra de herramientas ordenada y tipografía de documento. --}}
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
    window.contractQuill = new Quill('#editor-container', {
        theme: 'snow',
        placeholder: 'Escribe el contrato o carga una plantilla…',
        modules: {
            toolbar: [
                [{ header: [1, 2, 3, false] }],
                ['bold', 'italic', 'underline'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                [{ indent: '-1' }, { indent: '+1' }],
                [{ align: [] }],
                ['clean'],
            ],
        },
    });

    document.getElementById('contractForm').addEventListener('submit', () => {
        document.getElementById('hiddenContent').value = window.contractQuill.root.innerHTML;
    });
</script>

<style>
    .contract-editor .ql-toolbar.ql-snow {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px 0;
        padding: 10px 16px;
        border: 0;
        border-bottom: 1px solid #eef2f7;
        background: #f8fafc;
        font-family: inherit;
    }
    .contract-editor .ql-toolbar.ql-snow .ql-formats {
        display: inline-flex;
        align-items: center;
        gap: 2px;
        margin: 0 10px 0 0;
        padding-right: 10px;
        border-right: 1px solid #e2e8f0;
    }
    .contract-editor .ql-toolbar.ql-snow .ql-formats:last-child { border-right: 0; margin-right: 0; padding-right: 0; }
    .contract-editor .ql-toolbar.ql-snow button,
    .contract-editor .ql-toolbar.ql-snow .ql-picker-label {
        width: 30px;
        height: 30px;
        padding: 6px;
        border-radius: 8px;
        border: 0;
    }
    .contract-editor .ql-toolbar.ql-snow button:hover,
    .contract-editor .ql-toolbar.ql-snow .ql-picker-label:hover,
    .contract-editor .ql-toolbar.ql-snow button.ql-active { background: #eef2f7; }
    .contract-editor .ql-snow .ql-stroke { stroke: #475569; }
    .contract-editor .ql-snow .ql-fill { fill: #475569; }
    .contract-editor .ql-snow.ql-toolbar button.ql-active .ql-stroke,
    .contract-editor .ql-snow.ql-toolbar button:hover .ql-stroke { stroke: #0f172a; }
    .contract-editor .ql-snow.ql-toolbar button.ql-active .ql-fill,
    .contract-editor .ql-snow.ql-toolbar button:hover .ql-fill { fill: #0f172a; }

    /* Selector de estilo de párrafo, en español */
    .contract-editor .ql-snow .ql-picker.ql-header { width: 128px; height: 30px; }
    .contract-editor .ql-snow .ql-picker.ql-header .ql-picker-label {
        width: 100%;
        padding: 0 26px 0 10px;
        display: flex;
        align-items: center;
        border: 1px solid #e2e8f0;
        background: #fff;
        font-size: 13px;
        font-weight: 600;
        color: #334155;
    }
    .contract-editor .ql-snow .ql-picker.ql-header .ql-picker-label svg { right: 6px; }
    .contract-editor .ql-snow .ql-picker.ql-header .ql-picker-label::before,
    .contract-editor .ql-snow .ql-picker.ql-header .ql-picker-item::before { content: 'Texto normal'; }
    .contract-editor .ql-snow .ql-picker.ql-header [data-value="1"]::before { content: 'Título' !important; }
    .contract-editor .ql-snow .ql-picker.ql-header [data-value="2"]::before { content: 'Cláusula' !important; }
    .contract-editor .ql-snow .ql-picker.ql-header [data-value="3"]::before { content: 'Subtítulo' !important; }
    .contract-editor .ql-snow .ql-picker-options {
        border: 1px solid #e2e8f0 !important;
        border-radius: 12px;
        padding: 6px;
        box-shadow: 0 12px 32px rgba(15, 23, 42, 0.12);
    }
    .contract-editor .ql-snow .ql-picker.ql-header .ql-picker-item { padding: 6px 8px; border-radius: 8px; }
    .contract-editor .ql-snow .ql-picker.ql-header .ql-picker-item:hover { background: #f1f5f9; }
    .contract-editor .ql-snow .ql-picker.ql-align .ql-picker-label { border: 0; }

    /* Hoja del documento */
    .contract-editor .ql-container.ql-snow { border: 0; font-family: inherit; font-size: 15px; }
    .contract-editor .ql-editor {
        min-height: 560px;
        max-width: 760px;
        margin: 0 auto;
        padding: 40px 48px 56px;
        color: #1e293b;
        line-height: 1.75;
    }
    .contract-editor .ql-editor.ql-blank::before { left: 48px; right: 48px; color: #94a3b8; font-style: normal; }
    .contract-editor .ql-editor h1 { font-size: 1.5rem; line-height: 1.25; font-weight: 800; text-align: center; margin: 0 0 1.5rem; color: #0f172a; }
    .contract-editor .ql-editor h2 { font-size: 0.95rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.02em; margin: 1.6rem 0 0.4rem; color: #0f172a; }
    .contract-editor .ql-editor h3 { font-size: 0.95rem; font-weight: 700; margin: 1.2rem 0 0.3rem; color: #0f172a; }
    .contract-editor .ql-editor p { margin: 0 0 0.6rem; text-align: justify; }
    .contract-editor .ql-editor ol, .contract-editor .ql-editor ul { margin: 0 0 0.75rem; }
    @media (max-width: 640px) {
        .contract-editor .ql-editor { padding: 24px 18px 32px; }
        .contract-editor .ql-editor.ql-blank::before { left: 18px; right: 18px; }
    }
</style>
