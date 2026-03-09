<?php

declare(strict_types=1);

namespace App\Enums;

enum AccountProductStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Revoked = 'revoked';
}
