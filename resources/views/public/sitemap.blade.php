<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($staticUrls as $url)
    <url>
        <loc>{{ $url }}</loc>
    </url>
@endforeach
@foreach ($listings as $listing)
    <url>
        <loc>{{ route('public.car-prices.show', $listing) }}</loc>
        <lastmod>{{ $listing->updated_at->toAtomString() }}</lastmod>
    </url>
@endforeach
@foreach ($posts as $post)
    <url>
        <loc>{{ route('public.blog.show', $post) }}</loc>
        <lastmod>{{ $post->updated_at->toAtomString() }}</lastmod>
    </url>
@endforeach
</urlset>
