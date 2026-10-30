<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ChatbotMessage;
use App\Services\AiService;
use App\Services\DecisionSupportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * مساعد دعم القرار للقيادات: مؤشرات، استنتاجات، مقارنة المراكز، ومحادثة ذكية.
 */
class DecisionSupportController extends Controller
{
    public const CHANNEL = 'decision';

    public function __construct(protected DecisionSupportService $service, protected AiService $ai) {}

    public function index(Request $request): View
    {
        $brief = $this->service->brief($request->user(), $request->boolean('refresh'));

        $history = ChatbotMessage::where('user_id', $request->user()->id)
            ->where('context->channel', self::CHANNEL)
            ->latest('id')->take(30)->get()->reverse()->values()
            ->map(fn (ChatbotMessage $m) => [
                'role' => $m->role,
                'content' => $m->message,
                'source' => $m->context['source_label'] ?? null,
                'time' => $m->created_at?->locale('ar')->diffForHumans(),
            ]);

        return view('decision.index', [
            'brief' => $brief,
            'history' => $history,
            'indicators' => DecisionSupportService::INDICATORS,
            'suggestions' => DecisionSupportService::SUGGESTIONS,
            'aiStatus' => $this->ai->status(),
        ]);
    }

    public function insights(Request $request): JsonResponse
    {
        $brief = $this->service->brief($request->user(), $request->boolean('refresh'));

        return response()->json([
            'generated_at' => $brief['generated_at'],
            'scope' => $brief['scope_note'],
            'kpis' => $brief['kpis'],
            'insights' => $brief['insights'],
            'centers' => $brief['centers'],
        ])->header('Cache-Control', 'no-store, private');
    }

    public function ask(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($request->boolean('reset')) {
            ChatbotMessage::where('user_id', $user->id)->where('context->channel', self::CHANNEL)->delete();
            return response()->json(['success' => true, 'reset' => true]);
        }

        $data = $request->validate([
            'question' => ['required', 'string', 'min:2', 'max:2000'],
        ], ['question.required' => 'اكتب سؤالك أولاً.']);

        $question = trim($data['question']);

        $history = ChatbotMessage::where('user_id', $user->id)
            ->where('context->channel', self::CHANNEL)
            ->latest('id')->take(8)->get()->reverse()
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->message])->values()->all();

        ChatbotMessage::create([
            'user_id' => $user->id,
            'role' => 'user',
            'message' => $question,
            'context' => ['channel' => self::CHANNEL],
        ]);

        $started = microtime(true);
        $answer = $this->service->answer($user, $question, $history);

        $msg = ChatbotMessage::create([
            'user_id' => $user->id,
            'role' => 'assistant',
            'message' => $answer['content'],
            'model_used' => $answer['model'] ?? $answer['provider'],
            'context' => ['channel' => self::CHANNEL, 'source' => $answer['provider'], 'source_label' => $answer['provider_label']],
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'auditable_type' => ChatbotMessage::class,
            'auditable_id' => $msg->id,
            'event' => 'decision_query',
            'description' => 'استعلام دعم القرار: ' . mb_substr($question, 0, 150),
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 250),
        ]);

        return response()->json([
            'success' => true,
            'answer' => $answer['content'],
            'source' => $answer['provider'],
            'source_label' => $answer['provider_label'],
            'elapsed' => round(microtime(true) - $started, 1),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function brief(Request $request): View
    {
        $brief = $this->service->brief($request->user(), $request->boolean('refresh'));

        return view('decision.brief', [
            'brief' => $brief,
            'indicators' => DecisionSupportService::INDICATORS,
            'user' => $request->user(),
        ]);
    }
}
