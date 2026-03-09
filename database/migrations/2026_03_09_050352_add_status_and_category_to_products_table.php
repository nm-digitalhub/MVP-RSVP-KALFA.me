<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'status') || ! Schema::hasColumn('products', 'category')) {
            Schema::table('products', function (Blueprint $table): void {
                if (! Schema::hasColumn('products', 'status')) {
                    $table->enum('status', ['draft', 'active', 'archived'])->default('draft')->after('slug');
                    $table->index('status');
                }

                if (! Schema::hasColumn('products', 'category')) {
                    $table->string('category')->nullable()->after('status');
                }
            });
        }

        if (
            ! Schema::hasColumn('product_entitlements', 'label')
            || ! Schema::hasColumn('product_entitlements', 'type')
            || ! Schema::hasColumn('product_entitlements', 'is_active')
            || ! Schema::hasColumn('product_entitlements', 'description')
        ) {
            Schema::table('product_entitlements', function (Blueprint $table): void {
                if (! Schema::hasColumn('product_entitlements', 'label')) {
                    $table->string('label')->nullable()->after('feature_key');
                }

                if (! Schema::hasColumn('product_entitlements', 'type')) {
                    $table->enum('type', ['boolean', 'number', 'text', 'enum'])->default('text')->after('label');
                }

                if (! Schema::hasColumn('product_entitlements', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('type');
                }

                if (! Schema::hasColumn('product_entitlements', 'description')) {
                    $table->text('description')->nullable()->after('is_active');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $columns = [];

            if (Schema::hasColumn('products', 'status')) {
                $columns[] = 'status';
            }

            if (Schema::hasColumn('products', 'category')) {
                $columns[] = 'category';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('product_entitlements', function (Blueprint $table): void {
            $columns = [];

            foreach (['label', 'type', 'is_active', 'description'] as $column) {
                if (Schema::hasColumn('product_entitlements', $column)) {
                    $columns[] = $column;
                }
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
