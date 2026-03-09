<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('sumit.company_id', '');
        $this->migrator->add('sumit.private_key', '');
        $this->migrator->add('sumit.public_key', '');
        $this->migrator->add('sumit.environment', 'www');
        $this->migrator->add('sumit.is_active', true);
        $this->migrator->add('sumit.is_test_mode', false);
    }
};
