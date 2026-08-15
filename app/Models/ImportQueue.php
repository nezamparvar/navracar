<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportQueue extends Model
{
    protected $fillable = [
        'source',
        'source_listing_id',
        'source_url',
        'source_method',
        'status',
        'car_listing_id',
        'captured_data',
        'parsed_data',
        'diagnostics',
        'warnings',
        'error_message',
        'parse_confidence',
        'image_count',
        'images_imported',
        'canonical_url',
        'duplicate_detected_with',
        'notes',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'captured_data' => 'array',
            'parsed_data' => 'array',
            'diagnostics' => 'array',
            'warnings' => 'array',
            'parse_confidence' => 'float',
            'image_count' => 'integer',
            'images_imported' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function carListing()
    {
        return $this->belongsTo(CarListing::class);
    }

    public function duplicatesWith()
    {
        return $this->belongsTo(CarListing::class, 'duplicate_detected_with', 'slug');
    }

    public const SOURCES = ['dubizzle', 'dubicars', 'yallamotor'];
    public const METHODS = ['browser_extension', 'direct_url', 'manual_html'];
    public const STATUSES = ['captured', 'parsing', 'needs_review', 'images_pending', 'ready', 'failed', 'published'];
}
