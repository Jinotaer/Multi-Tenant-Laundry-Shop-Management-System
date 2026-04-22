<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'discount_percentage',
        'valid_until',
    ];

    protected $casts = [
        'valid_until' => 'datetime',
        'discount_percentage' => 'integer',
    ];
}
