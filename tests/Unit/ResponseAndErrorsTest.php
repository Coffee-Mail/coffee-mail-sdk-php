<?php

declare(strict_types=1);

use CoffeeMail\Exceptions\AuthenticationError;
use CoffeeMail\Exceptions\CoffeeMailException;
use CoffeeMail\Exceptions\ConflictError;
use CoffeeMail\Exceptions\ForbiddenError;
use CoffeeMail\Exceptions\NetworkException;
use CoffeeMail\Exceptions\NotFoundError;
use CoffeeMail\Exceptions\PaymentRequiredError;
use CoffeeMail\Exceptions\RateLimitError;
use CoffeeMail\Exceptions\ValidationError;
use CoffeeMail\Http\CoffeeMailResponse;

test('response success scenario supports object properties and tuple destructuring', function (): void {
    $payload = ['id' => 'eml_123', 'status' => 'queued'];
    /** @var CoffeeMailResponse<array{id: string, status: string}> $response */
    $response = new CoffeeMailResponse(data: $payload, error: null, statusCode: 200);

    expect($response->isSuccess())->toBeTrue()
        ->and($response->isError())->toBeFalse()
        ->and($response->data)->toBe($payload)
        ->and($response->error)->toBeNull()
        ->and($response->statusCode)->toBe(200)
        ->and($response->unwrap())->toBe($payload);

    [$data, $error] = $response;

    expect($data)->toBe($payload)
        ->and($error)->toBeNull();
});

test('response error scenario supports unwrap exception throwing and tuple destructuring', function (): void {
    $validationError = new ValidationError('Dados de e-mail inválidos', ['to' => 'Email obrigatório']);
    /** @var CoffeeMailResponse<null> $response */
    $response = new CoffeeMailResponse(data: null, error: $validationError, statusCode: 400);

    expect($response->isSuccess())->toBeFalse()
        ->and($response->isError())->toBeTrue()
        ->and($response->data)->toBeNull()
        ->and($response->error)->toBe($validationError)
        ->and($response->statusCode)->toBe(400);

    [$data, $error] = $response;

    expect($data)->toBeNull()
        ->and($error)->toBe($validationError);

    expect(fn () => $response->unwrap())->toThrow(ValidationError::class, 'Dados de e-mail inválidos');
});

test('rate limit error properly exposes retryAfterSeconds and status 429', function (): void {
    $error = new RateLimitError('Taxa limite excedida', retryAfterSeconds: 60);

    expect($error->status)->toBe(429)
        ->and($error->errorCode)->toBe('RATE_LIMIT_EXCEEDED')
        ->and($error->getErrorCode())->toBe('RATE_LIMIT_EXCEEDED')
        ->and($error->retryAfterSeconds)->toBe(60);
});

test('error hierarchy inherits from CoffeeMailException with specialized properties', function (): void {
    $errors = [
        new ValidationError('Erro 400', ['field' => 'invalido']),
        new AuthenticationError('Erro 401'),
        new PaymentRequiredError('Erro 402'),
        new ForbiddenError('Erro 403'),
        new NotFoundError('Erro 404'),
        new ConflictError('Erro 409'),
        new NetworkException('Falha de conexão'),
    ];

    foreach ($errors as $error) {
        expect($error)->toBeInstanceOf(CoffeeMailException::class)
            ->and($error->getMessage())->not->toBeEmpty()
            ->and($error->errorCode)->not->toBeEmpty();
    }
});
