<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CarListingImage extends Model
{
    protected $fillable = ['car_listing_id', 'local_path', 'source_url', 'sort_order', 'is_cover'];

    protected function casts(): array
    {
        return [
            'is_cover' => 'boolean',
        ];
    }

    public function carListing()
    {
        return $this->belongsTo(CarListing::class);
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->local_path);
    }
}
