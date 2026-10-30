<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI provider
    |--------------------------------------------------------------------------
    | ollama    : نموذج محلي عبر Ollama
    | anthropic : Claude عبر Anthropic Messages API
    | auto      : Claude إن وُجد مفتاح، ثم Ollama، ثم محرك التحليل الداخلي
    | none      : محرك التحليل الداخلي فقط (بدون اتصال خارجي)
    */
    'provider' => env('AI_PROVIDER', 'auto'),

    /* Ollama (local) */
    'ollama_url' => env('OLLAMA_URL', 'http://localhost:11434'),
    'model' => env('AI_MODEL', 'qwen2.5'),

    /* Anthropic Claude */
    'anthropic_key' => env('ANTHROPIC_API_KEY'),
    'anthropic_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com'),
    'anthropic_model' => env('ANTHROPIC_MODEL', 'claude-opus-5-5'),
    'anthropic_effort' => env('ANTHROPIC_EFFORT', 'medium'),

    /* Timeouts (seconds) */
    'connect_timeout' => (int) env('AI_CONNECT_TIMEOUT', 8),
    'timeout' => (int) env('AI_TIMEOUT', 120),

    /* Qdrant vector DB */
    'qdrant_url' => env('QDRANT_URL', 'http://localhost:6333'),

    'max_tokens' => (int) env('AI_MAX_TOKENS', 4096),
    'temperature' => 0.4,

    /* Minutes to remember that a provider is unreachable (avoid repeated slow failures) */
    'down_cache_minutes' => 3,
];
