<?php

declare(strict_types=1);

namespace CoffeeMail\Exceptions;

use Throwable;

final class AuthenticationError extends CoffeeMailException
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(
        string $message = 'Chave de API inválida, ausente ou expirada.',
        array $details = [],
        ?Throwable $previous = null,
        ?string $apiCode = null,
        ?string $requestId = null,
    ) {
        parent::__construct(
            message: $message,
            status: 401,
            errorCode: ErrorCodes::UNAUTHORIZED,
            details: $details,
            previous: $previous,
            apiCode: $apiCode,
            requestId: $requestId,
        );
    }
}
