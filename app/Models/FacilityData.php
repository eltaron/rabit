<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FacilityData extends Model
{
    use HasFactory;

    protected $table = 'facilities_data';

    protected $fillable = [
        'module_code',
        'module_name',
        'file_name',
        'sheet_name',
        'center_id',
        'center_name',
        'name',
        'type',
        'address',
        'phone',
        'governorate',
        'directorate',
        'city',
        'district',
        'org_unit_id',
        'x_coord',
        'y_coord',
        'latitude',
        'longitude',
        'extra_data',
    ];

    protected $casts = [
        'extra_data' => 'array',
        'x_coord' => 'float',
        'y_coord' => 'float',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    protected static function booted(): void
    {
        // حساب خط العرض/الطول الموحد من X/Y عند الاستيراد
        static::saving(fn ($m) => \App\Services\MapDataService::fillModelCoordinates($m));
    }

    public function orgUnit()
    {
        return $this->belongsTo(OrgUnit::class);
    }

    public function center()
    {
        return $this->belongsTo(Center::class);
    }
}
