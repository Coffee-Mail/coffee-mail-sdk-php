<?php

declare(strict_types=1);

namespace CoffeeMail\Exceptions;

use Throwable;

final class NotFoundError extends CoffeeMailException
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(
        string $message = 'Recurso não encontrado.',
        array $details = [],
        ?Throwable $previous = null,
        ?string $apiCode = null,
        ?string $requestId = null,
    ) {
        parent::__construct(
            message: $message,
            status: 404,
            errorCode: ErrorCodes::NOT_FOUND,
            details: $details,
            previous: $previous,
            apiCode: $apiCode,
            requestId: $requestId,
        );
    }
}
