<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
