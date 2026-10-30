<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MaintenancePlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'serial_number',
        'center_name',
        'vehicle_number',
        'vehicle_type',
        'usage_group',
        'week1_planned',
        'week1_executed',
        'week2_planned',
        'week2_executed',
        'week3_planned',
        'week3_executed',
        'week4_planned',
        'week4_executed',
        'month',
        'year',
        'file_name',
    ];
}
