<?php

declare(strict_types=1);

namespace CoffeeMail\Exceptions;

use Throwable;

final class RateLimitError extends CoffeeMailException
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(
        string $message = 'Limite de requisições excedido.',
        public readonly ?int $retryAfterSeconds = null,
        array $details = [],
        ?Throwable $previous = null,
        ?string $apiCode = null,
        ?string $requestId = null,
    ) {
        parent::__construct(
            message: $message,
            status: 429,
            errorCode: ErrorCodes::RATE_LIMIT_EXCEEDED,
            details: $details,
            previous: $previous,
            apiCode: $apiCode,
            requestId: $requestId,
        );
    }
}
