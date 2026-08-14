<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ route('public.car-prices.index') }}</loc>
    </url>
@foreach ($listings as $listing)
    <url>
        <loc>{{ route('public.car-prices.show', $listing) }}</loc>
        <lastmod>{{ $listing->updated_at->toAtomString() }}</lastmod>
    </url>
@endforeach
</urlset>
