<?php

declare(strict_types=1);

use CoffeeMail\CoffeeMail;
use CoffeeMail\Http\CoffeeMailResponse;
use CoffeeMail\Http\HttpTransportInterface;

test('complementary resources route properly to their corresponding REST endpoints', function (): void {
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

    // Domains
    $client->domains->create(['name' => 'dominio.com.br']);
    expect($lastMethod)->toBe('POST')->and($lastPath)->toBe('/v1/product/domains');

    $client->domains->verify('dom_123');
    expect($lastMethod)->toBe('POST')->and($lastPath)->toBe('/v1/product/domains/dom_123/verify');

    $client->domains->get('dom_123');
    expect($lastMethod)->toBe('GET')->and($lastPath)->toBe('/v1/product/domains/dom_123');

    $client->domains->delete('dom_123');
    expect($lastMethod)->toBe('DELETE')->and($lastPath)->toBe('/v1/product/domains/dom_123');

    // Templates
    $client->templates->create(['name' => 'Boas-vindas', 'html' => '<h1>Oi</h1>']);
    expect($lastMethod)->toBe('POST')->and($lastPath)->toBe('/v1/product/templates');

    $client->templates->preview('tpl_123', ['nome' => 'João']);
    expect($lastMethod)->toBe('POST')
        ->and($lastPath)->toBe('/v1/product/templates/tpl_123/preview')
        ->and($lastBody)->toBe(['variables' => ['nome' => 'João']]);

    // Audiences
    $client->audiences->create(['name' => 'Clientes VIP']);
    expect($lastMethod)->toBe('POST')->and($lastPath)->toBe('/v1/product/audiences');

    $client->audiences->addContact('aud_123', ['email' => 'vip@gmail.com']);
    expect($lastMethod)->toBe('POST')->and($lastPath)->toBe('/v1/product/audiences/aud_123/contacts');

    $client->audiences->bulkAddContacts('aud_123', [['email' => 'vip2@gmail.com']]);
    expect($lastMethod)->toBe('POST')->and($lastPath)->toBe('/v1/product/audiences/aud_123/contacts/bulk');

    $client->audiences->removeContact('aud_123', 'cnt_456');
    expect($lastMethod)->toBe('DELETE')->and($lastPath)->toBe('/v1/product/audiences/aud_123/contacts/cnt_456');

    // Broadcasts
    $client->broadcasts->create(['name' => 'Campanha Black Friday', 'audienceId' => 'aud_123']);
    expect($lastMethod)->toBe('POST')->and($lastPath)->toBe('/v1/product/broadcasts');

    $client->broadcasts->send('bc_123');
    expect($lastMethod)->toBe('POST')->and($lastPath)->toBe('/v1/product/broadcasts/bc_123/send');

    $client->broadcasts->cancel('bc_123');
    expect($lastMethod)->toBe('POST')->and($lastPath)->toBe('/v1/product/broadcasts/bc_123/cancel');

    // Suppressions
    $client->suppressions->create(['email' => 'bounce@gmail.com', 'reason' => 'hard_bounce']);
    expect($lastMethod)->toBe('POST')->and($lastPath)->toBe('/v1/product/suppressions');

    $client->suppressions->get('bounce@gmail.com');
    expect($lastMethod)->toBe('GET')->and($lastPath)->toBe('/v1/product/suppressions/bounce%40gmail.com');

    $client->suppressions->delete('bounce@gmail.com');
    expect($lastMethod)->toBe('DELETE')->and($lastPath)->toBe('/v1/product/suppressions/bounce%40gmail.com');

    // Stats
    $client->stats->get(['from' => '2026-01-01', 'to' => '2026-01-31']);
    expect($lastMethod)->toBe('GET')->and($lastPath)->toBe('/v1/product/stats');
});
