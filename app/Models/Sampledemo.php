<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sampledemo extends Model
{
    /** @use HasFactory<\Database\Factories\SampledemoFactory> */
    use HasFactory;

    protected $table = 'sampledemo';

    protected $fillable = ['name', 'age'];
}
