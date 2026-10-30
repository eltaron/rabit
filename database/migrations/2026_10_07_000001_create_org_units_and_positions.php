<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * الهيكل التنظيمي الموحد (شجرة الجهات) + الهيكل الوظيفي + نطاق البيانات.
 *
 * org_units يحل محل التقسيم القديم (governorates/sectors/departments/centers/divisions)
 * كمصدر رئيسي، مع الإبقاء على الجداول القديمة وربطها عبر legacy_* للتوافق.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('org_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('org_units')->nullOnDelete();
            $table->string('type', 30)->index()->comment('governorate|sector|ministry|directorate|authority|company|center|city|district|local_unit|village|hamlet|department|section|office');
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->string('code', 50)->nullable()->unique();
            $table->text('description')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('geometry')->nullable()->comment('GeoJSON geometry (Polygon/MultiPolygon)');
            $table->unsignedBigInteger('population')->nullable();
            $table->decimal('area_km2', 12, 3)->nullable();
            $table->string('head_name')->nullable()->comment('اسم رئيس الجهة');
            $table->string('head_title')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->json('meta')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('path', 500)->default('/')->index()->comment('materialized path: /1/5/23/');
            $table->unsignedSmallInteger('depth')->default(0);
            $table->unsignedBigInteger('legacy_sector_id')->nullable()->index();
            $table->unsignedBigInteger('legacy_department_id')->nullable()->index();
            $table->unsignedBigInteger('legacy_center_id')->nullable()->index();
            $table->unsignedBigInteger('legacy_division_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('job_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_unit_id')->constrained('org_units')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('job_positions')->nullOnDelete()->comment('يتبع وظيفياً');
            $table->string('title');
            $table->string('code', 50)->nullable()->unique();
            $table->string('grade', 60)->nullable()->comment('الدرجة الوظيفية');
            $table->string('category', 30)->default('executive')->comment('leadership|supervisory|specialist|executive|support');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->comment('شاغل الوظيفة');
            $table->unsignedSmallInteger('headcount')->default(1)->comment('عدد الدرجات المعتمدة');
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('org_unit_id')->nullable()->after('division_id')->constrained('org_units')->nullOnDelete();
            $table->foreignId('job_position_id')->nullable()->after('org_unit_id')->constrained('job_positions')->nullOnDelete();
            $table->string('data_scope', 20)->default('unit')->after('job_position_id')->comment('own|unit|subtree|all');
        });

        Schema::table('form_templates', function (Blueprint $table) {
            $table->foreignId('org_unit_id')->nullable()->after('department_id')->constrained('org_units')->nullOnDelete()->comment('الجهة المالكة للنموذج');
            $table->string('distribution', 20)->default('all')->after('org_unit_id')->comment('all|targeted|owner');
            $table->string('geo_lat_field')->nullable()->after('distribution')->comment('اسم حقل خط العرض');
            $table->string('geo_lng_field')->nullable()->after('geo_lat_field')->comment('اسم حقل خط الطول');
        });

        Schema::create('form_template_org_unit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('org_unit_id')->constrained('org_units')->cascadeOnDelete();
            $table->boolean('include_children')->default(true);
            $table->date('due_date')->nullable();
            $table->timestamps();
            $table->unique(['form_template_id', 'org_unit_id']);
        });

        Schema::table('form_submissions', function (Blueprint $table) {
            $table->foreignId('org_unit_id')->nullable()->after('assigned_to')->constrained('org_units')->nullOnDelete();
        });

        Schema::table('facilities_data', function (Blueprint $table) {
            $table->foreignId('org_unit_id')->nullable()->after('center_id')->constrained('org_units')->nullOnDelete();
            $table->decimal('latitude', 10, 7)->nullable()->after('y_coord');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });

        Schema::table('sector_data', function (Blueprint $table) {
            $table->foreignId('org_unit_id')->nullable()->after('center_id')->constrained('org_units')->nullOnDelete();
            $table->decimal('latitude', 10, 7)->nullable()->after('y_coord');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });

        $this->migrateLegacyStructure();
    }

    private function migrateLegacyStructure(): void
    {
        $now = now();
        $insert = function (array $row) use ($now): int {
            return DB::table('org_units')->insertGetId($row + ['created_at' => $now, 'updated_at' => $now]);
        };

        $gov = DB::table('governorates')->orderBy('id')->first();
        $rootId = $insert([
            'type' => 'governorate',
            'name' => 'محافظة كفر الشيخ',
            'name_en' => 'Kafr El-Sheikh Governorate',
            'code' => 'KFS',
            'latitude' => 31.1107,
            'longitude' => 30.9388,
            'population' => 3_700_000,
            'area_km2' => 3748,
        ]);

        // المراكز الإدارية (المراكز والمدن) مباشرة تحت المحافظة
        $adminCenters = DB::table('centers')->where('code', 'like', 'LGOV%')->get();
        foreach ($adminCenters as $i => $c) {
            $insert([
                'parent_id' => $rootId,
                'type' => 'center',
                'name' => $c->name,
                'code' => 'C-' . $c->code,
                'latitude' => $c->latitude,
                'longitude' => $c->longitude,
                'sort_order' => $i,
                'legacy_center_id' => $c->id,
            ]);
        }

        // القطاعات ← المديريات ← الإدارات/الجهات ← الأقسام
        foreach (DB::table('sectors')->orderBy('id')->get() as $s) {
            $sid = $insert(['parent_id' => $rootId, 'type' => 'sector', 'name' => 'قطاع ' . $s->name, 'code' => 'S-' . $s->code, 'legacy_sector_id' => $s->id, 'is_active' => $s->is_active]);
            foreach (DB::table('departments')->where('sector_id', $s->id)->get() as $d) {
                $did = $insert(['parent_id' => $sid, 'type' => 'directorate', 'name' => $d->name, 'code' => 'D-' . $d->code, 'legacy_department_id' => $d->id, 'is_active' => $d->is_active]);
                foreach (DB::table('centers')->where('department_id', $d->id)->where('code', 'not like', 'LGOV%')->get() as $c) {
                    $cid = $insert(['parent_id' => $did, 'type' => 'department', 'name' => $c->name, 'code' => 'U-' . $c->code, 'latitude' => $c->latitude, 'longitude' => $c->longitude, 'legacy_center_id' => $c->id, 'is_active' => $c->is_active]);
                    foreach (DB::table('divisions')->where('center_id', $c->id)->get() as $v) {
                        $insert(['parent_id' => $cid, 'type' => 'section', 'name' => $v->name, 'code' => 'V-' . $v->code, 'legacy_division_id' => $v->id, 'is_active' => $v->is_active]);
                    }
                }
            }
        }

        // تقسيمات تحت المراكز الإدارية (إن وجدت)
        foreach ($adminCenters as $c) {
            $parent = DB::table('org_units')->where('legacy_center_id', $c->id)->where('type', 'center')->value('id');
            foreach (DB::table('divisions')->where('center_id', $c->id)->get() as $v) {
                $insert(['parent_id' => $parent, 'type' => 'section', 'name' => $v->name, 'code' => 'V-' . $v->code, 'legacy_division_id' => $v->id]);
            }
        }

        $this->rebuildPaths();

        // ربط المستخدمين بأدق جهة متاحة
        foreach (DB::table('users')->get() as $u) {
            $unit = null;
            if ($u->division_id) $unit = DB::table('org_units')->where('legacy_division_id', $u->division_id)->value('id');
            if (! $unit && $u->center_id) $unit = DB::table('org_units')->where('legacy_center_id', $u->center_id)->value('id');
            if (! $unit && $u->department_id) $unit = DB::table('org_units')->where('legacy_department_id', $u->department_id)->value('id');
            if (! $unit && $u->sector_id) $unit = DB::table('org_units')->where('legacy_sector_id', $u->sector_id)->value('id');
            DB::table('users')->where('id', $u->id)->update(['org_unit_id' => $unit ?? $rootId]);
        }

        // ربط النماذج بالجهة المالكة
        foreach (DB::table('form_templates')->get() as $t) {
            $unit = null;
            if ($t->center_id) $unit = DB::table('org_units')->where('legacy_center_id', $t->center_id)->value('id');
            if (! $unit && $t->department_id) $unit = DB::table('org_units')->where('legacy_department_id', $t->department_id)->value('id');
            if (! $unit && $t->sector_id) $unit = DB::table('org_units')->where('legacy_sector_id', $t->sector_id)->value('id');
            DB::table('form_templates')->where('id', $t->id)->update(['org_unit_id' => $unit ?? $rootId]);
        }

        // الإرسالات تأخذ جهة منشئها
        DB::statement('UPDATE form_submissions fs JOIN users u ON u.id = fs.created_by SET fs.org_unit_id = u.org_unit_id');

        // البيانات المكانية: ربط بالمركز الإداري
        foreach (['facilities_data', 'sector_data'] as $tbl) {
            DB::statement("UPDATE {$tbl} t JOIN org_units o ON o.legacy_center_id = t.center_id AND o.type = 'center' SET t.org_unit_id = o.id");
        }
    }

    private function rebuildPaths(): void
    {
        $walk = function (?int $parentId, string $prefix, int $depth) use (&$walk) {
            $q = DB::table('org_units');
            $parentId === null ? $q->whereNull('parent_id') : $q->where('parent_id', $parentId);
            foreach ($q->pluck('id') as $id) {
                $path = $prefix . $id . '/';
                DB::table('org_units')->where('id', $id)->update(['path' => $path, 'depth' => $depth]);
                $walk($id, $path, $depth + 1);
            }
        };
        $walk(null, '/', 0);
    }

    public function down(): void
    {
        foreach (['sector_data', 'facilities_data'] as $tbl) {
            Schema::table($tbl, function (Blueprint $table) {
                $table->dropConstrainedForeignId('org_unit_id');
                $table->dropColumn(['latitude', 'longitude']);
            });
        }
        Schema::table('form_submissions', fn (Blueprint $t) => $t->dropConstrainedForeignId('org_unit_id'));
        Schema::dropIfExists('form_template_org_unit');
        Schema::table('form_templates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('org_unit_id');
            $table->dropColumn(['distribution', 'geo_lat_field', 'geo_lng_field']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('job_position_id');
            $table->dropConstrainedForeignId('org_unit_id');
            $table->dropColumn('data_scope');
        });
        Schema::dropIfExists('job_positions');
        Schema::dropIfExists('org_units');
    }
};
