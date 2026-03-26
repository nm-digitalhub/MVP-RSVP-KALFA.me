<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Full list of GET paths for authenticated panel capture ({@see CapturePanelScreenshotsCommand} --all).
 *
 * Mirrors scripts/script.cjs (system) plus main tenant routes from routes/web.php.
 * Override entirely with env PANEL_SCREENSHOT_AUTH_ALL_PATHS=comma,separated,paths
 */
final class PanelScreenshotAuthPaths
{
    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        $raw = env('PANEL_SCREENSHOT_AUTH_ALL_PATHS');

        if (is_string($raw) && trim($raw) !== '') {
            return self::splitCommaPaths($raw);
        }

        return self::defaultPaths([
            'org' => (string) env('PANEL_SCREENSHOT_SYSTEM_ORG_ID', env('CAPTURE_SYSTEM_ORG_ID', '1')),
            'account' => (string) env('PANEL_SCREENSHOT_SYSTEM_ACCOUNT_ID', env('CAPTURE_SYSTEM_ACCOUNT_ID', '1')),
            'user' => (string) env('PANEL_SCREENSHOT_SYSTEM_USER_ID', env('CAPTURE_SYSTEM_USER_ID', '1')),
            'product' => self::resolveProductId(),
            'event' => (string) env('PANEL_SCREENSHOT_EVENT_ID', env('CAPTURE_EVENT_ID', '1')),
        ]);
    }

    /**
     * @param  array{org: string, account: string, user: string, product: string, event: string}  $ids
     * @return array<int, string>
     */
    public static function defaultPaths(array $ids): array
    {
        $e = $ids['event'];
        $org = $ids['org'];
        $account = $ids['account'];
        $user = $ids['user'];
        $product = $ids['product'];

        $tenant = [
            '/dashboard',
            '/profile',
            '/organizations',
            '/organizations/create',
            '/select-plan',
            '/organization/settings',
            '/billing',
            '/billing/entitlements',
            '/billing/usage',
            '/billing/intents',
            '/team',
            '/dashboard/events',
            '/dashboard/events/create',
            "/dashboard/events/{$e}",
            "/dashboard/events/{$e}/edit",
            "/dashboard/events/{$e}/guests",
            "/dashboard/events/{$e}/tables",
            "/dashboard/events/{$e}/invitations",
            "/dashboard/events/{$e}/seat-assignments",
        ];

        $system = [
            '/system/dashboard',
            '/system/settings',
            '/system/organizations',
            "/system/organizations/{$org}",
            '/system/users',
            "/system/users/{$user}",
            '/system/accounts',
            '/system/accounts/create',
            "/system/accounts/{$account}",
            '/system/products',
            '/system/products/create',
            "/system/products/{$product}",
            '/system/trial-reminders',
        ];

        return array_values(array_unique([...$tenant, ...$system]));
    }

    private static function resolveProductId(): string
    {
        $explicit = env('PANEL_SCREENSHOT_PRODUCT_ID', env('CAPTURE_PRODUCT_ID'));

        if (is_string($explicit) && trim($explicit) !== '') {
            return trim($explicit);
        }

        $pathEnv = env('CAPTURE_PRODUCT_PATH');

        if (is_string($pathEnv) && preg_match('#/products/(\d+)#', $pathEnv, $m)) {
            return $m[1];
        }

        return '1';
    }

    /**
     * @return array<int, string>
     */
    private static function splitCommaPaths(string $raw): array
    {
        $paths = [];

        foreach (explode(',', $raw) as $segment) {
            $p = trim($segment);
            if ($p === '') {
                continue;
            }
            $paths[] = str_starts_with($p, '/') ? $p : '/'.$p;
        }

        return array_values($paths);
    }
}
