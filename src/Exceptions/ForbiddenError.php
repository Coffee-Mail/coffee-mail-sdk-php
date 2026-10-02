<?php

declare(strict_types=1);

namespace CoffeeMail\Exceptions;

use Throwable;

final class ForbiddenError extends CoffeeMailException
{
    public function __construct(
        string $message = 'Permissão insuficiente para acessar este recurso.',
        mixed $details = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            status: 403,
            errorCode: 'FORBIDDEN',
            details: $details,
            previous: $previous,
        );
    }
}
