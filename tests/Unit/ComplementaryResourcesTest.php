<?php

declare(strict_types=1);

use CoffeeMail\CoffeeMail;
use CoffeeMail\Http\CoffeeMailResponse;
use CoffeeMail\Tests\Support\FakeTransport;

test('complementary resources route properly to their corresponding REST endpoints', function (): void {
    $mockTransport = new FakeTransport(
        defaultResponse: new CoffeeMailResponse(
            data: ['ok' => true],
            error: null,
            statusCode: 200,
        )
    );

    $client = new CoffeeMail('cm_live_dummy', transport: $mockTransport);

    // Domains
    $client->domains->create(['name' => 'dominio.com.br']);
    expect($mockTransport->lastMethod)->toBe('POST')
        ->and($mockTransport->lastPath)->toBe('/v1/product/domains');

    $client->domains->verify('dom_123');
    expect($mockTransport->lastMethod)->toBe('POST')
        ->and($mockTransport->lastPath)->toBe('/v1/product/domains/dom_123/verify');

    $client->domains->get('dom_123');
    expect($mockTransport->lastMethod)->toBe('GET')
        ->and($mockTransport->lastPath)->toBe('/v1/product/domains/dom_123');

    $client->domains->delete('dom_123');
    expect($mockTransport->lastMethod)->toBe('DELETE')
        ->and($mockTransport->lastPath)->toBe('/v1/product/domains/dom_123');

    // Templates
    $client->templates->create(['name' => 'Boas-vindas', 'html' => '<h1>Oi</h1>']);
    expect($mockTransport->lastMethod)->toBe('POST')
        ->and($mockTransport->lastPath)->toBe('/v1/product/templates');

    $client->templates->preview('tpl_123', ['nome' => 'João']);
    expect($mockTransport->lastMethod)->toBe('POST')
        ->and($mockTransport->lastPath)->toBe('/v1/product/templates/tpl_123/preview')
        ->and($mockTransport->lastBody)->toBe(['variables' => ['nome' => 'João']]);

    // Audiences
    $client->audiences->create(['name' => 'Clientes VIP']);
    expect($mockTransport->lastMethod)->toBe('POST')
        ->and($mockTransport->lastPath)->toBe('/v1/product/audiences');

    $client->audiences->addContact('aud_123', ['email' => 'vip@gmail.com']);
    expect($mockTransport->lastMethod)->toBe('POST')
        ->and($mockTransport->lastPath)->toBe('/v1/product/audiences/aud_123/contacts');

    $client->audiences->bulkAddContacts('aud_123', [['email' => 'vip2@gmail.com']]);
    expect($mockTransport->lastMethod)->toBe('POST')
        ->and($mockTransport->lastPath)->toBe('/v1/product/audiences/aud_123/contacts/bulk');

    $client->audiences->removeContact('aud_123', 'cnt_456');
    expect($mockTransport->lastMethod)->toBe('DELETE')
        ->and($mockTransport->lastPath)->toBe('/v1/product/audiences/aud_123/contacts/cnt_456');

    // Broadcasts
    $client->broadcasts->create(['name' => 'Campanha Black Friday', 'audienceId' => 'aud_123']);
    expect($mockTransport->lastMethod)->toBe('POST')
        ->and($mockTransport->lastPath)->toBe('/v1/product/broadcasts');

    $client->broadcasts->send('bc_123');
    expect($mockTransport->lastMethod)->toBe('POST')
        ->and($mockTransport->lastPath)->toBe('/v1/product/broadcasts/bc_123/send');

    $client->broadcasts->cancel('bc_123');
    expect($mockTransport->lastMethod)->toBe('POST')
        ->and($mockTransport->lastPath)->toBe('/v1/product/broadcasts/bc_123/cancel');

    // Suppressions
    $client->suppressions->create(['email' => 'bounce@gmail.com', 'reason' => 'hard_bounce']);
    expect($mockTransport->lastMethod)->toBe('POST')
        ->and($mockTransport->lastPath)->toBe('/v1/product/suppressions');

    $client->suppressions->get('bounce@gmail.com');
    expect($mockTransport->lastMethod)->toBe('GET')
        ->and($mockTransport->lastPath)->toBe('/v1/product/suppressions/bounce%40gmail.com');

    $client->suppressions->delete('bounce@gmail.com');
    expect($mockTransport->lastMethod)->toBe('DELETE')
        ->and($mockTransport->lastPath)->toBe('/v1/product/suppressions/bounce%40gmail.com');

    // Stats
    $client->stats->get(['from' => '2026-01-01', 'to' => '2026-01-31']);
    expect($mockTransport->lastMethod)->toBe('GET')
        ->and($mockTransport->lastPath)->toBe('/v1/product/stats');
});
