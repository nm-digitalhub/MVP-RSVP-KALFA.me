<?php

declare(strict_types=1);

namespace Codeat3\BladeTeenyIcons;

use BladeUI\Icons\Factory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

final class BladeTeenyIconsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig();

        $this->callAfterResolving(Factory::class, function (Factory $factory, Container $container) {
            $config = $container->make('config')->get('blade-teeny-icons', []);

            $factory->add('teeny-icons', array_merge(['path' => __DIR__ . '/../resources/svg'], $config));
        });
    }

    private function registerConfig(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/blade-teeny-icons.php', 'blade-teeny-icons');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../resources/svg' => public_path('vendor/blade-teeny-icons'),
            ], 'blade-tni'); // TODO: update this alias with `blade-teeny-icons` in next major release

            $this->publishes([
                __DIR__ . '/../config/blade-teeny-icons.php' => $this->app->configPath('blade-teeny-icons.php'),
            ], 'blade-teeny-icons-config');
        }
    }
}
