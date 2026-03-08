<?php

declare(strict_types=1);

/**
 * Call log: GET returns status + conversation lines for a call_sid.
 * POST (from Node) appends a transcript line; requires key=CONFIG secret.
 */

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$callSid = trim((string) ($_GET['call_sid'] ?? $_POST['call_sid'] ?? ''));
$cacheKey = $callSid !== '' ? 'call_log:'.$callSid : null;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($cacheKey === null) {
        echo json_encode(['status' => null, 'lines' => [], 'error' => 'missing call_sid'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $data = Illuminate\Support\Facades\Cache::get($cacheKey, ['status' => null, 'lines' => [], 'updated_at' => null]);
    echo json_encode([
        'status' => $data['status'] ?? null,
        'lines' => $data['lines'] ?? [],
        'updated_at' => $data['updated_at'] ?? null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $secret = config('services.twilio.call_log_secret', '');
    $key = trim((string) ($_POST['key'] ?? $_GET['key'] ?? ''));
    if ($secret !== '' && $key !== $secret) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'forbidden'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($cacheKey === null) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'missing call_sid'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $role = trim((string) ($_POST['role'] ?? 'bot'));
    $text = trim((string) ($_POST['text'] ?? ''));
    if ($text === '') {
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $line = ['role' => $role === 'user' ? 'user' : 'bot', 'text' => $text, 'at' => now()->toIso8601String()];
    $data = Illuminate\Support\Facades\Cache::get($cacheKey, ['status' => null, 'lines' => [], 'updated_at' => null]);
    $data['lines'] = array_merge($data['lines'] ?? [], [$line]);
    $data['updated_at'] = now()->toIso8601String();
    Illuminate\Support\Facades\Cache::put($cacheKey, $data, 3600);
    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'method not allowed'], JSON_UNESCAPED_UNICODE);
