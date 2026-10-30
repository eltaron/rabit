<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatbotMessage;
use App\Services\PlatformAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatbotApiController extends Controller
{
    public function __construct(protected PlatformAssistant $assistant) {}

    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ], [
            'message.required' => 'الرسالة مطلوبة.',
        ]);

        $user = Auth::user();
        $question = trim($request->input('message'));
        $history = $this->assistant->history($user);

        $this->assistant->record($user, 'user', $question);
        $answer = $this->assistant->answer($user, $question, $history);
        $this->assistant->record($user, 'assistant', $answer['content'], $answer);

        return response()->json([
            'success' => true,
            'reply' => $answer['content'],
            'source' => $answer['provider'],
            'source_label' => $answer['provider_label'],
        ]);
    }

    public function getHistory(Request $request): JsonResponse
    {
        $messages = PlatformAssistant::historyScope(ChatbotMessage::where('user_id', Auth::id()))
            ->latest('id')->take(50)->get()->reverse()->values()
            ->map(fn ($msg) => [
                'id' => $msg->id,
                'role' => $msg->role,
                'message' => $msg->message,
                'source' => $msg->context['source_label'] ?? null,
                'created_at' => $msg->created_at->toISOString(),
            ]);

        return response()->json([
            'success' => true,
            'messages' => $messages,
        ]);
    }
}
