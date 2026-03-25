<?php

declare(strict_types=1);

/**
 * Plugin validation tests for kalfa/secure-storage.
 *
 * Run from the package root with:
 *   ../../vendor/bin/phpunit tests/
 */

use PHPUnit\Framework\TestCase;

class PluginTest extends TestCase
{
    private string $pluginPath;

    private string $manifestPath;

    protected function setUp(): void
    {
        $this->pluginPath = dirname(__DIR__);
        $this->manifestPath = $this->pluginPath.'/nativephp.json';
    }

    // -------------------------------------------------------------------------
    // Plugin Manifest
    // -------------------------------------------------------------------------

    /**
     * The nativephp.json file must exist and be valid JSON.
     */
    public function test_manifest_file_is_valid_json(): void
    {
        $this->assertFileExists($this->manifestPath);

        $content = file_get_contents($this->manifestPath);
        json_decode($content, true);

        $this->assertSame(JSON_ERROR_NONE, json_last_error(), 'nativephp.json is not valid JSON');
    }

    /**
     * The manifest must declare a namespace and bridge_functions.
     * Per v3 docs, package metadata (name/version/description) lives in
     * composer.json — not in nativephp.json.
     */
    public function test_manifest_has_required_fields(): void
    {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);

        $this->assertArrayHasKey('namespace', $manifest);
        $this->assertArrayHasKey('bridge_functions', $manifest);
        $this->assertSame('SecureStorage', $manifest['namespace']);

        // Package metadata must NOT be duplicated from composer.json
        $this->assertArrayNotHasKey('name', $manifest, 'name must not be duplicated in nativephp.json');
        $this->assertArrayNotHasKey('version', $manifest, 'version must not be duplicated in nativephp.json');
        $this->assertArrayNotHasKey('service_provider', $manifest, 'service_provider must not be duplicated in nativephp.json');
    }

    /**
     * Every bridge function must declare a name plus at least one native target.
     */
    public function test_manifest_has_valid_bridge_functions(): void
    {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);

        $this->assertIsArray($manifest['bridge_functions']);
        $this->assertNotEmpty($manifest['bridge_functions']);

        foreach ($manifest['bridge_functions'] as $function) {
            $this->assertArrayHasKey('name', $function);
            $this->assertTrue(
                isset($function['android']) || isset($function['ios']),
                "Bridge function '{$function['name']}' must declare at least one native target"
            );
        }
    }

    /**
     * Declared platform strings must be 'android' or 'ios'.
     */
    public function test_manifest_platforms_are_valid(): void
    {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);

        if (! isset($manifest['platforms'])) {
            $this->markTestSkipped('platforms key not present');
        }

        $this->assertIsArray($manifest['platforms']);
        $valid = ['android', 'ios'];
        foreach ($manifest['platforms'] as $platform) {
            $this->assertContains($platform, $valid, "Unknown platform: {$platform}");
        }
    }

    /**
     * Mobile shell loads the Laravel app over HTTPS; network_state in
     * config/nativephp.php expects ACCESS_NETWORK_STATE on Android.
     */
    public function test_android_declares_network_permissions_for_mobile_shell(): void
    {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);

        $this->assertIsArray($manifest['android']['permissions'] ?? null);
        $perms = $manifest['android']['permissions'];

        $this->assertContains('android.permission.INTERNET', $perms);
        $this->assertContains('android.permission.ACCESS_NETWORK_STATE', $perms);
    }

    /**
     * NativePHP Mobile v3 documents ios.info_plist and ios.dependencies — not
     * ios.permissions or ios.repositories (those are Android-oriented patterns).
     *
     * @see https://nativephp.com/docs/mobile/3/plugins/creating-plugins
     * @see https://nativephp.com/docs/mobile/3/plugins/permissions-dependencies
     */
    public function test_ios_manifest_uses_documented_nativephp_v3_structure(): void
    {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);
        $ios = $manifest['ios'];

        $this->assertIsArray($ios);
        $this->assertArrayHasKey('min_version', $ios);
        $this->assertArrayHasKey('dependencies', $ios);
        $this->assertIsArray($ios['dependencies']['swift_packages'] ?? null);
        $this->assertIsArray($ios['dependencies']['pods'] ?? null);

        $this->assertArrayNotHasKey(
            'permissions',
            $ios,
            'iOS permission copy belongs under ios.info_plist (NS*UsageDescription), not ios.permissions'
        );
        $this->assertArrayNotHasKey(
            'repositories',
            $ios,
            'Custom dependency repositories are declared under android.repositories only'
        );
    }

    // -------------------------------------------------------------------------
    // Native Code
    // -------------------------------------------------------------------------

    /**
     * The Android Kotlin source file must exist at the declared path.
     */
    public function test_android_kotlin_file_exists(): void
    {
        $file = $this->pluginPath.'/resources/android/src/SecureStorageFunctions.kt';

        $this->assertFileExists($file);

        $content = file_get_contents($file);
        $this->assertStringContainsString('package com.kalfa.plugins.secure_storage', $content);
        $this->assertStringContainsString('object SecureStorageFunctions', $content);
        $this->assertStringContainsString('BridgeFunction', $content);
    }

    /**
     * The iOS Swift source file must exist at the declared path.
     */
    public function test_ios_swift_file_exists(): void
    {
        $file = $this->pluginPath.'/resources/ios/Sources/SecureStorageFunctions.swift';

        $this->assertFileExists($file);

        $content = file_get_contents($file);
        $this->assertStringContainsString('enum SecureStorageFunctions', $content);
        $this->assertStringContainsString('BridgeFunction', $content);
    }

    /**
     * Every bridge function declared in the manifest must have a matching class
     * in both the Kotlin and Swift source files.
     */
    public function test_native_code_matches_bridge_function_declarations(): void
    {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);

        $kotlinFile = $this->pluginPath.'/resources/android/src/SecureStorageFunctions.kt';
        $swiftFile = $this->pluginPath.'/resources/ios/Sources/SecureStorageFunctions.swift';

        $this->assertFileExists($kotlinFile);
        $this->assertFileExists($swiftFile);

        $kotlinContent = file_get_contents($kotlinFile);
        $swiftContent = file_get_contents($swiftFile);

        foreach ($manifest['bridge_functions'] as $function) {
            if (isset($function['android'])) {
                $parts = explode('.', $function['android']);
                $className = end($parts);
                $this->assertStringContainsString(
                    "class {$className}",
                    $kotlinContent,
                    "Android Kotlin is missing class {$className}"
                );
            }

            if (isset($function['ios'])) {
                $parts = explode('.', $function['ios']);
                $className = end($parts);
                $this->assertStringContainsString(
                    "class {$className}",
                    $swiftContent,
                    "iOS Swift is missing class {$className}"
                );
            }
        }
    }

    // -------------------------------------------------------------------------
    // PHP Classes
    // -------------------------------------------------------------------------

    /**
     * The ServiceProvider must exist and declare the correct namespace and class.
     */
    public function test_service_provider_exists(): void
    {
        $file = $this->pluginPath.'/src/SecureStorageServiceProvider.php';

        $this->assertFileExists($file);

        $content = file_get_contents($file);
        $this->assertStringContainsString('namespace Kalfa\SecureStorage', $content);
        $this->assertStringContainsString('class SecureStorageServiceProvider', $content);
    }

    /**
     * The Facade class must exist and extend Illuminate Facade.
     */
    public function test_facade_exists(): void
    {
        $file = $this->pluginPath.'/src/Facades/SecureStorage.php';

        $this->assertFileExists($file);

        $content = file_get_contents($file);
        $this->assertStringContainsString('namespace Kalfa\SecureStorage\Facades', $content);
        $this->assertStringContainsString('class SecureStorage extends Facade', $content);
    }

    /**
     * The main implementation class must exist in the correct namespace.
     */
    public function test_main_class_exists(): void
    {
        $file = $this->pluginPath.'/src/SecureStorage.php';

        $this->assertFileExists($file);

        $content = file_get_contents($file);
        $this->assertStringContainsString('namespace Kalfa\SecureStorage', $content);
        $this->assertStringContainsString('class SecureStorage', $content);
    }

    // -------------------------------------------------------------------------
    // Composer Configuration
    // -------------------------------------------------------------------------

    /**
     * composer.json must be valid and declare the package as a nativephp-plugin.
     */
    public function test_composer_json_is_valid(): void
    {
        $composerPath = $this->pluginPath.'/composer.json';

        $this->assertFileExists($composerPath);

        $composer = json_decode(file_get_contents($composerPath), true);

        $this->assertSame(JSON_ERROR_NONE, json_last_error(), 'composer.json is not valid JSON');
        $this->assertSame('nativephp-plugin', $composer['type']);
        $this->assertSame('nativephp.json', $composer['extra']['nativephp']['manifest']);
    }

    // -------------------------------------------------------------------------
    // Lifecycle Hooks
    // -------------------------------------------------------------------------

    /**
     * Hook keys declared in the manifest must be from the allowed set.
     */
    public function test_hooks_configuration_is_valid(): void
    {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);

        if (! isset($manifest['hooks'])) {
            $this->markTestSkipped('hooks key not present');
        }

        $this->assertIsArray($manifest['hooks']);

        $validHooks = ['pre_compile', 'post_compile', 'copy_assets', 'post_build'];
        foreach (array_keys($manifest['hooks']) as $hook) {
            $this->assertContains($hook, $validHooks, "Unknown hook: {$hook}");
        }
    }

    /**
     * The copy_assets hook must be declared and backed by a command file.
     */
    public function test_copy_assets_hook_is_declared(): void
    {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);

        $this->assertArrayHasKey('hooks', $manifest);
        $this->assertArrayHasKey('copy_assets', $manifest['hooks']);
        $this->assertNotEmpty($manifest['hooks']['copy_assets']);

        $commandFile = $this->pluginPath.'/src/Commands/CopyAssetsCommand.php';
        $this->assertFileExists($commandFile);
    }

    /**
     * CopyAssetsCommand must extend NativePluginHookCommand.
     */
    public function test_copy_assets_command_extends_native_plugin_hook_command(): void
    {
        $file = $this->pluginPath.'/src/Commands/CopyAssetsCommand.php';
        $content = file_get_contents($file);

        $this->assertStringContainsString('extends NativePluginHookCommand', $content);
        $this->assertStringContainsString('use Native\Mobile\Plugins\Commands\NativePluginHookCommand', $content);
    }

    /**
     * The command $signature must match the hook value in nativephp.json.
     */
    public function test_copy_assets_command_signature_matches_manifest(): void
    {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);
        $expectedSignature = $manifest['hooks']['copy_assets'];

        $content = file_get_contents($this->pluginPath.'/src/Commands/CopyAssetsCommand.php');

        $this->assertStringContainsString(
            "\$signature = '{$expectedSignature}'",
            $content
        );
    }

    /**
     * The command must contain platform-specific branching for Android and iOS.
     */
    public function test_copy_assets_command_has_platform_methods(): void
    {
        $content = file_get_contents($this->pluginPath.'/src/Commands/CopyAssetsCommand.php');

        $this->assertStringContainsString('$this->isAndroid()', $content);
        $this->assertStringContainsString('$this->isIos()', $content);
    }

    /**
     * Assets configuration must be an array with android/ios sub-arrays when present.
     */
    public function test_assets_configuration_is_valid(): void
    {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);

        if (! isset($manifest['assets'])) {
            $this->markTestSkipped('assets key not present');
        }

        $this->assertIsArray($manifest['assets']);

        if (isset($manifest['assets']['android'])) {
            $this->assertIsArray($manifest['assets']['android']);
        }

        if (isset($manifest['assets']['ios'])) {
            $this->assertIsArray($manifest['assets']['ios']);
        }
    }
}
