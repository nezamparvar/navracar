@props(['items'])

<script type="application/ld+json">
{!! json_encode([
    '@'.'context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => collect($items)->values()->map(fn ($item, $i) => [
        '@type' => 'ListItem',
        'position' => $i + 1,
        'name' => $item['label'],
        'item' => $item['url'],
    ])->all(),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
