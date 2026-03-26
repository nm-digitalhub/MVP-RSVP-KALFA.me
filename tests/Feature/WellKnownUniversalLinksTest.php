<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class WellKnownUniversalLinksTest extends TestCase
{
    public function test_apple_app_site_association_returns_not_found_when_bundle_id_missing(): void
    {
        config()->set('nativephp.app_id', '');
        config()->set('nativephp.development_team', 'ABCDE12345');

        $this->get('/.well-known/apple-app-site-association')->assertNotFound();
    }

    public function test_apple_app_site_association_returns_not_found_when_team_id_missing(): void
    {
        config()->set('nativephp.app_id', 'me.example.app');
        config()->set('nativephp.development_team', '');

        $this->get('/.well-known/apple-app-site-association')->assertNotFound();
    }

    public function test_apple_app_site_association_returns_json_matching_apple_associated_domains_shape(): void
    {
        config()->set('nativephp.app_id', 'me.kalfa.eventrsvp');
        config()->set('nativephp.development_team', '3P6C82QTRL');
        config()->set('nativephp.universal_links.aasa_webcredentials', true);

        $expectedAppId = '3P6C82QTRL.me.kalfa.eventrsvp';

        $this->get('/.well-known/apple-app-site-association')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJson([
                'applinks' => [
                    'details' => [
                        [
                            'appIDs' => [$expectedAppId],
                            'components' => [
                                ['/' => '/*'],
                            ],
                        ],
                    ],
                ],
                'webcredentials' => [
                    'apps' => [$expectedAppId],
                ],
            ]);
    }

    public function test_apple_app_site_association_omits_webcredentials_when_disabled(): void
    {
        config()->set('nativephp.app_id', 'me.example.app');
        config()->set('nativephp.development_team', 'ABCDE12345');
        config()->set('nativephp.universal_links.aasa_webcredentials', false);

        $json = $this->get('/.well-known/apple-app-site-association')
            ->assertOk()
            ->json();

        $this->assertArrayNotHasKey('webcredentials', $json);
    }

    public function test_asset_links_returns_empty_json_array_when_no_sha256_fingerprints(): void
    {
        config()->set('nativephp.app_id', 'me.kalfa.eventrsvp');
        config()->set('nativephp.android_package_name', 'me.kalfa.eventrsvp');
        config()->set('nativephp.android_assetlinks_sha256_cert_fingerprints', []);

        $this->get('/.well-known/assetlinks.json')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertExactJson([]);
    }

    public function test_asset_links_returns_digital_asset_link_when_fingerprints_configured(): void
    {
        config()->set('nativephp.android_package_name', 'me.kalfa.eventrsvp');
        config()->set('nativephp.android_assetlinks_sha256_cert_fingerprints', [
            'AA:BB:CC:DD:EE:FF:00:11:22:33:44:55:66:77:88:99:AA:BB:CC:DD:EE:FF:00:11:22:33:44:55:66:77:88:99:AA:BB',
        ]);

        $this->get('/.well-known/assetlinks.json')
            ->assertOk()
            ->assertJson([
                [
                    'relation' => ['delegate_permission/common.handle_all_urls'],
                    'target' => [
                        'namespace' => 'android_app',
                        'package_name' => 'me.kalfa.eventrsvp',
                        'sha256_cert_fingerprints' => [
                            'AA:BB:CC:DD:EE:FF:00:11:22:33:44:55:66:77:88:99:AA:BB:CC:DD:EE:FF:00:11:22:33:44:55:66:77:88:99:AA:BB',
                        ],
                    ],
                ],
            ]);
    }
}
