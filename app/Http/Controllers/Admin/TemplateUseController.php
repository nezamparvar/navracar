<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeadActivity;
use App\Models\MessageTemplate;
use App\Models\QuoteRequest;
use Illuminate\Http\Request;

class TemplateUseController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'leadId' => ['required', 'integer'],
            'templateId' => ['required', 'integer'],
        ]);

        $lead = QuoteRequest::find($data['leadId']);
        if (! $lead) {
            return response()->json(['success' => false]);
        }
        if (! $request->user()->isAdmin() && $lead->assigned_to !== $request->user()->id) {
            return response()->json(['success' => false], 403);
        }

        $template = MessageTemplate::find($data['templateId']);
        $title = $template->title ?? ('#'.$data['templateId']);

        LeadActivity::create([
            'request_id' => $data['leadId'],
            'admin_user_id' => $request->user()->id,
            'activity_type' => 'note',
            'note' => 'قالب پیام «'.$title.'» کپی شد',
        ]);

        return response()->json(['success' => true]);
    }
}
