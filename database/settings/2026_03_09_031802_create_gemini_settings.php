<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('gemini.api_key', '');
        $this->migrator->add('gemini.model', 'models/gemini-2.0-flash-exp');
        $this->migrator->add('gemini.is_active', true);
    }
};
