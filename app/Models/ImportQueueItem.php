<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportQueueItem extends Model
{
    protected $table = 'import_queue';

    protected $fillable = ['user_id', 'source', 'source_platform', 'capture_method', 'source_url', 'status', 'payload_json', 'parsed_json', 'warnings_json', 'confidence', 'error'];

    protected $casts = ['payload_json' => 'array', 'parsed_json' => 'array', 'warnings_json' => 'array', 'confidence' => 'float'];

    public const STATUSES = ['pending', 'captured', 'parsed', 'needs_review', 'image_importing', 'ready', 'failed', 'published'];
}

