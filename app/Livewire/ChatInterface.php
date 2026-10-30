<?php

namespace App\Livewire;

use App\Models\ChatbotMessage;
use App\Services\PlatformAssistant;
use Livewire\Component;

class ChatInterface extends Component
{
    public array $messages = [];
    public string $newMessage = '';

    protected array $rules = [
        'newMessage' => 'required|string|max:2000',
    ];

    protected array $validationAttributes = [
        'newMessage' => 'الرسالة',
    ];

    public function mount(): void
    {
        $this->loadHistory();
    }

    public function loadHistory(): void
    {
        $user = auth()->user();
        if (! $user) return;

        $this->messages = PlatformAssistant::historyScope(ChatbotMessage::where('user_id', $user->id))
            ->latest('id')->limit(50)->get()->reverse()->values()
            ->map(fn (ChatbotMessage $msg) => [
                'role' => $msg->role,
                'message' => $msg->message,
                'created_at' => $msg->created_at?->locale('ar')->diffForHumans(),
                'source' => $msg->context['source_label'] ?? null,
            ])
            ->toArray();
    }

    public function ask(string $question): void
    {
        $this->newMessage = $question;
        $this->sendMessage();
    }

    public function sendMessage(): void
    {
        $this->validate();

        $user = auth()->user();
        $question = trim($this->newMessage);
        if (! $user || $question === '') return;

        $this->newMessage = '';
        $assistant = app(PlatformAssistant::class);
        $history = $assistant->history($user);

        $assistant->record($user, 'user', $question);
        $this->messages[] = ['role' => 'user', 'message' => $question, 'created_at' => 'الآن', 'source' => null];

        $answer = $assistant->answer($user, $question, $history);
        $assistant->record($user, 'assistant', $answer['content'], $answer);
        $this->messages[] = ['role' => 'assistant', 'message' => $answer['content'], 'created_at' => 'الآن', 'source' => $answer['provider_label']];

        $this->dispatch('messageSent');
    }

    public function clearChat(): void
    {
        $user = auth()->user();
        if (! $user) return;

        PlatformAssistant::historyScope(ChatbotMessage::where('user_id', $user->id))->delete();
        $this->messages = [];
    }

    public function render()
    {
        return view('livewire.chat-interface', ['suggestions' => PlatformAssistant::SUGGESTIONS]);
    }
}
