<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use Illuminate\Http\Request;

class MessageTemplateController extends Controller
{
    public function index()
    {
        $templates = MessageTemplate::orderBy('category')->orderBy('id')->get();

        return view('admin.templates.index', [
            'pageTitle' => 'قالب‌های پیام (فقط مدیر)',
            'templates' => $templates,
            'categories' => MessageTemplate::CATEGORIES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:50'],
            'body' => ['required', 'string'],
        ]);

        if (! empty($data['id'])) {
            MessageTemplate::findOrFail($data['id'])->update([
                'title' => $data['title'], 'category' => $data['category'] ?? 'custom', 'body' => $data['body'],
            ]);
            $message = 'قالب به‌روزرسانی شد.';
        } else {
            MessageTemplate::create([
                'title' => $data['title'], 'category' => $data['category'] ?? 'custom',
                'body' => $data['body'], 'created_by' => $request->user()->id,
            ]);
            $message = 'قالب جدید ساخته شد.';
        }

        return back()->with('success', $message);
    }

    public function toggle(MessageTemplate $template)
    {
        $template->update(['is_active' => ! $template->is_active]);

        return back()->with('success', 'وضعیت قالب تغییر کرد.');
    }

    public function destroy(MessageTemplate $template)
    {
        $template->delete();

        return back()->with('success', 'قالب حذف شد.');
    }
}
