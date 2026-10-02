<?php

declare(strict_types=1);

namespace CoffeeMail\Exceptions;

use Throwable;

final class InternalServerError extends CoffeeMailException
{
    public function __construct(
        string $message = 'Erro interno do servidor da API.',
        mixed $details = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            status: 500,
            errorCode: 'INTERNAL_ERROR',
            details: $details,
            previous: $previous,
        );
    }
}
