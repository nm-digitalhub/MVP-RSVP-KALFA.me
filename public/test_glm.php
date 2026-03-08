<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$response = Http::withToken('d9e71af7680e483bb8b49859cae064f5.LvYOh3QiJlbE20nR')
    ->post('https://api.z.ai/api/coding/paas/v4/chat/completions', [
        'model' => 'glm-4',
        'messages' => [
            ['role' => 'system', 'content' => 'You are a helpful assistant.'],
            ['role' => 'user', 'content' => 'Say hello in Hebrew.'],
        ],
    ]);

print_r($response->json());
