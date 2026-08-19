<?php

namespace Karabin\Fabriq\Models;

use Illuminate\Database\Eloquent\Model;

class Preview extends Model
{
    protected $guarded = [];

    protected $casts = [
        'content' => 'array',
    ];
}
