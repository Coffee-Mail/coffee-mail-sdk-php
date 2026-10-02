<?php

declare(strict_types=1);

namespace CoffeeMail\Exceptions;

use Throwable;

final class AuthenticationError extends CoffeeMailException
{
    public function __construct(
        string $message = 'Chave de API inválida, ausente ou expirada.',
        mixed $details = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            status: 401,
            errorCode: 'UNAUTHORIZED',
            details: $details,
            previous: $previous,
        );
    }
}
