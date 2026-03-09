<?php

declare(strict_types=1);

namespace App\Logging;

use Illuminate\Log\Logger;

class TruncateExceptionTap
{
    public function __invoke(Logger $logger): void
    {
        $logger->pushProcessor(new TruncateExceptionContextProcessor);
    }
}
