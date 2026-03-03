<?php

declare(strict_types=1);

namespace App\Enums;

enum RsvpResponseType: string
{
    case Yes = 'yes';
    case No = 'no';
    case Maybe = 'maybe';
}
