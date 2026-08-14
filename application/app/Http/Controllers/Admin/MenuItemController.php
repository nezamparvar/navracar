<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use Illuminate\Http\Request;

class MenuItemController extends Controller
{
    public function index()
    {
        return view('admin.menu-items.index', [
            'pageTitle' => 'منوی سایت',
            'items' => MenuItem::orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer'],
            'opens_new_tab' => ['nullable', 'boolean'],
        ]);

        MenuItem::create([
            'label' => $data['label'],
            'url' => $data['url'],
            'sort_order' => $data['sort_order'] ?? ((int) MenuItem::max('sort_order') + 1),
            'opens_new_tab' => (bool) ($data['opens_new_tab'] ?? false),
            'is_active' => true,
        ]);

        return back()->with('success', 'آیتم منو اضافه شد.');
    }

    public function toggle(MenuItem $menuItem)
    {
        $menuItem->update(['is_active' => ! $menuItem->is_active]);

        return back()->with('success', 'وضعیت آیتم منو تغییر کرد.');
    }

    public function destroy(MenuItem $menuItem)
    {
        $menuItem->delete();

        return back()->with('success', 'آیتم منو حذف شد.');
    }
}
