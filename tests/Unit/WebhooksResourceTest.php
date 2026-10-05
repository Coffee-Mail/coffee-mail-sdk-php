<?php

declare(strict_types=1);

use CoffeeMail\CoffeeMail;
use CoffeeMail\Http\CoffeeMailResponse;
use CoffeeMail\Http\HttpTransportInterface;
use CoffeeMail\Resources\Webhooks;

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
    $lastMethod = null;
    $lastPath = null;
    $lastBody = null;

    $mockTransport = new class($lastMethod, $lastPath, $lastBody) implements HttpTransportInterface {
        public function __construct(
            public mixed &$method,
            public mixed &$path,
            public mixed &$body,
        ) {}

        public function request(string $method, string $path, ?array $body = null, array $query = [], array $headers = []): CoffeeMailResponse
        {
            $this->method = $method;
            $this->path = $path;
            $this->body = $body;

            return new CoffeeMailResponse(data: ['id' => 'wh_123'], error: null, statusCode: 200);
        }

        public function get(string $path, array $query = [], array $headers = []): CoffeeMailResponse
        {
            return $this->request('GET', $path, null, $query, $headers);
        }

        public function post(string $path, ?array $body = null, array $query = [], array $headers = []): CoffeeMailResponse
        {
            return $this->request('POST', $path, $body, $query, $headers);
        }

        public function put(string $path, ?array $body = null, array $query = [], array $headers = []): CoffeeMailResponse
        {
            return $this->request('PUT', $path, $body, $query, $headers);
        }

        public function patch(string $path, ?array $body = null, array $query = [], array $headers = []): CoffeeMailResponse
        {
            return $this->request('PATCH', $path, $body, $query, $headers);
        }

        public function delete(string $path, array $query = [], array $headers = []): CoffeeMailResponse
        {
            return $this->request('DELETE', $path, null, $query, $headers);
        }

        public function getLocale(): string
        {
            return 'pt-BR';
        }
    };

    $client = new CoffeeMail('cm_live_dummy', transport: $mockTransport);

    $client->webhooks->create([
        'url' => 'https://api.empresa.com.br/webhook',
        'events' => ['email.delivered', 'email.bounced'],
    ]);
    expect($lastMethod)->toBe('POST')->and($lastPath)->toBe('/v1/product/webhooks');

    $client->webhooks->list();
    expect($lastMethod)->toBe('GET')->and($lastPath)->toBe('/v1/product/webhooks');

    $client->webhooks->get('wh_123');
    expect($lastMethod)->toBe('GET')->and($lastPath)->toBe('/v1/product/webhooks/wh_123');

    $client->webhooks->update('wh_123', ['events' => ['email.clicked']]);
    expect($lastMethod)->toBe('PATCH')->and($lastPath)->toBe('/v1/product/webhooks/wh_123');

    $client->webhooks->toggle('wh_123', false);
    expect($lastMethod)->toBe('PATCH')
        ->and($lastPath)->toBe('/v1/product/webhooks/wh_123/toggle')
        ->and($lastBody)->toBe(['enabled' => false]);

    $client->webhooks->rotateSecret('wh_123');
    expect($lastMethod)->toBe('POST')->and($lastPath)->toBe('/v1/product/webhooks/wh_123/rotate-secret');

    $client->webhooks->test('wh_123');
    expect($lastMethod)->toBe('POST')->and($lastPath)->toBe('/v1/product/webhooks/wh_123/test');

    $client->webhooks->listDeliveries('wh_123');
    expect($lastMethod)->toBe('GET')->and($lastPath)->toBe('/v1/product/webhooks/wh_123/deliveries');

    $client->webhooks->delete('wh_123');
    expect($lastMethod)->toBe('DELETE')->and($lastPath)->toBe('/v1/product/webhooks/wh_123');
});
