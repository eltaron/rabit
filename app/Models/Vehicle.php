<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'serial_number',
        'entity',
        'affiliated_entity',
        'license_plate',
        'chassis_number',
        'engine_number',
        'vehicle_type',
        'vehicle_type_per_license',
        'color',
        'model_year',
        'manufacture_year',
        'technical_condition',
        'fuel_type',
        'procurement_type',
        'procurement_source',
        'procurement_year',
        'odometer_reading',
        'equipment_level',
        'location',
        'maintenance_contract',
        'equipment_maintenance_contract',
        'annual_usage_distance',
        'breakdown_type',
        'breakdown_date',
        'action_taken',
        'reason_repair_failed',
        'entity_need',
        'breakdown_cause',
        'breakdown_details',
        'reason_surplus',
        'technical_validity',
        'vehicle_photo',
        'status',
        'sheet_name',
        'file_name',
    ];
}
