{{-- النماذج الموجهة لجهتي + آخر النشاط --}}
<div class="row g-4">
    <div class="col-lg-6" data-reveal>
        <div class="card-kfs h-100">
            <div class="card-header"><i class="fas fa-calendar-check ml-2"></i>النماذج الموجهة لجهتك</div>
            @if ($dueForms->isEmpty())
                <div class="empty-state py-4">
                    <span class="eg-icon"><i class="far fa-calendar"></i></span>
                    <p class="mb-0 small">لا توجد نماذج موجهة لجهتك بمواعيد تسليم حالياً.</p>
                </div>
            @else
                <ul class="dash-list">
                    @foreach ($dueForms as $f)
                        <li>
                            <span class="eg-icon sm"><i class="fas fa-file-lines"></i></span>
                            <div class="flex-grow-1">
                                <div class="t">{{ $f->name }}</div>
                                <div class="s">
                                    @if ($f->due_date) آخر موعد: {{ \Illuminate\Support\Carbon::parse($f->due_date)->format('Y/m/d') }} @else بدون موعد محدد @endif
                                </div>
                            </div>
                            @php
                                $txt = $f->submitted ? 'تم الإرسال' : ($f->days_left === null ? 'مفتوح' : ($f->days_left < 0 ? 'متأخر ' . abs($f->days_left) . ' يوم' : ($f->days_left === 0 ? 'اليوم' : 'متبقٍ ' . $f->days_left . ' يوم')));
                            @endphp
                            <span class="badge-status status-{{ $f->state }}">{{ $txt }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
    <div class="col-lg-6" data-reveal data-delay=".08">
        <div class="card-kfs h-100">
            <div class="card-header"><i class="fas fa-clock-rotate-left ml-2"></i>آخر النشاط</div>
            @if (empty($recentActivity))
                <div class="empty-state py-4">
                    <span class="eg-icon"><i class="fas fa-clock"></i></span>
                    <p class="mb-0 small">لا يوجد نشاط مسجل بعد.</p>
                </div>
            @else
                <ul class="dash-list">
                    @foreach ($recentActivity as $a)
                        <li>
                            <span class="eg-icon sm"><i class="fas {{ $a['icon'] }}"></i></span>
                            <div class="flex-grow-1">
                                <div class="t">{{ \Illuminate\Support\Str::limit($a['description'], 80) }}</div>
                                <div class="s">{{ $a['user'] }} · {{ $a['date'] }}</div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
