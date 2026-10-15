<?php

declare(strict_types=1);

namespace CoffeeMail\Exceptions;

use Throwable;

final class ConflictError extends CoffeeMailException
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(
        string $message = 'Conflito com um recurso existente.',
        array $details = [],
        ?Throwable $previous = null,
        ?string $apiCode = null,
        ?string $requestId = null,
    ) {
        parent::__construct(
            message: $message,
            status: 409,
            errorCode: ErrorCodes::CONFLICT,
            details: $details,
            previous: $previous,
            apiCode: $apiCode,
            requestId: $requestId,
        );
    }
}
