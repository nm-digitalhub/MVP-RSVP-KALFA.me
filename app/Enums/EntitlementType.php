<?php

declare(strict_types=1);

namespace App\Enums;

enum EntitlementType: string
{
    case Boolean = 'boolean';
    case Number = 'number';
    case Text = 'text';
    case Enum = 'enum';

    public function label(): string
    {
        return match ($this) {
            self::Boolean => __('Toggle'),
            self::Number => __('Limit'),
            self::Text => __('Value'),
            self::Enum => __('Option'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Boolean => 'tni-toggle',
            self::Number => 'heroicon-o-chart-bar',
            self::Text => 'heroicon-o-document-text',
            self::Enum => 'heroicon-o-chevron-up-down',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Boolean => 'indigo',
            self::Number => 'emerald',
            self::Text => 'amber',
            self::Enum => 'purple',
        };
    }
}
