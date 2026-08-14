<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeSlide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HomeSlideController extends Controller
{
    public function index()
    {
        return view('admin.home-slides.index', [
            'pageTitle' => 'اسلایدر صفحه اصلی',
            'slides' => HomeSlide::orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'image' => ['required', 'image', 'max:8192'],
            'cta_label' => ['nullable', 'string', 'max:100'],
            'cta_url' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $path = $request->file('image')->store('home-slides', 'public');

        HomeSlide::create([
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'image_path' => $path,
            'cta_label' => $data['cta_label'] ?? null,
            'cta_url' => $data['cta_url'] ?? null,
            'sort_order' => $data['sort_order'] ?? ((int) HomeSlide::max('sort_order') + 1),
            'is_active' => true,
        ]);

        return back()->with('success', 'اسلاید اضافه شد.');
    }

    public function update(Request $request, HomeSlide $homeSlide)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'cta_label' => ['nullable', 'string', 'max:100'],
            'cta_url' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $homeSlide->update($data);

        return back()->with('success', 'اسلاید به‌روزرسانی شد.');
    }

    public function toggle(HomeSlide $homeSlide)
    {
        $homeSlide->update(['is_active' => ! $homeSlide->is_active]);

        return back()->with('success', 'وضعیت اسلاید تغییر کرد.');
    }

    public function destroy(HomeSlide $homeSlide)
    {
        Storage::disk('public')->delete($homeSlide->image_path);
        $homeSlide->delete();

        return back()->with('success', 'اسلاید حذف شد.');
    }
}
