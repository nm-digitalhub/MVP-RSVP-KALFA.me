<?php

declare(strict_types=1);

namespace App\Http\Controllers\WellKnown;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hosts verification files for Universal Links (iOS) and App Links (Android).
 *
 * @see https://nativephp.com/docs/mobile/3/concepts/deep-links
 */
final class UniversalLinksController extends Controller
{
    /**
     * Apple App Site Association (no file extension in the URL path).
     *
     * @see https://developer.apple.com/documentation/xcode/supporting-associated-domains
     */
    public function appleAppSiteAssociation(): JsonResponse|Response
    {
        $appId = trim((string) (config('nativephp.app_id') ?? ''));
        $team = trim((string) (config('nativephp.development_team') ?? ''));

        if ($appId === '' || $team === '') {
            abort(Response::HTTP_NOT_FOUND);
        }

        $fullAppId = $team.'.'.$appId;

        $payload = [
            'applinks' => [
                'details' => [
                    [
                        'appIDs' => [$fullAppId],
                        'components' => [
                            ['/' => '/*'],
                        ],
                    ],
                ],
            ],
        ];

        if (config('nativephp.universal_links.aasa_webcredentials', true)) {
            $payload['webcredentials'] = [
                'apps' => [$fullAppId],
            ];
        }

        return response()
            ->json($payload, Response::HTTP_OK, [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ->header('Content-Type', 'application/json');
    }

    /**
     * Android Digital Asset Links.
     *
     * @see https://developers.google.com/digital-asset-links/v1/getting-started
     */
    public function assetLinks(): JsonResponse
    {
        $package = trim((string) config('nativephp.android_package_name', ''));
        if ($package === '') {
            $package = trim((string) (config('nativephp.app_id') ?? ''));
        }

        /** @var list<string> $fingerprints */
        $fingerprints = config('nativephp.android_assetlinks_sha256_cert_fingerprints', []);
        if (! is_array($fingerprints)) {
            $fingerprints = [];
        }
        $fingerprints = array_values(array_filter(array_map(
            static fn (mixed $fp): string => trim((string) $fp),
            $fingerprints
        )));

        if ($package === '' || $fingerprints === []) {
            return response()
                ->json([], Response::HTTP_OK, [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                ->header('Content-Type', 'application/json');
        }

        return response()
            ->json(
                [
                    [
                        'relation' => ['delegate_permission/common.handle_all_urls'],
                        'target' => [
                            'namespace' => 'android_app',
                            'package_name' => $package,
                            'sha256_cert_fingerprints' => $fingerprints,
                        ],
                    ],
                ],
                Response::HTTP_OK,
                [],
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            )
            ->header('Content-Type', 'application/json');
    }
}
