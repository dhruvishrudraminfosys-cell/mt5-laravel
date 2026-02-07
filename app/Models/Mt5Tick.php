<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mt5Tick extends Model
{
     protected $fillable = [
        'symbol',
        'bid',
        'ask',
        'spread',
        'tick_time',
    ];

     protected $casts = [
        'tick_time' => 'datetime',
        'bid' => 'decimal:5',
        'ask' => 'decimal:5',
        'spread' => 'decimal:5',
    ];
}
