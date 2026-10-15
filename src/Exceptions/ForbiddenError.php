<?php

declare(strict_types=1);

namespace CoffeeMail\Exceptions;

use Throwable;

final class ForbiddenError extends CoffeeMailException
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(
        string $message = 'Acesso negado ao recurso.',
        array $details = [],
        ?Throwable $previous = null,
        ?string $apiCode = null,
        ?string $requestId = null,
    ) {
        parent::__construct(
            message: $message,
            status: 403,
            errorCode: ErrorCodes::FORBIDDEN,
            details: $details,
            previous: $previous,
            apiCode: $apiCode,
            requestId: $requestId,
        );
    }
}
