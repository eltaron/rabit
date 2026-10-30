<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StrategicData extends Model
{
    use HasFactory;

    protected $fillable = [
        'serial_number',
        'category',
        'name',
        'headquarters',
        'address',
        'phone',
        'center',
        'city',
        'neighborhood',
        'governorate',
        'type',
        'governorate_code',
        'center_code',
        'city_code',
        'x_coord',
        'y_coord',
        'additional_data',
        'sheet_name',
        'file_name',
    ];

    protected $casts = [
        'additional_data' => 'array',
    ];
}
