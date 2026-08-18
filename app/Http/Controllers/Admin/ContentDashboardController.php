<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CarListing;
use App\Models\HomeSlide;
use App\Models\ImportQueueItem;
use App\Models\Post;
use Illuminate\Http\Request;

class ContentDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $needsReviewStatuses = ['pending', 'captured', 'parsed', 'needs_review', 'image_importing'];

        return view('admin.content-dashboard', [
            'pageTitle' => 'داشبورد محتوا',
            'pageSubtitle' => 'نمای کلی از آگهی‌های خودرو، مقالات و اسلایدهای صفحه اصلی.',
            'publishedListings' => CarListing::where('status', 'published')->count(),
            'draftListings' => CarListing::where('status', 'draft')->count(),
            'needsReviewImports' => ImportQueueItem::whereIn('status', $needsReviewStatuses)->count(),
            'failedImports' => ImportQueueItem::where('status', 'failed')->count(),
            'publishedPosts' => Post::where('status', 'published')->count(),
            'draftPosts' => Post::where('status', 'draft')->count(),
            'activeSlides' => HomeSlide::where('is_active', true)->count(),
            'recentListings' => CarListing::latest('created_at')->limit(5)->get(['id', 'slug', 'title_fa', 'status', 'created_at']),
            'recentPosts' => Post::latest('created_at')->limit(5)->get(['id', 'title', 'status', 'created_at']),
        ]);
    }
}
