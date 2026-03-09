<?php

use App\Console\Commands\ProductEngine\CheckIntegrityCommand;
use App\Console\Commands\ProductEngine\ProcessTrialExpirationsCommand;
use App\Services\ProductEngineOperationsMonitor;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

$applyFrequency = static function ($event, array $config) {
    $frequency = $config['frequency'] ?? 'hourly';

    return match ($frequency) {
        'everyMinute' => $event->everyMinute(),
        'everyFiveMinutes' => $event->everyFiveMinutes(),
        'everyTenMinutes' => $event->everyTenMinutes(),
        'everyFifteenMinutes' => $event->everyFifteenMinutes(),
        'everyThirtyMinutes' => $event->everyThirtyMinutes(),
        'hourly' => $event->hourly(),
        'daily' => $event->daily(),
        default => $event->hourly(),
    };
};

$attachMonitoringHooks = static function ($event, string $task) {
    return $event
        ->before(fn (): mixed => app(ProductEngineOperationsMonitor::class)->recordTaskStarted($task))
        ->onSuccess(fn (): mixed => app(ProductEngineOperationsMonitor::class)->recordTaskFinished($task, true))
        ->onFailure(fn (): mixed => app(ProductEngineOperationsMonitor::class)->recordTaskFinished($task, false));
};

Schedule::call(fn (): mixed => app(ProductEngineOperationsMonitor::class)->recordSchedulerHeartbeat())
    ->name('Product engine scheduler heartbeat')
    ->everyMinute();

$trialExpirationConfig = config('product-engine.operations.trial_expirations', []);

if (($trialExpirationConfig['enabled'] ?? true) === true) {
    $event = Schedule::command(ProcessTrialExpirationsCommand::class)
        ->description('Process expired trial subscriptions')
        ->withoutOverlapping()
        ->runInBackground();

    $attachMonitoringHooks($event, 'trial_expirations');
    $applyFrequency($event, $trialExpirationConfig);
}

$integrityCheckConfig = config('product-engine.operations.integrity_checks', []);

if (($integrityCheckConfig['enabled'] ?? true) === true) {
    $parameters = (($integrityCheckConfig['fail_on_issues'] ?? true) === true)
        ? ['--fail-on-issues' => true]
        : [];

    $event = Schedule::command(CheckIntegrityCommand::class, $parameters)
        ->description('Check product engine integrity')
        ->withoutOverlapping()
        ->runInBackground();

    $attachMonitoringHooks($event, 'integrity_checks');
    $applyFrequency($event, $integrityCheckConfig);
}
