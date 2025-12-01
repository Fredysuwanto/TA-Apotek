<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegressionResult extends Model
{
    use HasFactory;

    protected $table = 'regression_results';
    
    protected $fillable = [
        'nama_obat',
        'equation',
        'coefficients',
        'calculations',
        'predictions',
        'metrics'
    ];

    protected $casts = [
        'coefficients' => 'array',
        'calculations' => 'array',
        'predictions' => 'array',
        'metrics' => 'array'
    ];
}