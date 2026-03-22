<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Spatie\Mjml\Mjml;

abstract class MjmlMailable extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * Get the MJML view for the message.
     */
    abstract public function mjmlView(): string;

    /**
     * Get the data for the MJML view.
     */
    public function mjmlData(): array
    {
        return [];
    }

    /**
     * Get the locale used by the rendered email template.
     */
    protected function mailLanguage(): string
    {
        return app()->getLocale();
    }

    /**
     * Get the text direction used by the rendered email template.
     */
    protected function mailDirection(): string
    {
        return $this->mailLanguage() === 'he' ? 'rtl' : 'ltr';
    }

    /**
     * Get the default text alignment used by the rendered email template.
     */
    protected function mailTextAlign(): string
    {
        return $this->mailDirection() === 'rtl' ? 'right' : 'left';
    }

    /**
     * Resolve the MJML Blade template to its real file path.
     */
    protected function mjmlTemplatePath(): string
    {
        $view = $this->mjmlView();

        return resource_path(
            'views/'
            .str_replace('.', '/', Str::beforeLast($view, '.'))
            .'.'
            .Str::afterLast($view, '.')
            .'.blade.php'
        );
    }

    /**
     * Build the message and convert MJML to HTML.
     */
    public function build(): self
    {
        $mjml = Blade::render(
            File::get($this->mjmlTemplatePath()),
            [
                ...$this->mjmlData(),
                'mailLanguage' => $this->mailLanguage(),
                'mailDirection' => $this->mailDirection(),
                'mailTextAlign' => $this->mailTextAlign(),
            ],
            deleteCachedView: true
        );

        return $this->html(Mjml::new()->toHtml($mjml));
    }
}
