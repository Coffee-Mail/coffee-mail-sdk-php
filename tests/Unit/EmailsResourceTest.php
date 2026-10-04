<?php

declare(strict_types=1);

use CoffeeMail\CoffeeMail;
use CoffeeMail\Http\CoffeeMailResponse;
use CoffeeMail\Http\HttpTransportInterface;

test('emails send normalizes from and to and dispatches to POST /v1/product/emails', function (): void {
    $capturedPath = null;
    $capturedBody = null;
    $capturedHeaders = null;

    $mockTransport = new class($capturedPath, $capturedBody, $capturedHeaders) implements HttpTransportInterface {
        public function __construct(
            public mixed &$path,
            public mixed &$body,
            public mixed &$headers,
        ) {}

        public function request(string $method, string $path, ?array $body = null, array $query = [], array $headers = []): CoffeeMailResponse
        {
            $this->path = $path;
            $this->body = $body;
            $this->headers = $headers;

            return new CoffeeMailResponse(
                data: ['id' => 'eml_abc123', 'status' => 'queued'],
                error: null,
                statusCode: 201,
            );
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

    [$data, $error] = $client->emails->send([
        'from' => 'contato@seudominio.com.br',
        'to' => 'cliente@gmail.com',
        'subject' => 'Pedido #123 Confirmado',
        'html' => '<p>Seu pedido foi confirmado!</p>',
        'idempotencyKey' => 'idem_key_999',
        'isSandbox' => true,
    ]);

    assert(is_array($data));
    assert(is_array($capturedBody));
    assert(is_array($capturedHeaders));

    expect($error)->toBeNull()
        ->and($data)->toBeArray()
        ->and($data['id'])->toBe('eml_abc123')
        ->and($capturedPath)->toBe('/v1/product/emails')
        ->and($capturedBody['from'])->toBe(['email' => 'contato@seudominio.com.br'])
        ->and($capturedBody['to'])->toBe([['email' => 'cliente@gmail.com']])
        ->and($capturedBody['subject'])->toBe('Pedido #123 Confirmado')
        ->and($capturedHeaders['x-idempotency-key'])->toBe('idem_key_999')
        ->and($capturedHeaders['x-coffeemail-sandbox'])->toBe('true');
});

test('emails resource exposes batch, get, list, cancel, resend, tags and events operations', function (): void {
    $lastMethod = null;
    $lastPath = null;

    $mockTransport = new class($lastMethod, $lastPath) implements HttpTransportInterface {
        public function __construct(
            public mixed &$method,
            public mixed &$path,
        ) {}

        public function request(string $method, string $path, ?array $body = null, array $query = [], array $headers = []): CoffeeMailResponse
        {
            $this->method = $method;
            $this->path = $path;

            return new CoffeeMailResponse(data: ['ok' => true], error: null, statusCode: 200);
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

    $client->emails->get('eml_123');
    expect($lastMethod)->toBe('GET')->and($lastPath)->toBe('/v1/product/emails/eml_123');

    $client->emails->list(['status' => 'delivered', 'limit' => 20]);
    expect($lastMethod)->toBe('GET')->and($lastPath)->toBe('/v1/product/emails');

    $client->emails->getEvents('eml_123');
    expect($lastMethod)->toBe('GET')->and($lastPath)->toBe('/v1/product/emails/eml_123/events');

    $client->emails->getTags();
    expect($lastMethod)->toBe('GET')->and($lastPath)->toBe('/v1/product/emails/tags');

    $client->emails->cancel('eml_123');
    expect($lastMethod)->toBe('POST')->and($lastPath)->toBe('/v1/product/emails/eml_123/cancel');

    $client->emails->resend('eml_123');
    expect($lastMethod)->toBe('POST')->and($lastPath)->toBe('/v1/product/emails/eml_123/resend');

    $client->emails->sendBatch([
        ['from' => 'a@b.com', 'to' => 'c@d.com', 'subject' => 'S1'],
        ['from' => 'a@b.com', 'to' => 'e@f.com', 'subject' => 'S2'],
    ]);
    expect($lastMethod)->toBe('POST')->and($lastPath)->toBe('/v1/product/emails/batch');
});
