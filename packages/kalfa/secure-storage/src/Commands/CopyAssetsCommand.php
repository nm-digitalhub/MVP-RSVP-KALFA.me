<?php

declare(strict_types=1);

namespace Kalfa\SecureStorage\Commands;

use Native\Mobile\Plugins\Commands\NativePluginHookCommand;

/**
 * Hook command executed during the copy_assets phase of the NativePHP build.
 *
 * Add any asset-copying logic here for files that must land in specific
 * locations inside the native Android/iOS project before compilation.
 */
class CopyAssetsCommand extends NativePluginHookCommand
{
    protected $signature = 'nativephp:kalfa-secure-storage:copy-assets';

    protected $description = 'Copy assets for the kalfa/secure-storage plugin';

    public function handle(): int
    {
        if ($this->isAndroid()) {
            $this->copyAndroidAssets();
        }

        if ($this->isIos()) {
            $this->copyIosAssets();
        }

        return self::SUCCESS;
    }

    /**
     * Copy assets required for the Android build.
     */
    protected function copyAndroidAssets(): void
    {
        $this->info('Android assets copied for kalfa/secure-storage');
    }

    /**
     * Copy assets required for the iOS build.
     */
    protected function copyIosAssets(): void
    {
        $this->info('iOS assets copied for kalfa/secure-storage');
    }
}
