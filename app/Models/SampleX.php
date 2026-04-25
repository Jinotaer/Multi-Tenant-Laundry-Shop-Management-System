<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SampleX extends Model
{
    /** @use HasFactory<\Database\Factories\SampleXFactory> */
    use HasFactory;

    protected $table = 'sample_x';

    protected $fillable = [
        'number',
        'description',
    ];
}
