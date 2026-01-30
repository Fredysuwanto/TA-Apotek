<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesData extends Model
{
    use HasFactory;

    protected $table = 'sales_data';
    
    protected $fillable = [
        'tanggal',
        'nama_obat',
        'satuan',
        'x1',
        'x2',
        'y'
    ];

    protected $casts = [
        'tanggal' => 'date',
        'x1' => 'decimal:2',
        'x2' => 'decimal:1',
        'y'
    ];
}