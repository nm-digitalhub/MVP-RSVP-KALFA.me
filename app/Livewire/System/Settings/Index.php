<?php

declare(strict_types=1);

namespace App\Livewire\System\Settings;

use App\Settings\GeminiSettings;
use App\Settings\SumitSettings;
use App\Settings\TwilioSettings;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('System Settings')]
class Index extends Component
{
    public string $activeTab = 'sumit';

    public string $sumit_company_id = '';
    public string $sumit_private_key = '';
    public string $sumit_public_key = '';
    public string $sumit_environment = 'www';
    public bool $sumit_is_active = false;
    public bool $sumit_is_test_mode = false;

    public string $twilio_sid = '';
    public string $twilio_token = '';
    public string $twilio_number = '';
    public string $twilio_messaging_service_sid = '';
    public string $twilio_verify_sid = '';
    public string $twilio_api_key = '';
    public string $twilio_api_secret = '';
    public bool $twilio_is_active = false;

    public string $gemini_api_key = '';
    public string $gemini_model = 'models/gemini-2.0-flash-exp';
    public bool $gemini_is_active = false;

    public function mount(SumitSettings $sumit, TwilioSettings $twilio, GeminiSettings $gemini): void
    {
        $this->sumit_company_id = $sumit->company_id ?? '';
        $this->sumit_private_key = $sumit->private_key ?? '';
        $this->sumit_public_key = $sumit->public_key ?? '';
        $this->sumit_environment = $sumit->environment ?? 'www';
        $this->sumit_is_active = $sumit->is_active;
        $this->sumit_is_test_mode = $sumit->is_test_mode;

        $this->twilio_sid = $twilio->sid ?? '';
        $this->twilio_token = $twilio->token ?? '';
        $this->twilio_number = $twilio->number ?? '';
        $this->twilio_messaging_service_sid = $twilio->messaging_service_sid ?? '';
        $this->twilio_verify_sid = $twilio->verify_sid ?? '';
        $this->twilio_api_key = $twilio->api_key ?? '';
        $this->twilio_api_secret = $twilio->api_secret ?? '';
        $this->twilio_is_active = $twilio->is_active;

        $this->gemini_api_key = $gemini->api_key ?? '';
        $this->gemini_model = $gemini->model ?? 'models/gemini-2.0-flash-exp';
        $this->gemini_is_active = $gemini->is_active;
    }

    public function switchTab(string $tab): void
    {
        if (in_array($tab, ['sumit', 'twilio', 'gemini'], true)) {
            $this->activeTab = $tab;
            $this->resetValidation();
        }
    }

    public function saveSumit(SumitSettings $settings): void
    {
        $this->validate([
            'sumit_company_id' => 'required|string',
            'sumit_environment' => 'required|in:www,test',
            'sumit_is_active' => 'boolean',
            'sumit_is_test_mode' => 'boolean',
        ]);

        try {
            $settings->company_id = $this->sumit_company_id;
            $settings->private_key = $this->sumit_private_key;
            $settings->public_key = $this->sumit_public_key;
            $settings->environment = $this->sumit_environment;
            $settings->is_active = $this->sumit_is_active;
            $settings->is_test_mode = $this->sumit_is_test_mode;
            $settings->save();

            session()->flash('success', __('Sumit settings saved successfully.'));
        } catch (\Exception $e) {
            session()->flash('error', __('Failed to save Sumit settings.'));
        }
    }

    public function saveTwilio(TwilioSettings $settings): void
    {
        $this->validate([
            'twilio_sid' => 'required|string',
            'twilio_token' => 'required|string',
            'twilio_is_active' => 'boolean',
        ]);

        try {
            $settings->sid = $this->twilio_sid;
            $settings->token = $this->twilio_token;
            $settings->number = $this->twilio_number;
            $settings->messaging_service_sid = $this->twilio_messaging_service_sid;
            $settings->verify_sid = $this->twilio_verify_sid;
            $settings->api_key = $this->twilio_api_key;
            $settings->api_secret = $this->twilio_api_secret;
            $settings->is_active = $this->twilio_is_active;
            $settings->save();

            session()->flash('success', __('Twilio settings saved successfully.'));
        } catch (\Exception $e) {
            session()->flash('error', __('Failed to save Twilio settings.'));
        }
    }

    public function saveGemini(GeminiSettings $settings): void
    {
        $this->validate([
            'gemini_api_key' => 'required|string',
            'gemini_model' => 'required|string',
            'gemini_is_active' => 'boolean',
        ]);

        try {
            $settings->api_key = $this->gemini_api_key;
            $settings->model = $this->gemini_model;
            $settings->is_active = $this->gemini_is_active;
            $settings->save();

            session()->flash('success', __('Gemini settings saved successfully.'));
        } catch (\Exception $e) {
            session()->flash('error', __('Failed to save Gemini settings.'));
        }
    }

    public function render(): View
    {
        return view('livewire.system.settings.index');
    }
}
