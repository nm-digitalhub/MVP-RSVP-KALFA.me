<?php

declare(strict_types=1);

namespace App\Console\Commands\ProductEngine;

use App\Models\Product;
use App\Services\ProductIntegrityChecker;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class CheckIntegrityCommand extends Command
{
    protected $signature = 'app:check-integrity-command
                            {product? : Optional product ID or slug to check}
                            {--fail-on-issues : Return a non-zero exit code when integrity issues are found}';

    protected $description = 'Check product engine catalog integrity for duplicate keys, missing prices, and invalid limits';

    public function handle(ProductIntegrityChecker $integrityChecker): int
    {
        $products = $this->resolveProducts();

        if ($products->isEmpty()) {
            $this->error('No matching products found.');

            return self::FAILURE;
        }

        $rows = [];

        foreach ($products as $product) {
            foreach ($integrityChecker->issuesForProduct($product) as $issue) {
                $rows[] = [
                    'product_id' => $product->id,
                    'slug' => $product->slug,
                    'issue' => $issue,
                ];
            }
        }

        if ($rows === []) {
            $this->info(sprintf('Integrity check passed for %d product(s).', $products->count()));

            return self::SUCCESS;
        }

        $this->table(['Product ID', 'Slug', 'Issue'], $rows);
        $this->warn(sprintf('Integrity issues detected: %d issue(s) across %d product(s).', count($rows), collect($rows)->pluck('product_id')->unique()->count()));

        return $this->option('fail-on-issues') ? self::FAILURE : self::SUCCESS;
    }

    private function resolveProducts(): Collection
    {
        $product = $this->argument('product');

        $query = Product::query()->with('entitlements', 'productPlans.activePrices');

        if ($product === null) {
            return $query->orderBy('id')->get();
        }

        return $query
            ->where(function ($builder) use ($product): void {
                $builder->whereKey($product)
                    ->orWhere('slug', $product);
            })
            ->get();
    }
}
