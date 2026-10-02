<?php

declare(strict_types=1);

namespace CoffeeMail\Exceptions;

use Throwable;

final class RateLimitError extends CoffeeMailException
{
    public function __construct(
        string $message = 'Limite de requisições excedido.',
        public readonly ?int $retryAfterSeconds = null,
        mixed $details = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            status: 429,
            errorCode: 'RATE_LIMIT_EXCEEDED',
            details: $details,
            previous: $previous,
        );
    }
}
