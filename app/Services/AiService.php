<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * طبقة موحدة لمزودي الذكاء الاصطناعي: Claude (Anthropic) و Ollama المحلي.
 * إن تعذّر الوصول لأي مزود تعيد success=false ليتولى المستدعي محرك التحليل الداخلي.
 */
class AiService
{
    public const LABELS = [
        'anthropic' => 'Claude',
        'ollama' => 'Ollama',
        'internal' => 'محرك التحليل الداخلي',
    ];

    protected string $ollamaUrl;
    protected string $model;
    protected string $qdrantUrl;
    protected int $maxTokens;
    protected float $temperature;

    public function __construct()
    {
        $this->ollamaUrl = rtrim(config('ai.ollama_url', 'http://localhost:11434'), '/');
        $this->model = config('ai.model', 'qwen2.5');
        $this->qdrantUrl = config('ai.qdrant_url', 'http://localhost:6333');
        $this->maxTokens = (int) config('ai.max_tokens', 4096);
        $this->temperature = (float) config('ai.temperature', 0.4);
    }

    /** ترتيب المزودين المطلوب تجربتهم */
    public function providers(): array
    {
        $p = config('ai.provider', 'auto');
        $hasKey = filled(config('ai.anthropic_key'));

        return match ($p) {
            'anthropic' => $hasKey ? ['anthropic'] : [],
            'ollama' => ['ollama'],
            'none' => [],
            default => array_values(array_filter([$hasKey ? 'anthropic' : null, 'ollama'])),
        };
    }

    /** وصف المزود المتاح (للعرض في الواجهة) */
    public function status(): array
    {
        $list = $this->providers();
        return [
            'configured' => $list,
            'labels' => array_map(fn ($p) => self::LABELS[$p], $list),
            'down' => array_values(array_filter($list, fn ($p) => Cache::has("ai.down.$p"))),
        ];
    }

    /**
     * محادثة بنظام + رسائل ['role' => user|assistant, 'content' => string].
     *
     * @return array{success:bool, content?:string, provider?:string, provider_label?:string, model?:string, error?:string}
     */
    public function chat(string $system, array $messages): array
    {
        $messages = $this->normalizeMessages($messages);
        $errors = [];

        foreach ($this->providers() as $provider) {
            if (Cache::has("ai.down.$provider")) {
                $errors[] = "$provider: unavailable (cached)";
                continue;
            }
            $res = $provider === 'anthropic'
                ? $this->callAnthropic($system, $messages)
                : $this->callOllama($system, $messages);

            if ($res['success']) {
                return $res + ['provider' => $provider, 'provider_label' => self::LABELS[$provider]];
            }
            $errors[] = "$provider: " . ($res['error'] ?? 'error');
            if (! empty($res['unreachable'])) {
                Cache::put("ai.down.$provider", true, now()->addMinutes((int) config('ai.down_cache_minutes', 3)));
            }
        }

        return ['success' => false, 'error' => implode(' | ', $errors) ?: 'no provider configured'];
    }

    /** دمج الرسائل المتتالية من نفس الدور والتأكد أن أول رسالة من المستخدم */
    protected function normalizeMessages(array $messages): array
    {
        $out = [];
        foreach ($messages as $m) {
            $role = ($m['role'] ?? 'user') === 'assistant' ? 'assistant' : 'user';
            $content = trim((string) ($m['content'] ?? ''));
            if ($content === '') continue;
            if ($out && end($out)['role'] === $role) {
                $out[array_key_last($out)]['content'] .= "\n\n" . $content;
            } else {
                $out[] = ['role' => $role, 'content' => $content];
            }
        }
        while ($out && $out[0]['role'] !== 'user') array_shift($out);
        return $out;
    }

    protected function callAnthropic(string $system, array $messages): array
    {
        $model = config('ai.anthropic_model', 'claude-opus-5-5');
        try {
            $response = Http::withHeaders([
                'x-api-key' => config('ai.anthropic_key'),
                'anthropic-version' => '2023-06-01',
                'anthropic-beta' => 'server-side-fallback-2026-07-01',
            ])
                ->connectTimeout((int) config('ai.connect_timeout', 8))
                ->timeout((int) config('ai.timeout', 120))
                ->post(rtrim(config('ai.anthropic_url'), '/') . '/v1/messages', [
                    'model' => $model,
                    'max_tokens' => max($this->maxTokens, 4096),
                    'system' => $system,
                    'messages' => $messages,
                    'output_config' => ['effort' => config('ai.anthropic_effort', 'medium')],
                    'fallbacks' => 'default',
                ]);

            if (! $response->successful()) {
                Log::warning('Anthropic API error', ['status' => $response->status(), 'body' => mb_substr($response->body(), 0, 500)]);
                return [
                    'success' => false,
                    'error' => 'HTTP ' . $response->status(),
                    'unreachable' => in_array($response->status(), [401, 403, 404, 429, 500, 502, 503, 529], true),
                ];
            }

            $body = $response->json();
            if (($body['stop_reason'] ?? null) === 'refusal') {
                return ['success' => false, 'error' => 'refusal'];
            }
            $text = collect($body['content'] ?? [])->where('type', 'text')->pluck('text')->implode("\n");
            if (trim($text) === '') {
                return ['success' => false, 'error' => 'empty response'];
            }

            return ['success' => true, 'content' => $text, 'model' => $body['model'] ?? $model];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return ['success' => false, 'error' => 'connection', 'unreachable' => true];
        } catch (\Throwable $e) {
            Log::error('Anthropic exception', ['message' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function callOllama(string $system, array $messages): array
    {
        try {
            $response = Http::connectTimeout((int) config('ai.connect_timeout', 8))
                ->timeout((int) config('ai.timeout', 120))
                ->post("{$this->ollamaUrl}/api/chat", [
                    'model' => $this->model,
                    'messages' => array_merge([['role' => 'system', 'content' => $system]], $messages),
                    'stream' => false,
                    'options' => ['num_predict' => $this->maxTokens, 'temperature' => $this->temperature],
                ]);

            if ($response->successful() && trim((string) $response->json('message.content')) !== '') {
                return ['success' => true, 'content' => $response->json('message.content'), 'model' => $response->json('model', $this->model)];
            }

            return ['success' => false, 'error' => 'HTTP ' . $response->status(), 'unreachable' => $response->status() === 404];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return ['success' => false, 'error' => 'connection', 'unreachable' => true];
        } catch (\Throwable $e) {
            Log::error('Ollama exception', ['message' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * واجهة قديمة (متوافقة): رسالة مفردة مع سياق محادثة.
     */
    public function sendMessage(string $message, array $context = []): array
    {
        $msgs = array_map(fn ($m) => ['role' => $m['role'] ?? 'user', 'content' => $m['content'] ?? ''], $context);
        $msgs[] = ['role' => 'user', 'content' => $message];

        $res = $this->chat('أنت مساعد رسمي لمحافظة كفر الشيخ. أجب بالعربية الفصحى بإيجاز ودقة.', $msgs);

        return $res['success']
            ? ['success' => true, 'content' => $res['content'], 'model' => $res['model'] ?? null, 'provider' => $res['provider']]
            : ['success' => false, 'error' => 'تعذر الاتصال بخدمة الذكاء الاصطناعي حالياً.'];
    }

    /**
     * Generate report content based on a prompt and data.
     */
    public function generateReportContent(string $prompt, array $data = []): string
    {
        $systemPrompt = 'أنت مساعد ذكي لكتابة التقارير الرسمية في محافظة كفر الشيخ. '
            . 'اكتب التقارير باللغة العربية بشكل رسمي ومهني، بالصيغة المناسبة للتقارير الحكومية المصرية. '
            . 'اعتمد فقط على البيانات المرفقة ولا تختلق أرقاماً.';

        if (! empty($data)) {
            $prompt .= "\n\nالبيانات المتاحة:\n" . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        $result = $this->chat($systemPrompt, [['role' => 'user', 'content' => $prompt]]);

        return $result['success'] ? $result['content'] : 'عذراً، لم يتمكن من إنشاء المحتوى في الوقت الحالي.';
    }

    /**
     * Search the Qdrant vector database for relevant knowledge.
     */
    public function searchKnowledgeBase(string $query, int $limit = 5): array
    {
        try {
            $embeddingResponse = Http::connectTimeout(5)->timeout(30)
                ->post("{$this->ollamaUrl}/api/embeddings", ['model' => $this->model, 'prompt' => $query]);

            if (! $embeddingResponse->successful()) {
                return ['success' => false, 'error' => 'فشل في إنشاء تمثيل النص', 'results' => []];
            }

            $searchResponse = Http::connectTimeout(5)->timeout(30)
                ->post("{$this->qdrantUrl}/collections/kfs_knowledge/points/search", [
                    'vector' => $embeddingResponse->json('embedding', []),
                    'limit' => $limit,
                    'with_payload' => true,
                ]);

            if ($searchResponse->successful()) {
                return [
                    'success' => true,
                    'results' => collect($searchResponse->json('result', []))->map(fn ($item) => [
                        'score' => $item['score'] ?? 0,
                        'content' => $item['payload']['content'] ?? '',
                        'title' => $item['payload']['title'] ?? '',
                        'source' => $item['payload']['source'] ?? '',
                    ])->toArray(),
                ];
            }

            return ['success' => true, 'results' => []];
        } catch (\Throwable $e) {
            Log::error('Knowledge base search error', ['message' => $e->getMessage()]);
            return ['success' => false, 'error' => 'حدث خطأ في البحث', 'results' => []];
        }
    }
}
