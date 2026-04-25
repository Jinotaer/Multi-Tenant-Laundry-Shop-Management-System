<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sample1 extends Model
{
    /** @use HasFactory<\Database\Factories\Sample1Factory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'age',
    ];
}
