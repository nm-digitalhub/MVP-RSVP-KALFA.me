<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$models = ['glm-5', 'glm-4-plus', 'glm-4-air', 'glm-4-flash', 'glm-4v', 'glm-4.5'];

foreach ($models as $model) {
    echo "Testing $model...\n";
    $response = Http::withToken('d9e71af7680e483bb8b49859cae064f5.LvYOh3QiJlbE20nR')
        ->post('https://api.z.ai/api/coding/paas/v4/chat/completions', [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => 'Say hello in 1 word.'],
            ],
        ]);

    $data = $response->json();
    if (isset($data['error'])) {
        echo 'Error: '.$data['error']['message']."\n";
    } elseif (isset($data['choices'][0]['message']['content'])) {
        echo 'Success: '.$data['choices'][0]['message']['content']."\n";
    } else {
        print_r($data);
    }
}
