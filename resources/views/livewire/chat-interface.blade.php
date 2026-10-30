<div>
    <div class="card-kfs kfs-chat">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-robot ml-2"></i>المساعد الذكي لمنصة رابط</span>
            @if (! empty($messages))
                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="clearChat" wire:confirm="سيتم مسح المحادثة. هل أنت متأكد؟">
                    <i class="fas fa-broom ml-1"></i>محادثة جديدة
                </button>
            @endif
        </div>

        <div class="card-body kfs-chat-body" id="chatContainer" aria-live="polite">
            @if (empty($messages))
                <div class="empty-state py-4">
                    <span class="eg-icon lg"><i class="fas fa-robot"></i></span>
                    <h5>مرحباً بك في المساعد الذكي</h5>
                    <p class="mb-0">أساعدك في استخدام المنصة والإجابة عن أسئلة البيانات ضمن صلاحياتك.</p>
                </div>
            @endif

            @foreach ($messages as $msg)
                @php $isUser = ($msg['role'] ?? '') === 'user'; @endphp
                <div class="kfs-msg {{ $isUser ? 'user' : 'assistant' }}" wire:key="m-{{ $loop->index }}">
                    <div class="bubble">
                        @if ($isUser)
                            {!! nl2br(e($msg['message'] ?? '')) !!}
                        @else
                            {!! \App\Services\PlatformAssistant::toHtml($msg['message'] ?? '') !!}
                        @endif
                    </div>
                    <div class="meta">
                        @if (! empty($msg['source']))<span class="src-badge">{{ $msg['source'] }}</span>@endif
                        <span>{{ $msg['created_at'] ?? '' }}</span>
                    </div>
                </div>
            @endforeach

            <div class="kfs-msg assistant" wire:loading.flex wire:target="sendMessage,ask">
                <div class="bubble"><span class="kfs-typing"><span></span><span></span><span></span> جارٍ إعداد الإجابة…</span></div>
            </div>
        </div>

        <div class="card-footer">
            <div class="d-flex flex-wrap gap-2 mb-2">
                @foreach ($suggestions as $s)
                    <button type="button" class="kfs-suggest" wire:click="ask(@js($s))" wire:loading.attr="disabled">{{ $s }}</button>
                @endforeach
            </div>
            <form wire:submit="sendMessage" class="d-flex gap-2">
                <label for="chatInput" class="visually-hidden">رسالتك</label>
                <input type="text" class="form-control" wire:model="newMessage" id="chatInput" placeholder="اكتب سؤالك هنا…" autocomplete="off" maxlength="2000">
                <button type="submit" class="btn btn-kfs px-4" wire:loading.attr="disabled" aria-label="إرسال">
                    <i class="fas fa-paper-plane" wire:loading.remove wire:target="sendMessage"></i>
                    <i class="fas fa-spinner fa-spin" wire:loading wire:target="sendMessage"></i>
                </button>
            </form>
            @error('newMessage') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
        </div>
    </div>

    <style>
        .kfs-chat { display: flex; flex-direction: column; height: calc(100vh - 290px); min-height: 520px; }
        .kfs-chat-body { flex: 1; overflow-y: auto; background: var(--ivory); padding: 1rem; }
        .kfs-msg { display: flex; flex-direction: column; margin-bottom: .9rem; }
        .kfs-msg.user { align-items: flex-start; }
        .kfs-msg.assistant { align-items: stretch; }
        .kfs-msg .bubble { border-radius: 8px; padding: .65rem .9rem; font-size: .88rem; line-height: 1.85; overflow-wrap: anywhere; }
        .kfs-msg.user .bubble { background: var(--egypt-green); color: var(--white-soft); max-width: 85%; }
        .kfs-msg.assistant .bubble { background: var(--white-soft); border: 1px solid var(--line); border-top: 2px solid var(--egypt-gold); }
        .kfs-msg .bubble h4 { font-size: .93rem; margin: .6rem 0 .3rem; color: var(--egypt-green); }
        .kfs-msg .bubble h4:first-child { margin-top: 0; }
        .kfs-msg .bubble ul, .kfs-msg .bubble ol { padding-right: 1.1rem; padding-left: 0; margin: .2rem 0 .4rem; }
        .kfs-msg .meta { display: flex; gap: .5rem; align-items: center; font-size: .7rem; color: var(--muted); margin-top: .25rem; }
        .kfs-msg .src-badge { border: 1px solid var(--egypt-gold-line); color: #7a5f2c; border-radius: 4px; padding: 0 .4rem; }
        .kfs-suggest { border: 1px solid var(--egypt-gold-line); background: var(--white-soft); color: var(--egypt-green); font-size: .76rem; border-radius: var(--r); padding: .25rem .6rem; }
        .kfs-suggest:hover { background: var(--egypt-gold-soft); }
        .kfs-typing { display: inline-flex; gap: 4px; align-items: center; color: var(--text-soft); font-size: .8rem; }
        .kfs-typing span { width: 6px; height: 6px; border-radius: 50%; background: var(--egypt-gold); animation: kfsdot 1.2s var(--ease) infinite; }
        .kfs-typing span:nth-child(2) { animation-delay: .2s; } .kfs-typing span:nth-child(3) { animation-delay: .4s; }
        @keyframes kfsdot { 0%, 80%, 100% { opacity: .25; } 40% { opacity: 1; } }
    </style>

    @script
    <script>
        const scrollChat = () => { const c = document.getElementById('chatContainer'); if (c) c.scrollTop = c.scrollHeight; };
        scrollChat();
        $wire.on('messageSent', () => setTimeout(scrollChat, 50));
    </script>
    @endscript
</div>
