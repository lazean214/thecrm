@php
    use League\CommonMark\Environment\Environment;
    use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
    use League\CommonMark\Extension\Table\TableExtension;
    use League\CommonMark\MarkdownConverter;

    $environment = new Environment();
    $environment->addExtension(new CommonMarkCoreExtension());
    $environment->addExtension(new TableExtension());
    $converter = new MarkdownConverter($environment);

    $markdown = file_get_contents(base_path('docs/User Guide.md'));
    $result = $converter->convert($markdown);
    $content = $result->getContent();
@endphp

<x-layouts::app :title="__('User Guide')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl overflow-auto">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800/50">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">CRM User Guide</h1>
                <a href="{{ route('dashboard') }}"
                   class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm transition-colors hover:bg-zinc-100 dark:border-zinc-600 dark:bg-zinc-700 dark:hover:bg-zinc-600">
                    ← Back to Dashboard
                </a>
            </div>
            <div class="guide-content text-sm text-zinc-700 dark:text-zinc-300">
                {!! $content !!}
            </div>
        </div>
    </div>
</x-layouts::app>

<style>
    .guide-content h1 {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--pc);
        margin-top: 2rem;
        margin-bottom: 1rem;
    }
    .guide-content h1:first-child {
        margin-top: 0;
    }
    .guide-content h2 {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--pc);
        margin-top: 1.5rem;
        margin-bottom: 0.75rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid var(--pc);
    }
    .guide-content h3 {
        font-size: 1.125rem;
        font-weight: 500;
        margin-top: 1rem;
        margin-bottom: 0.5rem;
    }
    .guide-content h4 {
        font-size: 1rem;
        font-weight: 500;
        margin-top: 0.75rem;
        margin-bottom: 0.5rem;
    }
    .guide-content p {
        margin: 0.75rem 0;
        line-height: 1.625;
    }
    .guide-content ul, .guide-content ol {
        margin: 0.75rem 0;
        padding-left: 1.5rem;
    }
    .guide-content ul {
        list-style-type: disc;
    }
    .guide-content ol {
        list-style-type: decimal;
    }
    .guide-content li {
        margin: 0.25rem 0;
    }
    .guide-content li > ul, .guide-content li > ol {
        margin-top: 0.25rem;
        margin-bottom: 0;
    }
    .guide-content a {
        color: #3b82f6;
        text-decoration: underline;
    }
    .guide-content a:hover {
        color: #2563eb;
    }
    .guide-content strong {
        font-weight: 600;
    }
    .guide-content em {
        font-style: italic;
    }
    .guide-content code {
        padding: 0.125rem 0.375rem;
        border-radius: 0.25rem;
        background-color: rgba(0,0,0,0.05);
        font-size: 0.875em;
        font-family: ui-monospace, monospace;
    }
    .guide-content pre {
        margin: 1rem 0;
        padding: 1rem;
        border-radius: 0.5rem;
        background-color: #18181b;
        color: #fafafa;
        overflow-x: auto;
    }
    .guide-content pre code {
        padding: 0;
        background: transparent;
    }
    .guide-content blockquote {
        margin: 1rem 0;
        padding-left: 1rem;
        border-left: 4px solid #d4d4d4;
        font-style: italic;
        color: #737373;
    }
    .guide-content hr {
        margin: 2rem 0;
        border: none;
        border-top: 1px solid #e5e7eb;
    }
    .guide-content table {
        width: 100%;
        margin: 1rem 0;
        border-collapse: collapse;
        font-size: 0.875rem;
    }
    .guide-content thead {
        background-color: #f9fafb;
    }
    .guide-content th {
        padding: 0.5rem 1rem;
        text-align: left;
        font-weight: 600;
        border: 1px solid #e5e7eb;
    }
    .guide-content td {
        padding: 0.5rem 1rem;
        border: 1px solid #e5e7eb;
    }
    .guide-content tbody tr:hover {
        background-color: #f9fafb;
    }
    .guide-content img {
        max-width: 100%;
        height: auto;
        border-radius: 0.5rem;
        margin: 1rem 0;
    }
</style>
