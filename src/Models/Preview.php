<?php

namespace Karabin\Fabriq\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Preview extends Model
{
    protected $guarded = [];

    protected $casts = [
        'content' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function (Preview $preview) {
            $preview->uuid = Str::uuid();
        });
    }
}
