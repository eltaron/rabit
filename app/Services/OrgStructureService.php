<?php

namespace App\Services;

use App\Models\OrgUnit;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * بناء الهيكل التنظيمي للمحافظة: التقسيم الإداري (مدن/أحياء/وحدات محلية/قرى/عزب)
 * من ملف وزارة التنمية المحلية، والمديريات والهيئات الرئيسية. كل العمليات idempotent.
 */
class OrgStructureService
{
    public const ADMIN_FILE = 'قواعد البيانات/التقسيم الادارى/التنمية المحلية 2018-التقسيم الاداري/اخر تحديث للتقسيم الادارى.xlsx';

    private array $stats = [];
    private array $cache = [];

    /** توحيد الكتابة العربية للمطابقة */
    public static function norm(?string $s): string
    {
        $s = (string) $s;
        $s = str_replace(['أ', 'إ', 'آ', 'ٱ', 'ة', 'ى', 'ـ', '"', "'", '´', '.'], ['ا', 'ا', 'ا', 'ا', 'ه', 'ي', '', '', '', '', ''], $s);
        $s = preg_replace('/^(مركز|مدينه|مركز ومدينه)\s*/u', '', trim($s));
        return preg_replace('/\s+/u', '', $s);
    }

    private static function clean(?string $s): string
    {
        $s = trim(preg_replace('/\s+/u', ' ', (string) $s));
        $s = preg_replace('/^[\s.\-–—ـ،,]+|[\s.\-–—ـ،,]+$/u', '', $s);
        // أقواس غير متوازنة في الأطراف
        if (substr_count($s, '(') !== substr_count($s, ')')) $s = trim(str_replace(['(', ')'], ' ', $s));
        return trim(preg_replace('/\s+/u', ' ', $s));
    }

    /** إنشاء أو جلب جهة (حسب الأب + النوع + الاسم الموحّد) */
    public function upsert(?int $parentId, string $type, string $name, array $attrs = []): int
    {
        $name = self::clean($name);
        $key = $parentId . '|' . $type . '|' . self::norm($name);
        if (isset($this->cache[$key])) return $this->cache[$key];

        $existing = DB::table('org_units')->whereNull('deleted_at')
            ->where('type', $type)
            ->when($parentId, fn ($q) => $q->where('parent_id', $parentId), fn ($q) => $q->whereNull('parent_id'))
            ->get(['id', 'name'])
            ->first(fn ($r) => self::norm($r->name) === self::norm($name));

        if ($existing) {
            return $this->cache[$key] = $existing->id;
        }

        $parent = $parentId ? DB::table('org_units')->where('id', $parentId)->first(['path', 'depth']) : null;
        $id = DB::table('org_units')->insertGetId(array_merge([
            'parent_id' => $parentId,
            'type' => $type,
            'name' => $name,
            'is_active' => true,
            'path' => '/',
            'depth' => $parent ? $parent->depth + 1 : 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attrs));
        DB::table('org_units')->where('id', $id)->update(['path' => ($parent->path ?? '/') . $id . '/']);
        $this->stats[$type] = ($this->stats[$type] ?? 0) + 1;

        return $this->cache[$key] = $id;
    }

    /** يقسم قائمة العزب/الكفور المفصولة بشرطات */
    public static function splitList(?string $s): array
    {
        $s = (string) $s;
        if ($s === '' || preg_match('/^\s*(0|لا يوجد.*)\s*$/u', $s)) return [];
        $parts = preg_split('/\s*(?:-{1,2}|–|—|ـ{1,}|،|\n)\s*/u', $s);
        return collect($parts)->map(fn ($p) => self::clean($p))
            ->filter(fn ($p) => $p !== '' && $p !== '0' && mb_strlen($p) > 1 && ! str_contains($p, 'لا يوجد'))
            ->unique()->values()->all();
    }

    /**
     * يستورد التقسيم الإداري. يعيد إحصاءات بالعدد المضاف لكل نوع.
     */
    public function importAdministrative(?string $file = null): array
    {
        $file ??= public_path(self::ADMIN_FILE);
        if (! is_file($file)) {
            throw new \RuntimeException('ملف التقسيم الإداري غير موجود: ' . $file);
        }

        $root = OrgUnit::root();
        $centers = OrgUnit::where('type', 'center')->get(['id', 'name']);
        $findCenter = function (string $name) use ($centers, $root) {
            $n = self::norm(preg_replace('/^تابع\s*/u', '', $name));
            $c = $centers->first(fn ($c) => self::norm($c->name) === $n);
            if ($c) return $c->id;
            // مركز غير موجود: أنشئه تحت المحافظة
            return $this->upsert($root->id, 'center', 'مركز ' . self::clean(preg_replace('/^تابع\s*/u', '', $name)));
        };

        $ss = IOFactory::load($file);
        $ws = $ss->getSheetByName('كفر الشيخ') ?? $ss->getSheet($ss->getSheetCount() - 1);
        $max = $ws->getHighestDataRow();
        $v = fn ($col, $r) => self::clean((string) $ws->getCell($col . $r)->getCalculatedValue());

        $center = $city = $district = $local = $village = $last = null;

        DB::transaction(function () use ($max, $v, $findCenter, $centers, &$center, &$city, &$district, &$local, &$village, &$last) {
            $blank = 0;
            for ($r = 8; $r <= $max; $r++) {
                $rowText = $v('B', $r) . $v('C', $r) . $v('D', $r) . $v('F', $r) . $v('H', $r) . $v('J', $r);
                $blank = $rowText === '' ? $blank + 1 : 0;
                // نهاية الجدول الرئيسي: ملاحظات وجداول جانبية بعد عدة صفوف فارغة
                if ($blank >= 3 || $v('F', $r) === 'المركز') break;
                $b = $v('B', $r);
                if ($b !== '' && ! is_numeric($b)) {
                    $newCenter = $findCenter($b);
                    if ($newCenter !== $center) {
                        $center = $newCenter;
                        $city = $district = $local = $village = null;
                        $last = $center;
                    }
                }
                if (! $center) continue;

                if (($c = $v('C', $r)) !== '') {
                    // مدينة لها مركز مستقل (مثل مصيف بلطيم) تُلحق بمركزها لا بالمركز الأم في الملف
                    $own = in_array(self::norm($c), [self::norm('مصيف بلطيم')], true)
                        ? $centers->first(fn ($x) => self::norm($x->name) === self::norm('مركز ' . $c))
                        : null;
                    $city = $this->upsert($own->id ?? $center, 'city', 'مدينة ' . $c);
                    $district = $local = $village = null;
                    $last = $city;
                }
                if (($d = $v('D', $r)) !== '') {
                    $district = $this->upsert($city ?? $center, 'district', $d);
                    $last = $district;
                }
                if (($f = $v('F', $r)) !== '' && ! is_numeric($f)) {
                    $local = $this->upsert($center, 'local_unit', $f);
                    $village = null;
                    $last = $local;
                }
                if (($h = $v('H', $r)) !== '' && ! is_numeric($h)) {
                    // القرية التابعة تتبع الوحدة المحلية القروية الحالية
                    $village = $this->upsert($local ?? $center, 'village', $h);
                    $last = $village;
                }
                if (($j = (string) $v('J', $r)) !== '' && $last) {
                    foreach (self::splitList($j) as $hamlet) {
                        $this->upsert($last, 'hamlet', $hamlet);
                    }
                }
            }
        });

        // كل وحدة محلية قروية هي أيضاً قرية أم: سجّلها كبيانات وصفية
        return $this->stats;
    }

    /** المديريات والهيئات والشركات وديوان عام المحافظة */
    public function seedEntities(): array
    {
        $root = OrgUnit::root();
        if (! $root) throw new \RuntimeException('لا توجد جهة جذرية (المحافظة).');
        $rid = $root->id;

        // تصحيح أسماء قديمة غير عربية
        $renames = ['الlocal administration' => 'الإدارة المحلية', 'الشئون Civic' => 'الشئون المدنية', 'Directorate of Awqaf - KFS' => 'مديرية الأوقاف بكفر الشيخ'];
        foreach ($renames as $old => $new) {
            DB::table('org_units')->where('name', $old)->update(['name' => $new, 'updated_at' => now()]);
        }

        $existingNorm = OrgUnit::whereIn('type', ['ministry', 'directorate', 'authority', 'company'])->get(['id', 'name'])
            ->mapWithKeys(fn ($u) => [self::norm(preg_replace('/^(مديرية|مديريه)\s*/u', '', $u->name)) => $u->id]);

        $has = fn (string $name) => $existingNorm->has(self::norm(preg_replace('/^(مديرية|مديريه)\s*/u', '', $name)));

        // ديوان عام المحافظة
        $diwan = $this->upsert($rid, 'directorate', 'ديوان عام المحافظة', ['code' => 'DIWAN', 'sort_order' => -10]);
        foreach ([
            'مكتب السيد المحافظ', 'السكرتارية العامة', 'الإدارة العامة للشئون القانونية', 'الإدارة العامة للتخطيط والمتابعة',
            'الإدارة العامة للتنظيم والإدارة', 'الإدارة العامة للموارد البشرية', 'الإدارة العامة للشئون المالية والإدارية',
            'مركز المعلومات ودعم اتخاذ القرار', 'إدارة نظم المعلومات والتحول الرقمي', 'منظومة الشكاوى الحكومية',
            'إدارة الأزمات والكوارث', 'الإدارة العامة للإعلام والعلاقات العامة', 'إدارة المشروعات', 'إدارة التفتيش المالي والإداري',
            'إدارة المتغيرات المكانية', 'إدارة أملاك الدولة',
        ] as $i => $d) {
            $this->upsert($diwan, 'department', $d, ['sort_order' => $i]);
        }

        // مديريات الوزارات
        $ministries = [
            'مديرية التربية والتعليم' => 'وزارة التربية والتعليم', 'مديرية الشئون الصحية' => 'وزارة الصحة والسكان',
            'مديرية الزراعة' => 'وزارة الزراعة واستصلاح الأراضي', 'مديرية الموارد المائية والري' => 'وزارة الموارد المائية والري',
            'مديرية التموين والتجارة الداخلية' => 'وزارة التموين', 'مديرية الأوقاف' => 'وزارة الأوقاف',
            'مديرية الشباب والرياضة' => 'وزارة الشباب والرياضة', 'مديرية التضامن الاجتماعي' => 'وزارة التضامن الاجتماعي',
            'مديرية الإسكان والمرافق' => 'وزارة الإسكان', 'مديرية الطرق والنقل' => 'وزارة النقل',
            'مديرية القوى العاملة' => 'وزارة العمل', 'مديرية الثقافة' => 'وزارة الثقافة', 'مديرية السياحة والآثار' => 'وزارة السياحة والآثار',
            'مديرية شئون البيئة' => 'وزارة البيئة', 'مديرية الطب البيطري' => 'وزارة الزراعة', 'مديرية الطرق والكباري' => 'المحافظة',
            'مديرية الأمن' => 'وزارة الداخلية', 'مديرية الصناعة والتجارة' => 'وزارة التجارة والصناعة',
            'مديرية التنظيم والإدارة' => 'الجهاز المركزي للتنظيم والإدارة', 'مديرية الاتصالات وتكنولوجيا المعلومات' => 'وزارة الاتصالات',
            'مديرية العدل' => 'وزارة العدل', 'مديرية الضرائب العقارية' => 'وزارة المالية', 'مديرية المساحة' => 'الهيئة المصرية العامة للمساحة',
            'مديرية التنمية المحلية' => 'وزارة التنمية المحلية', 'مديرية الثروة السمكية' => 'الهيئة العامة لتنمية الثروة السمكية',
        ];
        $aliases = [
            'مديرية الشئون الصحية' => ['الصحة'], 'مديرية التربية والتعليم' => ['التعليم'], 'مديرية الموارد المائية والري' => ['الري'],
            'مديرية الأوقاف' => ['الأوقاف والشؤون الدينية'], 'مديرية الزراعة' => ['الزراعة'],
            'مديرية التضامن الاجتماعي' => ['التضامن الاجتماعي'], 'مديرية القوى العاملة' => ['القوى العاملة'],
        ];
        $i = 0;
        foreach ($ministries as $name => $ministry) {
            if ($has($name) || collect($aliases[$name] ?? [])->contains(fn ($a) => $has($a))) continue;
            $this->upsert($rid, 'ministry', $name, ['description' => 'تتبع ' . $ministry, 'sort_order' => 10 + $i++]);
        }

        // الهيئات والشركات والأجهزة
        $bodies = [
            ['authority', 'جامعة كفر الشيخ'], ['authority', 'الهيئة العامة للأبنية التعليمية - فرع كفر الشيخ'],
            ['authority', 'الهيئة العامة لتعليم الكبار - فرع كفر الشيخ'], ['authority', 'إدارة الحماية المدنية'],
            ['authority', 'هيئة الإسعاف المصرية - فرع كفر الشيخ'], ['authority', 'الهيئة العامة للتأمين الصحي - فرع كفر الشيخ'],
            ['authority', 'جهاز حماية المستهلك - فرع كفر الشيخ'], ['authority', 'الهيئة القومية للبريد - منطقة كفر الشيخ'],
            ['authority', 'السجل المدني'], ['authority', 'هيئة الأوقاف المصرية - منطقة كفر الشيخ'],
            ['company', 'شركة مياه الشرب والصرف الصحي بكفر الشيخ'], ['company', 'شركة شمال الدلتا لتوزيع الكهرباء - قطاع كفر الشيخ'],
            ['company', 'الشركة المصرية للاتصالات - منطقة كفر الشيخ'], ['company', 'شركة الغاز الطبيعي - كفر الشيخ'],
            ['company', 'جهاز تنمية المشروعات - مكتب كفر الشيخ'], ['company', 'المنطقة الصناعية بكفر الشيخ'],
        ];
        foreach ($bodies as $k => [$type, $name]) {
            if ($has($name)) continue;
            $this->upsert($rid, $type, $name, ['sort_order' => 50 + $k]);
        }

        return $this->stats;
    }

    /** الوظائف القيادية الأساسية (لا يكرر الموجود) */
    public function seedPositions(): array
    {
        $count = 0;
        $make = function (int $unitId, string $title, string $category, ?string $grade, ?int $parentId = null, int $sort = 0) use (&$count) {
            $existing = DB::table('job_positions')->where('org_unit_id', $unitId)->where('title', $title)->value('id');
            if ($existing) return $existing;
            $count++;
            return DB::table('job_positions')->insertGetId([
                'org_unit_id' => $unitId, 'parent_id' => $parentId, 'title' => $title, 'category' => $category,
                'grade' => $grade, 'headcount' => 1, 'sort_order' => $sort, 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        };

        $root = OrgUnit::root();
        $gov = $make($root->id, 'محافظ كفر الشيخ', 'leadership', 'ممتازة');
        $make($root->id, 'نائب المحافظ', 'leadership', 'ممتازة', $gov, 1);
        $sg = $make($root->id, 'السكرتير العام', 'leadership', 'عالية', $gov, 2);
        $asg = $make($root->id, 'السكرتير العام المساعد', 'leadership', 'مدير عام', $sg, 3);

        if ($diwan = OrgUnit::where('code', 'DIWAN')->first()) {
            foreach (OrgUnit::where('parent_id', $diwan->id)->get() as $i => $d) {
                $title = str_starts_with($d->name, 'الإدارة العامة') ? 'مدير عام ' . mb_substr($d->name, 15) : 'مدير ' . $d->name;
                $make($d->id, trim($title), 'leadership', str_starts_with($d->name, 'الإدارة العامة') ? 'مدير عام' : 'الأولى', $asg, $i);
            }
        }
        foreach (OrgUnit::where('type', 'center')->get() as $c) {
            $head = $make($c->id, 'رئيس ' . str_replace('مركز ', 'مركز ومدينة ', $c->name), 'leadership', 'مدير عام', $sg);
            $make($c->id, 'سكرتير ' . $c->name, 'supervisory', 'الأولى', $head, 1);
            foreach (OrgUnit::where('parent_id', $c->id)->where('type', 'city')->get() as $city) {
                $make($city->id, 'رئيس مجلس ' . $city->name, 'leadership', 'مدير عام', $head);
            }
            foreach (OrgUnit::where('parent_id', $c->id)->where('type', 'local_unit')->get() as $lu) {
                $make($lu->id, 'رئيس الوحدة المحلية', 'supervisory', 'الأولى', $head);
            }
        }
        foreach (OrgUnit::whereIn('type', ['ministry', 'directorate'])->where('code', '!=', 'DIWAN')->orWhere(fn ($q) => $q->whereIn('type', ['ministry', 'directorate'])->whereNull('code'))->get() as $m) {
            $make($m->id, $m->type === 'ministry' ? 'وكيل الوزارة' : 'مدير المديرية', 'leadership', 'مدير عام', $gov);
        }
        foreach (OrgUnit::whereIn('type', ['authority', 'company'])->get() as $a) {
            $make($a->id, 'رئيس الجهة', 'leadership', 'مدير عام');
        }

        $this->stats['positions'] = $count;
        return $this->stats;
    }

    public function stats(): array
    {
        return $this->stats;
    }

    /** إعادة بناء المسارات للشجرة كلها */
    public static function rebuildPaths(): void
    {
        $rows = DB::table('org_units')->get(['id', 'parent_id']);
        $children = [];
        foreach ($rows as $r) $children[(int) $r->parent_id][] = $r->id;
        $walk = function (int $pid, string $prefix, int $depth) use (&$walk, $children) {
            foreach ($children[$pid] ?? [] as $id) {
                $path = $prefix . $id . '/';
                DB::table('org_units')->where('id', $id)->update(['path' => $path, 'depth' => $depth]);
                $walk($id, $path, $depth + 1);
            }
        };
        $walk(0, '/', 0);
    }
}
