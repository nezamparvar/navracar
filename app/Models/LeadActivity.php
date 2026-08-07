<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadActivity extends Model
{
    public $timestamps = false;

    protected $fillable = ['request_id', 'admin_user_id', 'activity_type', 'note'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function request()
    {
        return $this->belongsTo(QuoteRequest::class, 'request_id');
    }

    public function adminUser()
    {
        return $this->belongsTo(AdminUser::class, 'admin_user_id');
    }
}
