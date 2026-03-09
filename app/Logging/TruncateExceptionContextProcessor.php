<?php

declare(strict_types=1);

namespace App\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Truncates exception context in log records to avoid dumping huge HTML/email bodies.
 */
class TruncateExceptionContextProcessor implements ProcessorInterface
{
    private const MAX_CONTEXT_CHARS = 2000;

    public function __invoke(LogRecord $record): LogRecord
    {
        $context = $record->context;
        if (! isset($context['exception']) || ! is_object($context['exception'])) {
            return $record;
        }

        $str = (string) $context['exception'];
        if (strlen($str) <= self::MAX_CONTEXT_CHARS) {
            return $record;
        }

        $context['exception'] = substr($str, 0, self::MAX_CONTEXT_CHARS)
            ."\n... [truncated ".(strlen($str) - self::MAX_CONTEXT_CHARS).' chars]';

        return $record->with(context: $context);
    }
}
