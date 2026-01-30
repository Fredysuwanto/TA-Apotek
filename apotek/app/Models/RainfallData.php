<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RainfallData extends Model
{
    use HasFactory;

    protected $table = 'rainfall_data';

    protected $fillable = [
        'tanggal',
        'curahhujan',
    ];

    protected $casts = [
        'tanggal'    => 'date',
        'curahhujan' => 'decimal:1',
    ];
}
