<?php

namespace App\Services;

use App\Models\ChatbotMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * المساعد الذكي العام للمنصة: إرشاد الاستخدام + أسئلة البيانات (ضمن نطاق المستخدم).
 * يستخدم نفس طبقة المزودين (Claude / Ollama) مع محرك داخلي احتياطي.
 */
class PlatformAssistant
{
    public const CHANNEL = 'general';

    public const SUGGESTIONS = [
        'كيف أحمّل قالب Excel لنموذج وأعيد رفعه؟',
        'كيف أرسل بيانات للمراجعة؟',
        'كيف أستخدم الخريطة التفاعلية؟',
        'أعطني ملخصاً لبيانات المحافظة',
    ];

    /** دليل مختصر للمنصة: intent keywords => answer */
    protected const HELP = [
        [['excel', 'اكسل', 'إكسل', 'قالب', 'تمبلت', 'رفع', 'استيراد', 'تصدير'], "### التعامل مع ملفات Excel\n- من **مركز ملفات Excel** أو من صفحة النموذج اضغط **تحميل القالب** لتحصل على ملف بأعمدة النموذج نفسه.\n- املأ البيانات بدءاً من الصف الخامس دون تعديل صفوف العناوين؛ الأعمدة المعلّمة بـ * إلزامية.\n- لتعديل بيانات موجودة: افتح الإرسال واضغط **تصدير البيانات**، عدّل الملف ثم **أعد رفعه**؛ الصفوف التي تحمل رقم السجل تُحدَّث والصفوف الجديدة تُضاف.\n- بعد الرفع يعرض النظام ملخصاً بعدد الصفوف المضافة والمعدلة والأخطاء إن وجدت."],
        [['ارسل', 'أرسل', 'تقديم للمراجعه', 'اعتماد', 'دوره الارسال'], "### دورة الإرسال والاعتماد\n1. من **الإرسالات والبيانات** اضغط **إرسال جديد** واختر النموذج.\n2. أدخل البيانات صفاً صفاً أو ارفع ملف Excel.\n3. اضغط **تقديم للمراجعة**؛ يصل الإشعار للمراجع المختص.\n4. يعتمد المراجع الإرسال أو يعيده للتعديل مع ملاحظات تظهر لك في سجل سير العمل."],
        [['خريطه', 'خريطة', 'طبقات', 'مكاني', 'احداثيات', 'إحداثيات'], "### الخريطة التفاعلية\n- افتح **خريطة كفر الشيخ التفاعلية** من القائمة.\n- فعّل الطبقات المطلوبة (المراكز، القرى، المدارس، المنشآت الصحية…) من لوحة الطبقات.\n- اضغط على أي نقطة أو مركز لعرض بياناته الوصفية، واستخدم البحث للوصول لمكان بالاسم."],
        [['نموذج جديد', 'انشاء نموذج', 'إنشاء نموذج', 'حقول', 'توجيه'], "### إنشاء النماذج وتوجيهها\n- من **النماذج** اضغط **نموذج جديد** وأضف الحقول ونوع كل حقل.\n- حدّد الجهة المالكة، ثم وجّه النموذج للجهات المطلوبة مع موعد التسليم.\n- يمكن تحديد حقلي خط العرض والطول ليظهر النموذج كطبقة على الخريطة."],
        [['كلمه المرور', 'كلمة المرور', 'حسابي', 'صلاحيه', 'صلاحية', 'المصادقه', 'المصادقة'], "### الحساب والصلاحيات\n- غيّر كلمة المرور وفعّل المصادقة الثنائية من **الملف الشخصي**.\n- الصلاحيات والأدوار ونطاق البيانات يحددها مدير النظام من **الحسابات** و**الأدوار والصلاحيات**؛ تواصل معه لطلب صلاحية إضافية."],
        [['هيكل', 'وظيفي', 'تنظيمي'], "### الهيكل التنظيمي والوظيفي\n- **الهيكل التنظيمي** يعرض شجرة المحافظة: المراكز والمدن والقرى والمديريات والهيئات.\n- **الهيكل الوظيفي** يعرض الوظائف وتبعيتها وشاغليها، ويمكن استيراده وتصديره عبر Excel."],
        [['تقرير', 'تقارير'], "### التقارير\n- من **التقارير** أنشئ تقريراً من إرسال معتمد، واختر الحقول، ويمكن توليد صياغة رسمية آلياً.\n- يمر التقرير بالاعتماد ثم الإرسال، ويمكن تصديره PDF أو Excel."],
    ];

    public function __construct(protected AiService $ai, protected DecisionSupportService $decision) {}

    public static function historyScope(Builder $q): Builder
    {
        return $q->where(fn ($w) => $w->whereNull('context')->orWhere('context->channel', self::CHANNEL));
    }

    /**
     * @return array{content:string, provider:string, provider_label:string, model:?string}
     */
    public function answer(User $user, string $question, array $history = []): array
    {
        $canData = $user->can('view_dashboard');
        $system = "أنت المساعد الذكي الرسمي لمنصة \"رابط\" — البوابة الإلكترونية لتداول البيانات المكانية والوصفية بمحافظة كفر الشيخ.\n"
            . "- أجب بالعربية الفصحى بإيجاز ووضوح ومهنية، ونسّق الإجابة بعناوين ### ونقاط -، بدون رموز تعبيرية.\n"
            . "- ساعد المستخدم في استخدام المنصة: النماذج، الإرسالات، قوالب Excel (تحميل وإعادة رفع)، الخريطة التفاعلية، التقارير، الهيكل التنظيمي والوظيفي.\n"
            . "- عند السؤال عن أرقام استند فقط إلى موجز البيانات المرفق، ولا تختلق أرقاماً؛ وإن لم تتوفر قل ذلك.\n"
            . "- اسم المستخدم: {$user->name}.\n";

        if ($canData) {
            $brief = $this->decision->brief($user);
            $system .= "\nموجز البيانات ({$brief['scope_note']}):\n" . $this->decision->contextText($brief);
        }

        $res = $this->ai->chat($system, array_merge(array_slice($history, -8), [['role' => 'user', 'content' => $question]]));
        if ($res['success']) {
            return ['content' => $res['content'], 'provider' => $res['provider'], 'provider_label' => $res['provider_label'], 'model' => $res['model'] ?? null];
        }

        return ['content' => $this->fallback($user, $question), 'provider' => 'internal', 'provider_label' => AiService::LABELS['internal'], 'model' => null];
    }

    public function fallback(User $user, string $q): string
    {
        $n = $this->decision->norm($q);
        $isDataQuestion = (bool) preg_match('/(كم|عدد|ملخص|اقل|اكثر|اعلي|وضع|نسبه|قارن|مقارنه|اولويات|متاخر|مركز)/u', $n);
        if ($isDataQuestion && ! str_contains($n, 'كيف') && $user->can('view_dashboard')) {
            return $this->decision->fallbackAnswer($this->decision->brief($user), $q);
        }
        foreach (self::HELP as [$words, $answer]) {
            foreach ($words as $w) {
                if (str_contains($n, $this->decision->norm($w))) {
                    return $answer . "\n\n---\nللمزيد راجع **دليل الاستخدام** من القائمة الجانبية.";
                }
            }
        }

        if ($user->can('view_dashboard')) {
            return $this->decision->fallbackAnswer($this->decision->brief($user), $q);
        }

        return "### كيف يمكنني المساعدة؟\n- اسألني عن طريقة استخدام المنصة: قوالب Excel، الإرسال للمراجعة، الخريطة، التقارير، الحساب والصلاحيات.\n- يمكنك أيضاً مراجعة **دليل الاستخدام** من القائمة الجانبية.";
    }

    /** Markdown مبسّط وآمن: يُهرَّب النص أولاً ثم تُطبق العناوين والقوائم والتأكيد */
    public static function toHtml(string $text): string
    {
        $inline = fn ($s) => preg_replace(['/\*\*(.+?)\*\*/u', '/`([^`]+)`/u'], ['<strong>$1</strong>', '<code>$1</code>'], $s);
        $html = '';
        $list = null;
        $close = function () use (&$html, &$list) { if ($list) { $html .= "</$list>"; $list = null; } };

        foreach (preg_split('/\r?\n/', e($text)) as $raw) {
            $l = trim($raw);
            if ($l === '') { $close(); continue; }
            if (preg_match('/^-{3,}$/', $l)) { $close(); $html .= '<hr>'; }
            elseif (preg_match('/^#{1,4}\s+(.*)$/u', $l, $m)) { $close(); $html .= '<h4>' . $inline($m[1]) . '</h4>'; }
            elseif (preg_match('/^[-*•]\s+(.*)$/u', $l, $m)) { if ($list !== 'ul') { $close(); $html .= '<ul>'; $list = 'ul'; } $html .= '<li>' . $inline($m[1]) . '</li>'; }
            elseif (preg_match('/^\d+[.)]\s+(.*)$/u', $l, $m)) { if ($list !== 'ol') { $close(); $html .= '<ol>'; $list = 'ol'; } $html .= '<li>' . $inline($m[1]) . '</li>'; }
            else { $close(); $html .= '<p class="mb-1">' . $inline($l) . '</p>'; }
        }
        $close();

        return $html;
    }

    public function record(User $user, string $role, string $message, array $extra = []): ChatbotMessage
    {
        return ChatbotMessage::create([
            'user_id' => $user->id,
            'role' => $role,
            'message' => $message,
            'model_used' => $extra['model'] ?? ($extra['provider'] ?? null),
            'context' => array_filter(['channel' => self::CHANNEL, 'source' => $extra['provider'] ?? null, 'source_label' => $extra['provider_label'] ?? null]),
        ]);
    }

    public function history(User $user, int $limit = 10): array
    {
        return static::historyScope(ChatbotMessage::where('user_id', $user->id))
            ->latest('id')->take($limit)->get()->reverse()
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->message])->values()->all();
    }
}
