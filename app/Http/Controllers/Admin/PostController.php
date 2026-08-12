<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\SocialPublisher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PostController extends Controller
{
    public function __construct(private readonly SocialPublisher $socialPublisher) {}

    public function index()
    {
        return view('admin.posts.index', [
            'pageTitle' => 'وبلاگ',
            'posts' => Post::latest()->paginate(20),
        ]);
    }

    public function create()
    {
        return view('admin.posts.create', [
            'pageTitle' => 'نوشتن مطلب جدید',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $post = Post::create([
            ...$data,
            'slug' => Post::slugify($data['title']),
            'status' => 'draft',
            'cover_image_path' => $this->storeCover($request),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.posts.edit', $post)->with('success', 'مطلب ایجاد شد.');
    }

    public function edit(Post $post)
    {
        $caption = $this->socialPublisher->buildCaption(
            title: $post->title,
            description: $post->excerpt,
            priceLine: null,
            url: route('public.blog.show', $post),
            hashtags: ['ناوراکار', 'وبلاگ_ناوراکار'],
        );

        return view('admin.posts.edit', [
            'pageTitle' => 'ویرایش مطلب: '.$post->title,
            'post' => $post,
            'socialHasImage' => (bool) $post->cover_image_path,
            'socialWhatsappUrl' => $this->socialPublisher->whatsAppShareUrl($caption),
        ]);
    }

    public function update(Request $request, Post $post)
    {
        $data = $this->validated($request);

        $cover = $this->storeCover($request);
        if ($cover) {
            if ($post->cover_image_path) {
                Storage::disk('public')->delete($post->cover_image_path);
            }
            $data['cover_image_path'] = $cover;
        }

        $post->update($data);

        return back()->with('success', 'تغییرات ذخیره شد.');
    }

    public function publish(Post $post)
    {
        $post->update(['status' => 'published', 'published_at' => $post->published_at ?? now()]);

        return back()->with('success', 'مطلب منتشر شد.');
    }

    public function unpublish(Post $post)
    {
        $post->update(['status' => 'draft']);

        return back()->with('success', 'انتشار مطلب لغو شد.');
    }

    public function publishSocial(Request $request, Post $post)
    {
        $data = $request->validate([
            'platform' => ['required', Rule::in(['telegram', 'bale'])],
        ]);

        if (! $post->cover_image_path) {
            return response()->json(['ok' => false, 'error' => 'این مطلب عکس کاور ندارد — ابتدا یک عکس اضافه کنید.'], 422);
        }

        $caption = $this->socialPublisher->buildCaption(
            title: $post->title,
            description: $post->excerpt,
            priceLine: null,
            url: route('public.blog.show', $post),
            hashtags: ['ناوراکار', 'وبلاگ_ناوراکار'],
        );

        $result = $data['platform'] === 'telegram'
            ? $this->socialPublisher->publishToTelegram($post->coverUrl(), $caption)
            : $this->socialPublisher->publishToBale($post->coverUrl(), $caption);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function destroy(Post $post)
    {
        if ($post->cover_image_path) {
            Storage::disk('public')->delete($post->cover_image_path);
        }
        $post->delete();

        return redirect()->route('admin.posts.index')->with('success', 'مطلب حذف شد.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:500'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'body' => ['required', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ]);
    }

    private function storeCover(Request $request): ?string
    {
        if (! $request->hasFile('cover_image')) {
            return null;
        }

        return $request->file('cover_image')->store('posts', 'public');
    }
}
