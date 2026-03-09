<?php

declare(strict_types=1);

return [
    'feature_cache_ttl' => (int) env('PRODUCT_ENGINE_FEATURE_CACHE_TTL', 300),
    'cache_store' => env('PRODUCT_ENGINE_CACHE_STORE'),
    'defaults' => [],
    'commercial' => [
        'default_billing_cycle' => 'monthly',
    ],
    'usage' => [
        'default_policy' => env('PRODUCT_ENGINE_USAGE_POLICY', 'hard'),
    ],
    'operations' => [
        'monitor' => [
            'max_scheduler_heartbeat_age_seconds' => (int) env('PRODUCT_ENGINE_MONITOR_MAX_HEARTBEAT_AGE', 120),
            'task_grace_seconds' => (int) env('PRODUCT_ENGINE_MONITOR_TASK_GRACE_SECONDS', 120),
            'state_ttl_seconds' => (int) env('PRODUCT_ENGINE_MONITOR_STATE_TTL_SECONDS', 172800),
        ],
        'trial_expirations' => [
            'enabled' => (bool) env('PRODUCT_ENGINE_TRIAL_EXPIRATIONS_ENABLED', true),
            'frequency' => env('PRODUCT_ENGINE_TRIAL_EXPIRATIONS_FREQUENCY', 'everyFiveMinutes'),
        ],
        'integrity_checks' => [
            'enabled' => (bool) env('PRODUCT_ENGINE_INTEGRITY_CHECKS_ENABLED', true),
            'frequency' => env('PRODUCT_ENGINE_INTEGRITY_CHECKS_FREQUENCY', 'hourly'),
            'fail_on_issues' => (bool) env('PRODUCT_ENGINE_INTEGRITY_FAIL_ON_ISSUES', true),
        ],
    ],
];
