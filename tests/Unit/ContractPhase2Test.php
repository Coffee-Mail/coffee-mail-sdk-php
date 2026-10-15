<?php

declare(strict_types=1);

use CoffeeMail\CoffeeMail;
use CoffeeMail\Exceptions\ConflictError;
use CoffeeMail\Exceptions\NotFoundError;
use CoffeeMail\Http\CoffeeMailResponse;
use CoffeeMail\Http\ResponseParser;
use CoffeeMail\Tests\Support\FakeTransport;

test('senders resendVerification posts to the resend route', function (): void {
    $transport = new FakeTransport(
        defaultResponse: new CoffeeMailResponse(data: null, error: null, statusCode: 204),
    );
    $client = new CoffeeMail('cm_live_dummy', transport: $transport);

    $response = $client->senders->resendVerification('s_1');

    expect($transport->lastMethod)->toBe('POST')
        ->and($transport->lastPath)->toBe('/v1/product/senders/s_1/resend-verification')
        ->and($response->isSuccess())->toBeTrue();
});

test('senders resendVerification maps conflict and not found to typed errors', function (): void {
    $conflictBody = '{"error":{"code":"CONFLICT","message":"sender is already verified"}}';
    $conflict = ResponseParser::parse(409, $conflictBody, [], 'pt-BR');
    $notFound = ResponseParser::parse(404, '{"error":{"code":"NOT_FOUND"}}', [], 'pt-BR');

    $conflicted = new CoffeeMail('cm_live_dummy', transport: new FakeTransport(defaultResponse: $conflict));
    $missing = new CoffeeMail('cm_live_dummy', transport: new FakeTransport(defaultResponse: $notFound));

    expect($conflicted->senders->resendVerification('s_1')->error)->toBeInstanceOf(ConflictError::class)
        ->and($missing->senders->resendVerification('s_x')->error)->toBeInstanceOf(NotFoundError::class);
});

test('broadcasts create forwards scheduledAt and get exposes metrics', function (): void {
    $transport = new FakeTransport(defaultResponse: new CoffeeMailResponse(
        data: ['id' => 'bc_1', 'status' => 'sent', 'metrics' => ['delivered' => 9, 'opened' => 4]],
        error: null,
        statusCode: 200,
    ));
    $client = new CoffeeMail('cm_live_dummy', transport: $transport);

    $client->broadcasts->create([
        'audienceId' => 'a1',
        'fromEmail' => 'news@acme.com',
        'templateId' => 'tpl_1',
        'scheduledAt' => '2030-01-01T10:00:00.000Z',
    ]);
    expect($transport->lastBody['scheduledAt'] ?? null)->toBe('2030-01-01T10:00:00.000Z');

    $detail = $client->broadcasts->get('bc_1')->data;
    $metrics = is_array($detail) && is_array($detail['metrics'] ?? null) ? $detail['metrics'] : [];
    expect($metrics['delivered'] ?? null)->toBe(9);
});
