<?php

declare(strict_types=1);

use CoffeeMail\CoffeeMail;
use CoffeeMail\Http\CoffeeMailResponse;
use CoffeeMail\Resources\Webhooks;
use CoffeeMail\Tests\Support\FakeTransport;

test('webhooks verifySignature validates authentic signatures and rejects tampering', function (): void {
    $secret = 'whsec_test_secret_key_123';
    $payload = '{"event":"email.delivered","data":{"id":"eml_123"}}';

    $validSignature = hash_hmac('sha256', $payload, $secret);

    // Assinatura legítima
    expect(Webhooks::verifySignature($payload, $validSignature, $secret))->toBeTrue();

    // Assinatura adulterada
    expect(Webhooks::verifySignature($payload, 'assinatura_invalida_adulterada', $secret))->toBeFalse();

    // Payload adulterado
    $tamperedPayload = '{"event":"email.bounced","data":{"id":"eml_123"}}';
    expect(Webhooks::verifySignature($tamperedPayload, $validSignature, $secret))->toBeFalse();

    // Secret divergente
    expect(Webhooks::verifySignature($payload, $validSignature, 'whsec_outro_segredo'))->toBeFalse();

    // Entradas vazias
    expect(Webhooks::verifySignature('', $validSignature, $secret))->toBeFalse()
        ->and(Webhooks::verifySignature($payload, '', $secret))->toBeFalse()
        ->and(Webhooks::verifySignature($payload, $validSignature, ''))->toBeFalse();
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
    expect($mockTransport->lastMethod)->toBe('PATCH')
        ->and($mockTransport->lastPath)->toBe('/v1/product/webhooks/wh_123');

    $client->webhooks->toggle('wh_123', false);
    expect($mockTransport->lastMethod)->toBe('PATCH')
        ->and($mockTransport->lastPath)->toBe('/v1/product/webhooks/wh_123/toggle')
        ->and($mockTransport->lastBody)->toBe(['enabled' => false]);

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
