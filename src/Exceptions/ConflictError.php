<?php

declare(strict_types=1);

namespace CoffeeMail\Exceptions;

use Throwable;

final class ConflictError extends CoffeeMailException
{
    public function __construct(
        string $message = 'Conflito com recurso existente.',
        mixed $details = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            status: 409,
            errorCode: 'CONFLICT',
            details: $details,
            previous: $previous,
        );
    }
}
