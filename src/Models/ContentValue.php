<?php

namespace Karabin\Fabriq\Models;

use Illuminate\Database\Eloquent\Model;

class ContentValue extends Model
{
    protected $guarded = [];

    protected $casts = [
        'value' => 'array',
    ];
}
