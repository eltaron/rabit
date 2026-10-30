<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\MaintenancePlan;
use App\Models\StrategicData;
use App\Models\SectorData;
use App\Models\FacilityData;
use App\Services\FacilityDataImportService;
use App\Services\SectorDataImportService;
use Illuminate\Http\Request;

class ExcelUploadController extends Controller
{
    public function index()
    {
        $sectorCounts = [];
        foreach (SectorDataImportService::SECTORS as $s) {
            $sectorCounts[$s] = SectorData::where('sector', $s)->count();
        }

        $facilityCounts = [];
        foreach (FacilityDataImportService::MODULES as $m) {
            $facilityCounts[$m] = FacilityData::where('module_code', $m)->count();
        }

        $stats = [
            'vehicles' => Vehicle::count(),
            'maintenance' => MaintenancePlan::count(),
            'strategic' => StrategicData::count(),
            'sectors' => array_sum($sectorCounts),
            'sectorCounts' => $sectorCounts,
            'facilities' => array_sum($facilityCounts),
            'facilityCounts' => $facilityCounts,
            'total' => Vehicle::count() + MaintenancePlan::count() + StrategicData::count() + array_sum($sectorCounts) + array_sum($facilityCounts),
        ];

        return view('excel-upload.index', compact('stats'));
    }

    public function vehicles(Request $request)
    {
        $query = Vehicle::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('sheet')) {
            $query->where('sheet_name', $request->sheet);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('license_plate', 'like', "%{$search}%")
                  ->orWhere('vehicle_type', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('affiliated_entity', 'like', "%{$search}%");
            });
        }

        $vehicles = $query->orderByDesc('id')->paginate(20);
        $sheets = Vehicle::distinct()->pluck('sheet_name')->filter()->values();

        return view('excel-upload.vehicles', compact('vehicles', 'sheets'));
    }

    public function maintenance(Request $request)
    {
        $query = MaintenancePlan::query();

        if ($request->filled('center')) {
            $query->where('center_name', $request->center);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('vehicle_number', 'like', "%{$search}%")
                  ->orWhere('vehicle_type', 'like', "%{$search}%")
                  ->orWhere('center_name', 'like', "%{$search}%");
            });
        }

        $plans = $query->orderByDesc('id')->paginate(20);
        $centers = MaintenancePlan::distinct()->pluck('center_name')->filter()->values();

        return view('excel-upload.maintenance', compact('plans', 'centers'));
    }

    public function strategic(Request $request)
    {
        $query = StrategicData::query();

        if ($request->filled('sheet')) {
            $query->where('sheet_name', $request->sheet);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('center', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        $data = $query->orderByDesc('id')->paginate(20);
        $sheets = StrategicData::distinct()->pluck('sheet_name')->filter()->values();

        return view('excel-upload.strategic', compact('data', 'sheets'));
    }

    public function sector(Request $request, string $sector)
    {
        if (!in_array($sector, SectorDataImportService::SECTORS, true)) {
            abort(404);
        }

        $query = \App\Support\DataScope::byUnit(SectorData::where('sector', $sector), $request->user());

        if ($request->filled('sheet')) {
            $query->where('sheet_name', $request->sheet);
        }
        if ($request->filled('center')) {
            $query->where('center_name', $request->center);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('center_name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('subcategory', 'like', "%{$search}%")
                  ->orWhere('extra_data', 'like', "%{$search}%");
            });
        }

        $data = $query->with('center')->orderByDesc('id')->paginate(20);
        $sheets = SectorData::where('sector', $sector)->distinct()->pluck('sheet_name')->filter()->values();
        $centers = SectorData::where('sector', $sector)->distinct()->pluck('center_name')->filter()->values();
        $label = SectorDataImportService::SECTOR_LABELS[$sector] ?? $sector;

        return view('excel-upload.sector', compact('data', 'sheets', 'centers', 'sector', 'label'));
    }

    public function facilities(Request $request, string $module)
    {
        if (!in_array($module, FacilityDataImportService::MODULES, true)) {
            abort(404);
        }

        $query = \App\Support\DataScope::byUnit(FacilityData::where('module_code', $module), $request->user());

        if ($request->filled('center')) {
            $query->where('center_name', $request->center);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('center_name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('governorate', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('district', 'like', "%{$search}%")
                  ->orWhere('extra_data', 'like', "%{$search}%");
            });
        }

        $data = $query->with('center')->orderByDesc('id')->paginate(20);
        $centers = FacilityData::where('module_code', $module)->distinct()->pluck('center_name')->filter()->values();
        $label = FacilityDataImportService::MODULE_LABELS[$module] ?? $module;

        return view('excel-upload.facilities', compact('data', 'centers', 'module', 'label'));
    }
}
