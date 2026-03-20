<?php

declare(strict_types=1);

namespace Kalfa\SecureStorage\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Kalfa\SecureStorage\SecureStorage
 */
class SecureStorage extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Kalfa\SecureStorage\SecureStorage::class;
    }
}
