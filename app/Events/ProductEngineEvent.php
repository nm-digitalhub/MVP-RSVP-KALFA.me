<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Account;
use App\Models\AccountSubscription;
use App\Models\Product;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ProductEngineEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly string $action,
        public readonly ?Account $account = null,
        public readonly ?Product $product = null,
        public readonly ?AccountSubscription $subscription = null,
        public readonly array $payload = [],
        public readonly string $level = 'info',
    ) {}
}
