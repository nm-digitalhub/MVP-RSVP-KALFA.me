<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ProductEngineEvent;
use App\Services\SystemAuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

final class LogProductEngineEvent
{
    public function handle(ProductEngineEvent $event): void
    {
        Log::log($event->level, 'Product Engine event', [
            'action' => $event->action,
            'account_id' => $event->account?->id,
            'product_id' => $event->product?->id,
            'subscription_id' => $event->subscription?->id,
            'payload' => $event->payload,
        ]);

        SystemAuditLogger::log(
            null,
            'product_engine.'.$event->action,
            $this->resolveTarget($event),
            array_filter([
                'account_id' => $event->account?->id,
                'product_id' => $event->product?->id,
                'subscription_id' => $event->subscription?->id,
                ...$event->payload,
            ], static fn (mixed $value): bool => $value !== null),
        );
    }

    private function resolveTarget(ProductEngineEvent $event): Model|int|null
    {
        return $event->subscription
            ?? $event->product
            ?? $event->account;
    }
}
