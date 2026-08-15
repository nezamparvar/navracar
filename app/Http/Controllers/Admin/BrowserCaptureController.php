<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImportQueueItem;
use App\Services\Capture\BrowserCaptureSource;
use App\Services\Capture\DubizzleImportService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class BrowserCaptureController extends Controller
{
    public function __invoke(Request $request, DubizzleImportService $imports): mixed
    {
        $data = $request->validate([
            'url' => ['required', 'url', 'max:1000'],
            'html' => ['nullable', 'string', 'max:5242880'],
            'structured' => ['nullable', 'array'],
        ]);
        if ($request->hasAny(['cookies', 'headers', 'authorization', 'session'])) {
            throw ValidationException::withMessages(['capture' => 'Browser credentials are never accepted.']);
        }

        $result = $imports->import(new BrowserCaptureSource, $data['url'], $data);
        ImportQueueItem::create([
            'user_id' => $request->user()->id,
            'source' => 'browser',
            'source_url' => $data['url'],
            'status' => $result['status'] === 'parsed' ? 'needs_review' : 'failed',
            'payload_json' => ['structured' => $data['structured'] ?? null],
            'parsed_json' => $result['data'],
            'warnings_json' => $result['warnings'],
            'confidence' => $result['status'] === 'parsed' ? 0.5 : null,
            'error' => $result['status'] === 'parsed' ? null : implode(' ', $result['warnings']),
        ]);
        return response()->json($result, $result['status'] === 'parsed' ? 200 : 422);
    }
}

