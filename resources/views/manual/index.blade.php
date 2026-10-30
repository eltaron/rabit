@extends('layouts.app')

@section('title', 'دليل الاستخدام')
@section('page-title', 'دليل استخدام منصة رابط')
@section('page-subtitle', 'الدليل الرسمي لتداول البيانات المكانية والوصفية على مستوى محافظة كفر الشيخ')
@section('breadcrumb')
    <li class="breadcrumb-item active">دليل الاستخدام</li>
@endsection
@section('page-actions')
    <button type="button" class="btn btn-kfs-gold" onclick="window.print()"><i class="fas fa-print ml-1"></i>طباعة / حفظ PDF</button>
@endsection

@push('styles')
<style>
    .manual-grid { display: grid; grid-template-columns: 270px minmax(0, 1fr); gap: 1.75rem; align-items: start; }
    .manual-toc { position: sticky; top: calc(var(--idbar-height) + var(--navbar-height) + 1rem); max-height: calc(100vh - 140px); overflow: auto; }
    .manual-toc ol { list-style: none; margin: 0; padding: .5rem 0; counter-reset: toc; }
    .manual-toc li { counter-increment: toc; }
    .manual-toc a { display: flex; gap: .6rem; padding: .4rem 1.1rem; color: var(--text); font-size: .86rem; border-right: 2px solid transparent; }
    .manual-toc a::before { content: counter(toc, decimal-leading-zero); font-family: var(--ff-num); color: var(--egypt-gold); min-width: 1.6rem; }
    .manual-toc a:hover, .manual-toc a.active { color: var(--egypt-green); border-right-color: var(--egypt-gold); background: var(--papyrus); }
    .m-section { scroll-margin-top: calc(var(--idbar-height) + var(--navbar-height) + 16px); margin-bottom: 1.75rem; }
    .m-section .card-body { padding: 1.5rem 1.6rem; }
    .m-section h3 { font-size: 1.05rem; margin: 1.2rem 0 .5rem; }
    .m-section p, .m-section li { color: var(--text); }
    .m-steps { list-style: none; padding: 0; margin: .5rem 0 1rem; counter-reset: st; }
    .m-steps > li { counter-increment: st; position: relative; padding: .55rem 2.6rem .55rem 0; border-bottom: 1px dashed var(--line); }
    .m-steps > li:last-child { border-bottom: 0; }
    .m-steps > li::before { content: counter(st); position: absolute; right: 0; top: .5rem; width: 1.8rem; height: 1.8rem; border: 1px solid var(--egypt-gold); color: var(--egypt-green); border-radius: var(--r); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .85rem; }
    .m-note { border-right: 3px solid var(--egypt-gold); background: var(--papyrus); padding: .75rem 1rem; border-radius: var(--r); margin: .75rem 0; font-size: .88rem; }
    .m-warn { border-right-color: var(--danger); background: rgba(169,50,50,.06); }
    .m-path { font-family: var(--ff-ar); background: var(--papyrus); border: 1px solid var(--line); padding: .05rem .45rem; border-radius: 4px; font-size: .82rem; color: var(--egypt-green); white-space: nowrap; }
    .m-flow { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; margin: .75rem 0; }
    .m-flow span { border: 1px solid var(--egypt-gold-line); padding: .35rem .75rem; border-radius: var(--r); background: var(--white-soft); font-size: .84rem; font-weight: 600; color: var(--egypt-green); }
    .m-flow i { color: var(--egypt-gold); }
    .m-table th { background: var(--egypt-green); color: var(--white-soft); font-weight: 600; font-size: .8rem; }
    .m-table td { font-size: .85rem; }
    .m-table .yes { color: var(--success); } .m-table .no { color: var(--muted); }
    .m-cover { text-align: center; padding: 2.5rem 1rem; }
    .m-cover img { height: 70px; }
    @media (max-width: 991.98px) { .manual-grid { grid-template-columns: 1fr; } .manual-toc { position: static; max-height: none; } }
    @media print {
        .manual-toc { display: none; } .manual-grid { display: block; }
        .m-section { break-inside: avoid-page; page-break-after: always; box-shadow: none; }
        .m-cover { page-break-after: always; }
        @page { size: A4; margin: 16mm 14mm; }
    }
</style>
@endpush

@php
    $sections = [
        'intro' => 'نظرة عامة على المنصة',
        'login' => 'الدخول والأمان',
        'shell' => 'واجهة النظام والتنقل',
        'roles' => 'الأدوار والصلاحيات ونطاق البيانات',
        'structure' => 'الهيكل التنظيمي (الجهات والمراكز والقرى)',
        'positions' => 'الهيكل الوظيفي',
        'users' => 'إدارة الحسابات',
        'forms' => 'النماذج وتوجيهها للجهات',
        'submissions' => 'إدخال البيانات ودورة الاعتماد',
        'excel' => 'العمل بملفات Excel (تحميل القالب وإعادة الرفع)',
        'sector' => 'قواعد البيانات القطاعية',
        'map' => 'الخريطة التفاعلية',
        'decision' => 'مساعد دعم القرار للقيادات',
        'reports' => 'التقارير',
        'search' => 'البحث الشامل والإشعارات',
        'pwa' => 'التثبيت كتطبيق والعمل دون اتصال',
        'a11y' => 'إمكانية الوصول',
        'faq' => 'الأسئلة الشائعة وحل المشكلات',
    ];
@endphp

@section('content')
<div class="manual-grid">
    <aside class="card-kfs manual-toc no-print" aria-label="محتويات الدليل">
        <div class="card-header">محتويات الدليل</div>
        <ol>
            @foreach ($sections as $id => $label)
                <li><a href="#{{ $id }}">{{ $label }}</a></li>
            @endforeach
        </ol>
    </aside>

    <div>
        <div class="card-kfs m-cover d-none d-print-block">
            <img src="{{ asset('logo.png') }}" alt="رابط">
            <div class="egypt-cornice my-3"></div>
            <h1 style="font-size:1.8rem">دليل استخدام منصة رابط</h1>
            <p class="text-muted">محافظة كفر الشيخ — البوابة الإلكترونية الرسمية لتداول البيانات المكانية والوصفية</p>
            <p class="text-muted">الإصدار {{ config('app.version') }} — {{ now()->locale('ar')->translatedFormat('F Y') }}</p>
        </div>

        {{-- 01 --}}
        <section class="card-kfs m-section" id="intro">
            <div class="card-header">01 — نظرة عامة على المنصة</div>
            <div class="card-body">
                <p><strong>رابط</strong> هي المنصة الرسمية لمحافظة كفر الشيخ لتداول البيانات <strong>الوصفية</strong> (الجداول والنماذج والإحصاءات) و<strong>المكانية</strong> (المواقع والإحداثيات والطبقات) بين ديوان عام المحافظة والمراكز والمدن والأحياء والوحدات المحلية والقرى، ومديريات الخدمات والوزارات والهيئات التابعة، وتحويل هذه البيانات إلى معلومات تساعد القيادات في اتخاذ القرار.</p>
                <div class="m-flow" aria-label="مسار البيانات">
                    <span>البيانات</span><i class="fas fa-arrow-left"></i>
                    <span>المعلومات</span><i class="fas fa-arrow-left"></i>
                    <span>المعرفة</span><i class="fas fa-arrow-left"></i>
                    <span>القرار</span>
                </div>
                <h3>المكونات الرئيسية</h3>
                <ul>
                    <li><strong>الهيكل التنظيمي الموحد:</strong> شجرة واحدة تضم المحافظة والمراكز والمدن والأحياء والوحدات المحلية والقرى والعزب، والمديريات والهيئات والإدارات والأقسام.</li>
                    <li><strong>الهيكل الوظيفي:</strong> الوظائف داخل كل جهة وتبعيتها وشاغلوها والوظائف الشاغرة.</li>
                    <li><strong>النماذج:</strong> تصميم نماذج جمع البيانات وتوجيهها لجهات بعينها بمواعيد تسليم.</li>
                    <li><strong>ملفات Excel:</strong> لكل نموذج قالب Excel رسمي يُحمَّل ويُملأ ثم يُعاد رفعه للإضافة أو التعديل.</li>
                    <li><strong>الخريطة التفاعلية:</strong> عرض كل البيانات ذات الموقع على خريطة كفر الشيخ مع الاستعلام عنها.</li>
                    <li><strong>مساعد دعم القرار:</strong> مؤشرات وتنبيهات وتوصيات وإجابات على أسئلة القيادات من واقع البيانات.</li>
                    <li><strong>الحسابات والأدوار والصلاحيات:</strong> تحكم كامل في من يرى ماذا ومن يفعل ماذا.</li>
                </ul>
            </div>
        </section>

        {{-- 02 --}}
        <section class="card-kfs m-section" id="login">
            <div class="card-header">02 — الدخول والأمان</div>
            <div class="card-body">
                <ol class="m-steps">
                    <li>افتح رابط المنصة واضغط <span class="m-path">الدخول إلى المنصة</span>.</li>
                    <li>أدخل <strong>البريد الإلكتروني أو الرقم القومي</strong> وكلمة المرور ثم اضغط دخول.</li>
                    <li>إذا كانت المصادقة الثنائية مفعّلة، أدخل الرمز المكوّن من 6 أرقام من تطبيق المصادقة (Google Authenticator أو ما يماثله).</li>
                </ol>
                <h3>المصادقة الثنائية (إجبارية لكل الحسابات)</h3>
                <p>لا يمكن استخدام المنصة قبل تفعيل المصادقة الثنائية. عند أول دخول تظهر صفحة الإعداد تلقائياً:</p>
                <ol class="m-steps">
                    <li>ثبّت على هاتفك تطبيق <strong>Google Authenticator</strong> أو <strong>Microsoft Authenticator</strong>.</li>
                    <li>من التطبيق اختر «إضافة حساب» ثم «مسح رمز QR»، ووجّه الكاميرا إلى المربع الظاهر في الخطوة 2 بصفحة الإعداد. إن تعذر المسح اضغط «أدخل المفتاح يدوياً» وانسخ المفتاح.</li>
                    <li>اكتب الرمز المكوّن من 6 أرقام الظاهر في التطبيق (يتغير كل 30 ثانية).</li>
                    <li>تظهر <strong>8 أكواد استرداد</strong> مرة واحدة فقط: انسخها أو نزّلها أو اطبعها واحفظها بعيداً عن الهاتف، ثم اضغط «متابعة».</li>
                </ol>
                <p>في كل دخول بعد ذلك: البريد/الرقم القومي وكلمة المرور، ثم الرمز من التطبيق.</p>
                <ul>
                    <li><strong>فقدت هاتفك؟</strong> في صفحة التحقق اضغط «استخدم كود استرداد» وأدخل أحد الأكواد (كل كود مرة واحدة)، ثم غيّر جهاز المصادقة من الملف الشخصي.</li>
                    <li><strong>فقدت الهاتف والأكواد؟</strong> اطلب من مدير النظام «إعادة ضبط المصادقة الثنائية» لحسابك من صفحة الحسابات، وستُطلب منك إعادة الإعداد عند الدخول.</li>
                    <li><strong>هاتف جديد:</strong> الملف الشخصي ← «تغيير جهاز المصادقة».</li>
                    <li><strong>أكواد جديدة:</strong> الملف الشخصي ← «توليد أكواد استرداد جديدة» (تُلغى الأكواد القديمة).</li>
                </ul>
                <div class="m-note">إذا ظهر أن الرمز غير صحيح فتأكد أن ساعة الهاتف مضبوطة تلقائياً. بعد 5 محاولات خاطئة يُوقف التحقق مؤقتاً لبضع دقائق.</div>
                <h3>تغيير كلمة المرور</h3>
                <p>من <span class="m-path">الملف الشخصي</span> ← قسم كلمة المرور. استخدم 8 أحرف على الأقل تجمع حروفاً وأرقاماً.</p>
                <div class="m-note m-warn"><strong>تنبيه أمني:</strong> لا تشارك حسابك مع أحد، وسجّل الخروج دائماً عند استخدام جهاز مشترك. كل العمليات تُسجَّل في سجل التدقيق باسم صاحب الحساب.</div>
            </div>
        </section>

        {{-- 03 --}}
        <section class="card-kfs m-section" id="shell">
            <div class="card-header">03 — واجهة النظام والتنقل</div>
            <div class="card-body">
                <ul>
                    <li><strong>شريط الهوية الحكومية</strong> (أعلى الصفحة): التاريخ، وأزرار تكبير/تصغير الخط.</li>
                    <li><strong>القائمة الجانبية</strong> (يمين الشاشة): تظهر فيها الأقسام المسموح لك بها فقط حسب صلاحياتك. على الهاتف تُفتح بزر القائمة <i class="fas fa-bars"></i>.</li>
                    <li><strong>شريط الأدوات:</strong> البحث الشامل (اختصار <span class="en">Ctrl + K</span>)، الخريطة، الإشعارات، وقائمة الحساب.</li>
                    <li><strong>رأس الصفحة:</strong> مسار التنقل (الرئيسية / القسم / الصفحة) واسم الصفحة وأزرار الإجراءات الخاصة بها.</li>
                    <li><strong>الأزرار العائمة</strong> (يسار أسفل): إمكانية الوصول، والعودة لأعلى الصفحة.</li>
                </ul>
                <h3>لوحة التحكم</h3>
                <p>تختلف لوحة التحكم حسب دورك: المحافظ والقيادات يرون لوحة تنفيذية بالمؤشرات العامة ورابطاً مباشراً لمساعد دعم القرار، ومديرو الجهات يرون أعمال جهاتهم والنماذج الموجهة إليهم ومواعيد تسليمها، ومدخلو البيانات يرون المهام المسندة إليهم.</p>
            </div>
        </section>

        {{-- 04 --}}
        <section class="card-kfs m-section" id="roles">
            <div class="card-header">04 — الأدوار والصلاحيات ونطاق البيانات</div>
            <div class="card-body">
                <p>تتحدد قدرات كل مستخدم بثلاثة عناصر: <strong>الدور</strong> (مجموعة صلاحيات)، و<strong>الصلاحيات الإضافية</strong> المباشرة إن وجدت، و<strong>نطاق البيانات</strong> الذي يحدد الجهات التي يرى بياناتها.</p>
                <h3>الأدوار الافتراضية</h3>
                <div class="table-responsive">
                    <table class="table m-table">
                        <thead><tr><th>الدور</th><th>الاستخدام المعتاد</th><th>النطاق الافتراضي</th></tr></thead>
                        <tbody>
                            <tr><td>مدير النظام</td><td>كل الصلاحيات وإدارة المنصة بالكامل</td><td>كل المحافظة</td></tr>
                            <tr><td>السيد المحافظ</td><td>اللوحة التنفيذية، دعم القرار، اعتماد التقارير</td><td>كل المحافظة</td></tr>
                            <tr><td>القيادات التنفيذية</td><td>نواب المحافظ ورؤساء المراكز ووكلاء الوزارات: متابعة ودعم قرار</td><td>جهته والتابعة</td></tr>
                            <tr><td>مدير / رئيس جهة</td><td>تصميم النماذج وتوجيهها، المراجعة والاعتماد، الهيكل الوظيفي لجهته</td><td>جهته والتابعة</td></tr>
                            <tr><td>مراجع بيانات</td><td>مراجعة الإرسالات وإعادتها للتعديل</td><td>جهته والتابعة</td></tr>
                            <tr><td>مدخل بيانات</td><td>إدخال البيانات يدوياً أو عبر Excel</td><td>جهته فقط</td></tr>
                            <tr><td>السلطة المختصة</td><td>استلام التقارير ومراجعتها</td><td>جهته والتابعة</td></tr>
                            <tr><td>مسؤول نظم المعلومات الجغرافية</td><td>إدارة الطبقات والبيانات المكانية وقواعد البيانات القطاعية</td><td>كل المحافظة</td></tr>
                            <tr><td>مستعرض</td><td>قراءة فقط</td><td>جهته فقط</td></tr>
                        </tbody>
                    </table>
                </div>
                <h3>نطاق البيانات</h3>
                <ul>
                    <li><strong>بياناته فقط:</strong> ما أنشأه المستخدم أو أُسند إليه.</li>
                    <li><strong>جهته فقط:</strong> بيانات الجهة المسكّن عليها.</li>
                    <li><strong>جهته والجهات التابعة:</strong> مثال: رئيس مركز دسوق يرى بيانات المركز والمدينة وكل الوحدات المحلية والقرى التابعة.</li>
                    <li><strong>كل المحافظة:</strong> لمن لديه صلاحية «الاطلاع على بيانات كل المحافظة».</li>
                </ul>
                <h3>تعديل صلاحيات دور</h3>
                <ol class="m-steps">
                    <li>افتح <span class="m-path">الحسابات والأمان ← الأدوار والصلاحيات</span>.</li>
                    <li>اضغط تعديل على الدور، أو أنشئ دوراً جديداً باسم واضح.</li>
                    <li>فعّل أو ألغِ الصلاحيات داخل كل مجموعة (يمكن تحديد المجموعة كاملة بضغطة واحدة)، ثم احفظ.</li>
                    <li>للتعديل السريع لعدة أدوار مرة واحدة استخدم <strong>مصفوفة الصلاحيات</strong> (الأدوار × الصلاحيات).</li>
                </ol>
                <div class="m-note">دور «مدير النظام» يحتفظ دائماً بكل الصلاحيات ولا يمكن حذفه، ولا يُحذف أي دور مسند لمستخدمين.</div>
            </div>
        </section>

        {{-- 05 --}}
        <section class="card-kfs m-section" id="structure">
            <div class="card-header">05 — الهيكل التنظيمي (الجهات والمراكز والقرى)</div>
            <div class="card-body">
                <p>من <span class="m-path">الهيكل ← الهيكل التنظيمي</span> تظهر شجرة المحافظة كاملة. كل عنصر في الشجرة «جهة» لها نوع:</p>
                <p class="d-flex flex-wrap gap-2">
                    @foreach (\App\Models\OrgUnit::TYPES as $t => $l)
                        <span class="badge badge-green"><i class="fas {{ \App\Models\OrgUnit::ICONS[$t] ?? 'fa-circle' }} ml-1"></i>{{ $l }}</span>
                    @endforeach
                </p>
                <h3>إضافة جهة</h3>
                <ol class="m-steps">
                    <li>اضغط <span class="m-path">إضافة جهة</span> أو زر الإضافة بجوار الجهة الأم في الشجرة.</li>
                    <li>اختر الجهة الأم والنوع واكتب الاسم والكود (اختياري لكنه مفيد في ملفات Excel).</li>
                    <li>أدخل الإحداثيات (خط العرض وخط الطول) لتظهر الجهة على الخريطة، وبيانات رئيس الجهة ووسائل التواصل.</li>
                    <li>احفظ. يمكن نقل الجهة لاحقاً لأب آخر وستنتقل معها كل الجهات التابعة لها تلقائياً.</li>
                </ol>
                <h3>الإضافة الجماعية</h3>
                <ul>
                    <li><strong>استيراد التقسيم الإداري:</strong> زر يقرأ ملف «التقسيم الإداري» الرسمي وينشئ المدن والأحياء والوحدات المحلية والقرى التوابع والعزب تحت كل مركز (آمن للتكرار، لا ينشئ مكررات).</li>
                    <li><strong>Excel:</strong> حمّل قالب الجهات، املأه (الجهة الأم بالاسم أو الكود)، ثم ارفعه للإضافة أو التحديث.</li>
                </ul>
                <div class="m-note">لا يمكن حذف جهة لها جهات تابعة أو مستخدمون مسكّنون عليها؛ انقلهم أولاً.</div>
            </div>
        </section>

        {{-- 06 --}}
        <section class="card-kfs m-section" id="positions">
            <div class="card-header">06 — الهيكل الوظيفي</div>
            <div class="card-body">
                <ol class="m-steps">
                    <li>افتح <span class="m-path">الهيكل ← الهيكل الوظيفي</span> واختر الجهة من الشجرة الجانبية.</li>
                    <li>اضغط <span class="m-path">إضافة وظيفة</span>: المسمى، الدرجة الوظيفية، الفئة (قيادية / إشرافية / تخصصية / تنفيذية / معاونة)، الوظيفة الرئيسة (يتبع)، وعدد الدرجات المعتمدة.</li>
                    <li>يُرسم الهيكل تلقائياً كمخطط شجري يوضح التبعية، وتظهر الوظائف الشاغرة بعلامة «شاغرة».</li>
                    <li>لتسكين موظف: اضغط <span class="m-path">تسكين</span> على الوظيفة واختر الحساب. يمكن إلغاء التسكين بنفس الطريقة.</li>
                </ol>
                <p>يمكن أيضاً تصدير الهيكل الوظيفي إلى Excel أو استيراده دفعة واحدة من قالب Excel.</p>
            </div>
        </section>

        {{-- 07 --}}
        <section class="card-kfs m-section" id="users">
            <div class="card-header">07 — إدارة الحسابات</div>
            <div class="card-body">
                <ol class="m-steps">
                    <li>افتح <span class="m-path">الحسابات والأمان ← الحسابات</span> ثم <span class="m-path">إضافة حساب</span>.</li>
                    <li>أدخل الاسم والبريد والرقم القومي والهاتف وكلمة مرور أولية.</li>
                    <li>اختر <strong>الجهة</strong> من الهيكل التنظيمي، و<strong>الوظيفة</strong> إن وجدت، و<strong>الدور</strong> (أو أكثر)، و<strong>نطاق البيانات</strong>.</li>
                    <li>يمكن منح صلاحيات إضافية مباشرة لحساب بعينه دون تغيير دوره.</li>
                    <li>احفظ، وسلّم بيانات الدخول لصاحب الحساب واطلب تغيير كلمة المرور عند أول دخول.</li>
                </ol>
                <ul>
                    <li><strong>إيقاف / تفعيل:</strong> زر الإيقاف يمنع الدخول فوراً دون حذف البيانات.</li>
                    <li><strong>إعادة تعيين كلمة المرور:</strong> تُنشأ كلمة مرور مؤقتة تظهر مرة واحدة فقط.</li>
                    <li><strong>الاستيراد الجماعي:</strong> قالب Excel للحسابات (الجهة والدور بالاسم أو الكود)؛ الحساب الموجود بنفس البريد أو الرقم القومي يُحدَّث.</li>
                    <li><strong>البحث والتصفية:</strong> بالاسم، الدور، الجهة (مع التابعة)، والحالة.</li>
                </ul>
            </div>
        </section>

        {{-- 08 --}}
        <section class="card-kfs m-section" id="forms">
            <div class="card-header">08 — النماذج وتوجيهها للجهات (تداول البيانات)</div>
            <div class="card-body">
                <ol class="m-steps">
                    <li>من <span class="m-path">تداول البيانات ← النماذج</span> اضغط <span class="m-path">نموذج جديد</span>.</li>
                    <li>اكتب اسم النموذج ووصفه، واختر <strong>الجهة المالكة</strong>.</li>
                    <li>أضف الحقول: نص، نص طويل، رقم، بريد، تاريخ، قائمة اختيار، اختيار متعدد، صورة، مربع اختيار. لكل حقل: إلزامي أم لا، نص مساعد، قيمة افتراضية، وخيارات القوائم.</li>
                    <li>حدد <strong>طريقة التوجيه</strong>:
                        <ul>
                            <li><em>عام:</em> متاح لكل الجهات.</li>
                            <li><em>موجّه:</em> لجهات محددة (مثلاً كل الوحدات المحلية بمركز بيلا) مع خيار «يشمل الجهات التابعة» وتاريخ تسليم.</li>
                            <li><em>للجهة المالكة فقط.</em></li>
                        </ul>
                    </li>
                    <li>إن كان النموذج يحتوي إحداثيات، اربط حقلي <strong>خط العرض</strong> و<strong>خط الطول</strong> لتظهر بياناته على الخريطة.</li>
                    <li>احفظ وفعّل النموذج. تتابع صفحة النموذج نسبة الاستجابة لكل جهة موجّه إليها (أرسلت / اعتُمدت / متأخرة).</li>
                </ol>
            </div>
        </section>

        {{-- 09 --}}
        <section class="card-kfs m-section" id="submissions">
            <div class="card-header">09 — إدخال البيانات ودورة الاعتماد</div>
            <div class="card-body">
                <div class="m-flow">
                    <span>مسودة</span><i class="fas fa-arrow-left"></i><span>إدخال البيانات</span><i class="fas fa-arrow-left"></i>
                    <span>قيد المراجعة</span><i class="fas fa-arrow-left"></i><span>معتمد</span>
                </div>
                <ol class="m-steps">
                    <li>من <span class="m-path">الإرسالات والبيانات</span> اضغط <span class="m-path">إرسال جديد</span> واختر النموذج (تظهر فقط النماذج المتاحة لجهتك).</li>
                    <li>أدخل البيانات في الجدول: كل صف سجل مستقل؛ أضف صفوفاً أو كرّر صفاً أو احذفه.</li>
                    <li>اضغط <strong>حفظ</strong> للحفظ كمسودة، أو <strong>تقديم للمراجعة</strong> عند الانتهاء.</li>
                    <li>المراجع يعتمد أو يرفض أو يعيد الإرسال للتعديل مع ملاحظات تظهر لك وفي الإشعارات.</li>
                    <li>بعد الاعتماد يمكن إنشاء تقرير من البيانات وإرساله للسلطة المختصة.</li>
                </ol>
                <p>يُسجَّل كل إجراء في <strong>الخط الزمني</strong> للإرسال (من قام به ومتى وما الملاحظات).</p>
            </div>
        </section>

        {{-- 10 --}}
        <section class="card-kfs m-section" id="excel">
            <div class="card-header">10 — العمل بملفات Excel (تحميل القالب وإعادة الرفع)</div>
            <div class="card-body">
                <p>لكل نموذج في المنصة <strong>قالب Excel رسمي</strong> مطابق لحقوله. هذه أسرع طريقة لإدخال أو تعديل عدد كبير من السجلات.</p>
                <div class="m-flow"><span>حمّل القالب</span><i class="fas fa-arrow-left"></i><span>املأ البيانات</span><i class="fas fa-arrow-left"></i><span>ارفع الملف</span><i class="fas fa-arrow-left"></i><span>راجع النتيجة</span></div>
                <h3>إضافة بيانات جديدة</h3>
                <ol class="m-steps">
                    <li>افتح <span class="m-path">مركز ملفات Excel</span> (أو صفحة النموذج) واضغط <span class="m-path">تحميل القالب</span>.</li>
                    <li>املأ البيانات بدءاً من <strong>الصف الخامس</strong>. لا تعدّل صفوف العناوين ولا تحذف الأعمدة. الأعمدة المعلّمة بـ (*) إلزامية، والأعمدة ذات القوائم تقبل القيم المعروضة فقط.</li>
                    <li>اترك عمود <strong>رقم الصف</strong> فارغاً للسجلات الجديدة.</li>
                    <li>ارفع الملف بزر <span class="m-path">رفع ملف</span> (أو اسحبه وأفلته) واختر نوع العملية.</li>
                </ol>
                <h3>تعديل بيانات موجودة</h3>
                <ol class="m-steps">
                    <li>من صفحة الإرسال اضغط <span class="m-path">تصدير البيانات</span>؛ يُنزَّل الملف ببياناته الحالية ورقم كل صف.</li>
                    <li>عدّل القيم المطلوبة، وأضف صفوفاً جديدة في الأسفل (برقم صف فارغ).</li>
                    <li>ارفع الملف واختر العملية <strong>تحديث</strong>.</li>
                </ol>
                <h3>أنواع العمليات عند الرفع</h3>
                <ul>
                    <li><strong>إضافة:</strong> تُضاف كل الصفوف كسجلات جديدة.</li>
                    <li><strong>تحديث:</strong> الصفوف التي تحمل رقم صف تُعدَّل، والصفوف بلا رقم تُضاف.</li>
                    <li><strong>استبدال:</strong> تُحذف بيانات الإرسال الحالية وتحل محلها بيانات الملف.</li>
                </ul>
                <div class="m-note">يفحص النظام كل صف قبل الحفظ (الحقول الإلزامية، الأرقام، التواريخ، البريد، قيم القوائم). عند وجود أخطاء يعرض جدولاً برقم الصف والعمود وسبب الخطأ <strong>ولا يحفظ شيئاً</strong> حتى تصححها، إلا إذا اخترت «تجاهل الصفوف غير الصحيحة».</div>
                <p>يمكن أيضاً رفع ملف Excel غير مأخوذ من القالب بشرط أن تطابق رؤوس أعمدته أسماء حقول النموذج.</p>
            </div>
        </section>

        {{-- 11 --}}
        <section class="card-kfs m-section" id="sector">
            <div class="card-header">11 — قواعد البيانات القطاعية</div>
            <div class="card-body">
                <p>من <span class="m-path">قواعد البيانات القطاعية</span> تُرفع ملفات القطاعات الكبيرة (التعليم، الصحة، التموين، الاستثمار، السكان، نوتة المعلومات: المساجد، المدارس، محطات المياه والكهرباء، مراكز الشباب، الكباري، المحاكم … ، المركبات والمعدات، خطط الصيانة، بيانات القيادة الاستراتيجية).</p>
                <ol class="m-steps">
                    <li>اختر القطاع أو النموذج المطلوب.</li>
                    <li>ارفع ملف Excel بصيغته المعتادة؛ يتعرف النظام على الأعمدة والمراكز تلقائياً.</li>
                    <li>تُربط السجلات بالمركز، وتُحوَّل الإحداثيات (X/Y) إلى مواقع على الخريطة تلقائياً.</li>
                </ol>
            </div>
        </section>

        {{-- 12 --}}
        <section class="card-kfs m-section" id="map">
            <div class="card-header">12 — الخريطة التفاعلية</div>
            <div class="card-body">
                <p>تعرض <span class="m-path">خريطة كفر الشيخ التفاعلية</span> حدود المحافظة والمراكز والقرى وكل البيانات التي لها موقع: المرافق والمنشآت، بيانات القطاعات، وبيانات النماذج التي تحتوي إحداثيات — كلٌ حسب نطاق بياناتك.</p>
                <ul>
                    <li><strong>الطبقات:</strong> من اللوحة الجانبية فعّل أو ألغِ أي طبقة، ويظهر بجوارها عدد عناصرها ولونها.</li>
                    <li><strong>البحث:</strong> اكتب اسم منشأة أو قرية أو عنوان، واختر من الاقتراحات لينتقل إليه.</li>
                    <li><strong>الاستعلام:</strong> اضغط على أي نقطة لعرض بطاقة بكل بياناتها الوصفية، مع نسخ الإحداثيات أو فتح السجل الأصلي.</li>
                    <li><strong>المراكز:</strong> اضغط على مركز لعرض ملخص مؤشراته والجهات التابعة له، واختر مؤشراً لتلوين المراكز حسبه.</li>
                    <li><strong>جدول البيانات:</strong> اعرض العناصر الظاهرة في جدول وصدّرها إلى Excel.</li>
                    <li><strong>خرائط الأساس:</strong> خريطة هادئة أو صور القمر الصناعي، مع تحديد موقعي وملء الشاشة.</li>
                </ul>
                <div class="m-note">يمكن مشاركة رابط الخريطة مفتوحاً على جهة أو منشأة بعينها؛ المستلم يرى فقط ما يسمح به نطاقه.</div>
            </div>
        </section>

        {{-- 13 --}}
        <section class="card-kfs m-section" id="decision">
            <div class="card-header">13 — مساعد دعم القرار للقيادات</div>
            <div class="card-body">
                <p>مخصص للسيد المحافظ والقيادات. يجمع البيانات من كل مصادر المنصة ويحولها إلى:</p>
                <ul>
                    <li><strong>مؤشرات رئيسية</strong> على مستوى المحافظة أو نطاقك.</li>
                    <li><strong>تنبيهات مرتبة حسب الأولوية</strong> (حرجة / تحذير / معلومة / إيجابية) مع الأرقام الداعمة وتوصية عملية لكل تنبيه.</li>
                    <li><strong>مقارنة المراكز</strong> حسب المؤشر المختار.</li>
                    <li><strong>المساعد الذكي:</strong> اسأل بلغة طبيعية مثل: «ما المراكز الأقل في عدد المدارس؟»، «ما النماذج المتأخرة؟»، «لخص وضع المركبات»، «ماذا تقترح لتحسين تغطية محطات المياه؟». تأتي الإجابة من واقع بيانات المنصة مع الأرقام والبدائل والتوصية والمخاطر، ويُذكر مصدر الإجابة.</li>
                    <li><strong>الموجز التنفيذي:</strong> صفحة قابلة للطباعة تلخص الوضع الحالي لعرضها في الاجتماعات.</li>
                </ul>
                <div class="m-note">المساعد يوصي ولا يقرر؛ القرار النهائي للقيادة المختصة. إذا كانت البيانات ناقصة يوضح المساعد ذلك صراحة بدلاً من التخمين — لذا فإن دقة الإجابات تعتمد على اكتمال وتحديث البيانات من الجهات.</div>
            </div>
        </section>

        {{-- 14 --}}
        <section class="card-kfs m-section" id="reports">
            <div class="card-header">14 — التقارير</div>
            <div class="card-body">
                <ol class="m-steps">
                    <li>من <span class="m-path">التقارير</span> أنشئ تقريراً جديداً أو من إرسال معتمد.</li>
                    <li>اختر الحقول المطلوبة، ويمكن توليد نص تحليلي بالذكاء الاصطناعي ثم تعديله.</li>
                    <li>قدّم التقرير للاعتماد؛ بعد اعتماده يمكن إرساله للسلطة المختصة.</li>
                    <li>نزّل التقرير بصيغة PDF أو Excel، وراجع الإصدارات السابقة وقارن بينها أو استرجع أحدها.</li>
                </ol>
            </div>
        </section>

        {{-- 15 --}}
        <section class="card-kfs m-section" id="search">
            <div class="card-header">15 — البحث الشامل والإشعارات</div>
            <div class="card-body">
                <p><strong>البحث الشامل</strong> في شريط الأدوات (أو <span class="en">Ctrl + K</span>) يبحث في الجهات والقرى، والنماذج، والإرسالات بالرقم المرجعي، والمنشآت، والحسابات والوظائف (لمن يملك صلاحيتها).</p>
                <p><strong>الإشعارات</strong> (أيقونة الجرس) تنبهك بالمهام المسندة، وطلبات التعديل، والاعتماد والرفض، والتقارير المرسلة إليك.</p>
            </div>
        </section>

        {{-- 16 --}}
        <section class="card-kfs m-section" id="pwa">
            <div class="card-header">16 — التثبيت كتطبيق والعمل دون اتصال</div>
            <div class="card-body">
                <ul>
                    <li><strong>على الكمبيوتر (Chrome / Edge):</strong> اضغط أيقونة التثبيت في شريط العنوان أو زر «تثبيت التطبيق» داخل المنصة.</li>
                    <li><strong>على Android:</strong> من قائمة المتصفح اختر «تثبيت التطبيق» أو «إضافة إلى الشاشة الرئيسية».</li>
                    <li><strong>على iPhone:</strong> من Safari اضغط مشاركة ثم «إضافة إلى الشاشة الرئيسية».</li>
                </ul>
                <p>تُحفظ ملفات الواجهة على الجهاز لتسريع الفتح. عند انقطاع الإنترنت تظهر صفحة «غير متصل» بدلاً من الخطأ. <strong>لا تُحفظ أي بيانات حساسة أو صفحات إدارية على الجهاز</strong>، ويتم تحديث التطبيق تلقائياً عند صدور إصدار جديد (تظهر رسالة «يتوفر إصدار جديد» للتحديث).</p>
            </div>
        </section>

        {{-- 17 --}}
        <section class="card-kfs m-section" id="a11y">
            <div class="card-header">17 — إمكانية الوصول</div>
            <div class="card-body">
                <p>زر <i class="fas fa-universal-access text-gold"></i> العائم يفتح لوحة إمكانية الوصول: تكبير وتصغير الخط، التباين العالي، وإيقاف الحركة. تُحفظ اختياراتك على جهازك. المنصة تدعم التنقل بلوحة المفاتيح (Tab) وقارئات الشاشة ورابط «تخطَّ إلى المحتوى».</p>
            </div>
        </section>

        {{-- 18 --}}
        <section class="card-kfs m-section" id="faq">
            <div class="card-header">18 — الأسئلة الشائعة وحل المشكلات</div>
            <div class="card-body">
                <div class="accordion" id="faqAcc">
                    @foreach ([
                        ['لا أرى قسماً معيناً في القائمة', 'القائمة تعرض فقط ما تسمح به صلاحياتك. اطلب من مدير النظام إضافة الصلاحية لدورك أو لحسابك مباشرة.'],
                        ['لا أرى بيانات جهة أخرى', 'نطاق بياناتك يحدد الجهات المسموح لك برؤيتها. يمكن لمدير النظام توسيع النطاق إلى «جهته والجهات التابعة» أو «كل المحافظة».'],
                        ['رفض النظام ملف Excel', 'راجع جدول الأخطاء: رقم الصف والعمود وسبب الرفض. تأكد أنك لم تحذف صفوف العناوين، وأن القيم في الأعمدة ذات القوائم من القيم المسموح بها، وأن التواريخ بصيغة تاريخ صحيحة.'],
                        ['لا تظهر بياناتي على الخريطة', 'يجب أن يحتوي السجل على إحداثيات صحيحة داخل حدود المحافظة، وأن يكون النموذج مربوطاً بحقلي خط العرض وخط الطول.'],
                        ['المساعد الذكي يقول إن البيانات غير كافية', 'المساعد يعتمد على بيانات المنصة فقط. اطلب من الجهات استكمال النماذج الموجهة إليها وتحديث قواعد البيانات القطاعية.'],
                        ['نسيت كلمة المرور', 'تواصل مع مدير النظام لإعادة تعيينها؛ ستحصل على كلمة مرور مؤقتة غيّرها فور الدخول.'],
                        ['تظهر رسالة انتهاء الجلسة (419)', 'انتهت صلاحية الصفحة لطول فترة عدم النشاط. حدّث الصفحة وأعد المحاولة.'],
                    ] as $i => [$q, $a])
                        <div class="accordion-item">
                            <h4 class="accordion-header">
                                <button class="accordion-button {{ $i ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#faq{{ $i }}" aria-expanded="{{ $i ? 'false' : 'true' }}" style="font-size:.9rem;font-weight:700;color:var(--egypt-green)">{{ $q }}</button>
                            </h4>
                            <div id="faq{{ $i }}" class="accordion-collapse collapse {{ $i ? '' : 'show' }} d-print-block" data-bs-parent="#faqAcc">
                                <div class="accordion-body">{{ $a }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="m-note mt-3">للدعم الفني تواصل مع إدارة نظم المعلومات والتحول الرقمي بديوان عام المحافظة.</div>
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const links = document.querySelectorAll('.manual-toc a');
        if (!('IntersectionObserver' in window) || !links.length) return;
        const io = new IntersectionObserver(es => es.forEach(e => {
            if (e.isIntersecting) links.forEach(a => a.classList.toggle('active', a.getAttribute('href') === '#' + e.target.id));
        }), { rootMargin: '-30% 0px -60% 0px' });
        document.querySelectorAll('.m-section').forEach(s => io.observe(s));
    })();
</script>
@endpush
