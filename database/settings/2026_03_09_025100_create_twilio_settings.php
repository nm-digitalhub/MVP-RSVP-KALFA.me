<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('twilio.sid', '');
        $this->migrator->add('twilio.token', '');
        $this->migrator->add('twilio.number', '');
        $this->migrator->add('twilio.messaging_service_sid', '');
        $this->migrator->add('twilio.verify_sid', '');
        $this->migrator->add('twilio.api_key', '');
        $this->migrator->add('twilio.api_secret', '');
        $this->migrator->add('twilio.is_active', true);
    }
};
