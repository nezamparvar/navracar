<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\HtmlSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function index()
    {
        $posts = Post::published()->latest('published_at')->paginate(9);

        return view('public.blog.index', [
            'title' => 'وبلاگ ناوراکار',
            'posts' => $posts,
        ]);
    }

    public function show(Request $request, Post $post, HtmlSanitizer $sanitizer)
    {
        if ($post->status !== 'published' && ! $request->user()) {
            abort(404);
        }

        $safeBody = $sanitizer->clean($post->body);

        return view('public.blog.show', [
            'title' => $post->meta_title ?: ($post->title.' | ناوراکار'),
            'post' => $post,
            'safeBody' => $safeBody,
            'metaDescription' => $post->meta_description ?: Str::limit(strip_tags($post->excerpt ?: $safeBody), 160),
        ]);
    }
}
