<?php

declare(strict_types=1);

namespace CoffeeMail\Exceptions;

use Throwable;

final class ValidationError extends CoffeeMailException
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(
        string $message = 'Dados inválidos.',
        array $details = [],
        ?Throwable $previous = null,
        ?string $apiCode = null,
        ?string $requestId = null,
    ) {
        parent::__construct(
            message: $message,
            status: 400,
            errorCode: ErrorCodes::VALIDATION_ERROR,
            details: $details,
            previous: $previous,
            apiCode: $apiCode,
            requestId: $requestId,
        );
    }
}
