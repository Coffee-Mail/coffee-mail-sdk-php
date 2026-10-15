<?php

declare(strict_types=1);

use CoffeeMail\CoffeeMail;
use CoffeeMail\Exceptions\AuthenticationError;
use CoffeeMail\Exceptions\CoffeeMailException;
use CoffeeMail\Exceptions\ConflictError;
use CoffeeMail\Exceptions\ErrorCodes;
use CoffeeMail\Exceptions\ForbiddenError;
use CoffeeMail\Exceptions\InternalServerError;
use CoffeeMail\Exceptions\NotFoundError;
use CoffeeMail\Exceptions\PaymentRequiredError;
use CoffeeMail\Exceptions\PermissionError;
use CoffeeMail\Exceptions\RateLimitError;
use CoffeeMail\Exceptions\ValidationError;
use CoffeeMail\Http\CoffeeMailResponse;
use CoffeeMail\Http\ResponseParser;
use CoffeeMail\Tests\Support\FakeTransport;

test('status maps to canonical error with apiCode, requestId and merged details', function (): void {
    /** @var array<int, array{class-string<CoffeeMailException>, string}> $table */
    $table = [
        400 => [ValidationError::class, ErrorCodes::VALIDATION_ERROR],
        401 => [AuthenticationError::class, ErrorCodes::UNAUTHORIZED],
        402 => [PaymentRequiredError::class, ErrorCodes::PAYMENT_REQUIRED],
        403 => [ForbiddenError::class, ErrorCodes::FORBIDDEN],
        404 => [NotFoundError::class, ErrorCodes::NOT_FOUND],
        409 => [ConflictError::class, ErrorCodes::CONFLICT],
        429 => [RateLimitError::class, ErrorCodes::RATE_LIMIT_EXCEEDED],
        500 => [InternalServerError::class, ErrorCodes::INTERNAL_SERVER_ERROR],
        502 => [InternalServerError::class, ErrorCodes::INTERNAL_SERVER_ERROR],
        503 => [InternalServerError::class, ErrorCodes::INTERNAL_SERVER_ERROR],
        504 => [InternalServerError::class, ErrorCodes::INTERNAL_SERVER_ERROR],
    ];
    $body = '{"error":{"code":"API_SPECIFIC","message":"falhou","meta":{"issues":[1]},"details":{"campo":"to"}}}';

    foreach ($table as $status => [$class, $code]) {
        $response = ResponseParser::parse($status, $body, ['x-request-id' => 'req_123'], 'pt-BR');
        $error = $response->error;

        expect($error)->toBeInstanceOf($class)
            ->and($error?->status)->toBe($status)
            ->and($error?->errorCode)->toBe($code)
            ->and($error?->apiCode)->toBe('API_SPECIFIC')
            ->and($error?->requestId)->toBe('req_123')
            ->and($error?->getMessage())->toBe('falhou')
            ->and($error?->details)->toBe(['issues' => [1], 'campo' => 'to'])
            ->and($response->data)->toBeNull()
            ->and($response->statusCode)->toBe($status);
    }
});

test('unknown status keeps the API code or falls back to API_ERROR', function (): void {
    $withCode = ResponseParser::parse(422, '{"error":{"code":"INVALID_FROM_DOMAIN","message":"x"}}', [], 'pt-BR');
    $withoutCode = ResponseParser::parse(418, '{}', [], 'en');

    expect($withCode->error)->toBeInstanceOf(CoffeeMailException::class)
        ->and($withCode->error?->errorCode)->toBe('INVALID_FROM_DOMAIN')
        ->and($withoutCode->error?->errorCode)->toBe(ErrorCodes::API_ERROR)
        ->and($withoutCode->error?->getMessage())->toStartWith('An unexpected error');
});

test('missing messages fall back to the local catalog or the raw body', function (): void {
    $unauthenticated = ResponseParser::parse(401, '{"error":{"code":"UNAUTHENTICATED"}}', [], 'pt-BR');
    $rateLimited = ResponseParser::parse(429, '{}', ['retry-after' => '60'], 'en');
    $html = ResponseParser::parse(502, '<html>Bad Gateway</html>', [], 'pt-BR');
    $rootMessage = ResponseParser::parse(400, '{"message":"raiz"}', [], 'pt-BR');

    expect($unauthenticated->error?->getMessage())->toStartWith('Ocorreu um erro inesperado')
        ->and($rateLimited->error?->getMessage())->toStartWith('Rate limit exceeded')
        ->and($rateLimited->error)->toBeInstanceOf(RateLimitError::class)
        ->and($html->error?->getMessage())->toBe('<html>Bad Gateway</html>')
        ->and($rootMessage->error?->getMessage())->toBe('raiz');

    $error = $rateLimited->error;
    expect($error instanceof RateLimitError ? $error->retryAfterSeconds : null)->toBe(60);
});

test('invalid Retry-After is ignored', function (): void {
    $response = ResponseParser::parse(429, '{}', ['retry-after' => 'Wed, 21 Oct 2026 07:28:00 GMT'], 'pt-BR');
    $error = $response->error;

    expect($error instanceof RateLimitError ? $error->retryAfterSeconds : 'x')->toBeNull();
});

test('successful responses decode JSON, accept empty bodies and flag invalid JSON', function (): void {
    $ok = ResponseParser::parse(200, '{"id":"e1"}', [], 'pt-BR');
    $empty = ResponseParser::parse(204, '', [], 'pt-BR');
    $broken = ResponseParser::parse(200, '{"id":', ['x-request-id' => 'req_9'], 'en');

    expect($ok->data)->toBe(['id' => 'e1'])
        ->and($ok->error)->toBeNull()
        ->and($empty->isSuccess())->toBeTrue()
        ->and($empty->data)->toBeNull()
        ->and($broken->error?->errorCode)->toBe(ErrorCodes::INVALID_RESPONSE)
        ->and($broken->error?->requestId)->toBe('req_9')
        ->and($broken->error?->getMessage())->toStartWith('The server response could not be parsed');
});

test('broadcasts turn 403 into PermissionError requiring full_access', function (): void {
    $forbidden = ResponseParser::parse(403, '{"error":{"code":"FORBIDDEN","message":"proibido"}}', [], 'pt-BR');
    $transport = new FakeTransport(defaultResponse: $forbidden);
    $client = new CoffeeMail('cm_live_dummy', transport: $transport);

    $error = $client->broadcasts->send('bc_1')->error;

    expect($error)->toBeInstanceOf(PermissionError::class)
        ->and($error?->errorCode)->toBe(ErrorCodes::PERMISSION_DENIED)
        ->and($error?->apiCode)->toBe('FORBIDDEN')
        ->and($error?->details)->toBe(['operation' => 'broadcasts.send'])
        ->and($error?->getMessage())->toContain('full_access (called by broadcasts.send)')
        ->and($error instanceof PermissionError ? $error->requiredPermission : null)->toBe('full_access');
});

test('broadcasts keep other errors and successes untouched', function (): void {
    $notFound = ResponseParser::parse(404, '{"error":{"code":"NOT_FOUND"}}', [], 'pt-BR');
    $client = new CoffeeMail('cm_live_dummy', transport: new FakeTransport(defaultResponse: $notFound));
    $okClient = new CoffeeMail('cm_live_dummy', transport: new FakeTransport(
        defaultResponse: new CoffeeMailResponse(data: ['id' => 'bc_1'], error: null, statusCode: 200),
    ));

    expect($client->broadcasts->get('bc_1')->error)->toBeInstanceOf(NotFoundError::class)
        ->and($okClient->broadcasts->cancel('bc_1')->data)->toBe(['id' => 'bc_1']);

    $okClient->broadcasts->testSend('bc_1', ['to' => 'teste@gmail.com']);
    $okClient->broadcasts->list(['status' => 'sent']);
    $okClient->broadcasts->create(['audienceId' => 'a1', 'fromEmail' => 'n@e.com']);
    expect($okClient->broadcasts->get('bc_1')->isSuccess())->toBeTrue();
});
