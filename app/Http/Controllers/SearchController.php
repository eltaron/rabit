<?php

namespace App\Http\Controllers;

use App\Models\FacilityData;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\JobPosition;
use App\Models\OrgUnit;
use App\Models\User;
use App\Support\DataScope;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * البحث الشامل في الجهات والنماذج والإرسالات والبيانات المكانية (حسب نطاق المستخدم).
 */
class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->input('q'));
        $user = $request->user();
        $results = [];

        if (mb_strlen($q) >= 2) {
            $like = '%' . $q . '%';

            $results['units'] = OrgUnit::where(fn ($w) => $w->where('name', 'like', $like)->orWhere('code', 'like', $like))
                ->orderBy('depth')->limit(15)->get();

            $results['templates'] = DataScope::templates(FormTemplate::query(), $user)
                ->where('name', 'like', $like)->withCount('formSubmissions')->limit(15)->get();

            $results['submissions'] = DataScope::submissions(FormSubmission::query(), $user)
                ->with('formTemplate')
                ->where(fn ($w) => $w->where('reference_number', 'like', $like)
                    ->orWhereHas('formTemplate', fn ($t) => $t->where('name', 'like', $like)))
                ->latest()->limit(15)->get();

            if ($user->can('view_map')) {
                $results['places'] = DataScope::byUnit(FacilityData::query(), $user)
                    ->where('name', 'like', $like)->limit(20)
                    ->get(['id', 'module_name', 'name', 'center_name', 'address', 'latitude', 'longitude']);
            }

            if ($user->can('manage_users')) {
                $results['users'] = User::where(fn ($w) => $w->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)->orWhere('national_id', 'like', $like))
                    ->with('orgUnit')->limit(15)->get();
            }

            if ($user->can('manage_positions')) {
                $results['positions'] = JobPosition::with(['orgUnit', 'holder'])->where('title', 'like', $like)->limit(15)->get();
            }
        }

        $total = collect($results)->sum(fn ($c) => $c->count());

        return view('search.index', compact('q', 'results', 'total'));
    }
}
