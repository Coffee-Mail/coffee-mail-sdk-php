<?php

declare(strict_types=1);

use CoffeeMail\CoffeeMail;
use CoffeeMail\Http\CoffeeMailResponse;
use CoffeeMail\Resources\Webhooks;
use CoffeeMail\Tests\Support\FakeTransport;
use CoffeeMail\Webhooks\WebhookVerifier;

test('verifySignature matches the shared API vectors', function (): void {
    /** @var array{vectors: list<array{name: string, secret: string, body: string, timestamp: string,
     *     idempotencyKey: string, signature: string, nowEpochSeconds: int, toleranceSeconds: int,
     *     expected: bool}>} $file */
    $file = json_decode((string) file_get_contents(__DIR__ . '/../Fixtures/webhook-vectors.json'), true);

    expect($file['vectors'])->toHaveCount(11);

    foreach ($file['vectors'] as $vector) {
        $valid = Webhooks::verifySignature(
            payload: $vector['body'],
            secret: $vector['secret'],
            signature: $vector['signature'],
            timestamp: $vector['timestamp'],
            idempotencyKey: $vector['idempotencyKey'],
            toleranceSeconds: $vector['toleranceSeconds'],
            now: $vector['nowEpochSeconds'],
        );

        expect($valid)->toBe($vector['expected'], $vector['name']);
    }
});

test('verifySignature reads the x-webhook-* headers in any case and as lists', function (): void {
    $payload = '{"event_type":"email.delivered","webhook_id":"w1","data":{}}';
    $signature = WebhookVerifier::sign($payload, 'whsec_x', '1760000000', 'ed4e7012eb856296');

    $headers = [
        'X-Webhook-Signature' => [$signature],
        'x-webhook-timestamp' => '1760000000',
        'X-WEBHOOK-IDEMPOTENCY-KEY' => ['ed4e7012eb856296'],
    ];

    expect(Webhooks::verifySignature($payload, 'whsec_x', $headers, now: 1760000100))->toBeTrue()
        ->and(Webhooks::verifySignature($payload, 'whsec_y', $headers, now: 1760000100))->toBeFalse()
        ->and(Webhooks::verifySignature($payload . ' ', 'whsec_x', $headers, now: 1760000100))->toBeFalse();
});

test('verifySignature rejects missing headers, empty secret and stale timestamps', function (): void {
    $payload = '{}';
    $signature = WebhookVerifier::sign($payload, 'whsec_x', '1760000000', 'k1');

    expect(Webhooks::verifySignature($payload, 'whsec_x', ['x-webhook-signature' => $signature], now: 1760000000))
        ->toBeFalse()
        ->and(Webhooks::verifySignature(
            $payload,
            '',
            signature: $signature,
            timestamp: '1760000000',
            idempotencyKey: 'k1',
            now: 1760000000,
        ))->toBeFalse()
        ->and(Webhooks::verifySignature(
            $payload,
            'whsec_x',
            signature: $signature,
            timestamp: '1760000000',
            idempotencyKey: 'k1',
        ))->toBeFalse()
        ->and(Webhooks::verifySignature(
            $payload,
            'whsec_x',
            signature: $signature,
            timestamp: '1760000000',
            idempotencyKey: 'k1',
            toleranceSeconds: 10,
            now: 1760000011,
        ))->toBeFalse();
});

test('webhooks resource handles full lifecycle CRUD, toggle, rotateSecret, test and deliveries', function (): void {
    $mockTransport = new FakeTransport(
        defaultResponse: new CoffeeMailResponse(
            data: ['id' => 'wh_123'],
            error: null,
            statusCode: 200,
        )
    );

    $client = new CoffeeMail('cm_live_dummy', transport: $mockTransport);

    $client->webhooks->create([
        'url' => 'https://api.empresa.com.br/webhook',
        'events' => ['email.delivered', 'email.bounced'],
    ]);
    expect($mockTransport->lastMethod)->toBe('POST')
        ->and($mockTransport->lastPath)->toBe('/v1/product/webhooks');

    $client->webhooks->list();
    expect($mockTransport->lastMethod)->toBe('GET')
        ->and($mockTransport->lastPath)->toBe('/v1/product/webhooks');

    $client->webhooks->get('wh_123');
    expect($mockTransport->lastMethod)->toBe('GET')
        ->and($mockTransport->lastPath)->toBe('/v1/product/webhooks/wh_123');

    $client->webhooks->update('wh_123', ['events' => ['email.clicked']]);
    expect($mockTransport->lastMethod)->toBe('PUT')
        ->and($mockTransport->lastPath)->toBe('/v1/product/webhooks/wh_123');

    $client->webhooks->toggle('wh_123', false);
    expect($mockTransport->lastMethod)->toBe('PATCH')
        ->and($mockTransport->lastPath)->toBe('/v1/product/webhooks/wh_123')
        ->and($mockTransport->lastBody)->toBe(['status' => 'paused']);

    $client->webhooks->rotateSecret('wh_123');
    expect($mockTransport->lastMethod)->toBe('POST')
        ->and($mockTransport->lastPath)->toBe('/v1/product/webhooks/wh_123/rotate-secret');

    $client->webhooks->test('wh_123');
    expect($mockTransport->lastMethod)->toBe('POST')
        ->and($mockTransport->lastPath)->toBe('/v1/product/webhooks/wh_123/test');

    $client->webhooks->listDeliveries('wh_123');
    expect($mockTransport->lastMethod)->toBe('GET')
        ->and($mockTransport->lastPath)->toBe('/v1/product/webhooks/wh_123/deliveries');

    $client->webhooks->delete('wh_123');
    expect($mockTransport->lastMethod)->toBe('DELETE')
        ->and($mockTransport->lastPath)->toBe('/v1/product/webhooks/wh_123');
});
