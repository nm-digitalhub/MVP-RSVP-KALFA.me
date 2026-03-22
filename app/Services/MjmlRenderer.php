<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Spatie\Mjml\Mjml;

final class MjmlRenderer
{
    /**
     * Convert MJML to HTML.
     */
    public function render(string $mjml): string
    {
        try {
            return Mjml::new()
                ->beautify()
                ->hideComments()
                ->toHtml($mjml);
        } catch (\Throwable $e) {
            Log::error('Failed to render MJML', [
                'error' => $e->getMessage(),
                'mjml' => $mjml,
            ]);

            throw $e;
        }
    }

    /**
     * Check if MJML is valid.
     */
    public function validate(string $mjml): bool
    {
        return Mjml::new()->canConvertWithoutErrors($mjml);
    }

    /**
     * Get detailed result (HTML + metadata).
     */
    public function convert(string $mjml): MjmlResult
    {
        $result = Mjml::new()->convert($mjml);

        return new MjmlResult(
            html: $result->html(),
            hasErrors: $result->hasErrors(),
            errors: $result->errors(),
        );
    }
}

/**
 * Simple value object for MJML result.
 */
final class MjmlResult
{
    public function __construct(
        public readonly string $html,
        public readonly bool $hasErrors,
        public readonly array $errors,
    ) {}
}
