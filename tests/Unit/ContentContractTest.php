<?php

declare(strict_types=1);

use CoffeeMail\Exceptions\ConflictError;
use CoffeeMail\Exceptions\NotFoundError;
use CoffeeMail\Exceptions\RateLimitError;
use CoffeeMail\Exceptions\ValidationError;
use CoffeeMail\Http\CoffeeMailResponse;
use CoffeeMail\Http\ResponseParser;
use CoffeeMail\Tests\Support\DomainFixtures;
use CoffeeMail\Tests\Support\FakeTransport;

const CONTRACT_TEMPLATE_ID = '3c1e9a2b-4d5f-4a6b-8c7d-9e0f1a2b3c4d';
const CONTRACT_WEBHOOK_ID = '5a6b7c8d-9e0f-4a1b-8c2d-3e4f5a6b7c8d';
const CONTRACT_DELIVERY_ID = '9d8c7b6a-5f4e-4d3c-8b2a-1f0e9d8c7b6a';

test('templates listVersions sends limit and offset and returns the page', function (): void {
    $page = [
        'data' => [[
            'version' => 3,
            'name' => 'Boas-vindas',
            'subject' => null,
            'createdBy' => null,
            'createdAt' => '2026-10-15T12:00:00.000Z',
        ]],
        'total' => 3,
    ];
    $transport = DomainFixtures::transport($page);

    $response = DomainFixtures::client($transport)
        ->templates->listVersions(CONTRACT_TEMPLATE_ID, ['limit' => 10, 'offset' => 0]);

    expect($transport->lastMethod)->toBe('GET')
        ->and($transport->lastPath)->toBe('/v1/product/templates/' . CONTRACT_TEMPLATE_ID . '/versions')
        ->and($transport->lastQuery)->toBe(['limit' => 10, 'offset' => 0])
        ->and($response->data)->toBe($page);
});

test('templates getVersion returns the full version', function (): void {
    $version = [
        'templateId' => CONTRACT_TEMPLATE_ID,
        'version' => 2,
        'name' => 'Boas-vindas',
        'subject' => 'Oi',
        'preheader' => null,
        'html' => '<p>Oi {{nome}}</p>',
        'contentJson' => null,
        'textPayload' => null,
        'variables' => ['nome'],
        'format' => 'html',
        'sourceLocale' => 'pt-BR',
        'createdBy' => null,
        'createdAt' => '2026-10-15T12:00:00.000Z',
    ];
    $transport = DomainFixtures::transport($version);

    $data = DomainFixtures::client($transport)->templates->getVersion(CONTRACT_TEMPLATE_ID, 2)->data;

    expect($transport->lastMethod)->toBe('GET')
        ->and($transport->lastPath)->toBe('/v1/product/templates/' . CONTRACT_TEMPLATE_ID . '/versions/2')
        ->and($data)->toMatchArray(['templateId' => CONTRACT_TEMPLATE_ID, 'version' => 2, 'format' => 'html']);
});

test('templates restoreVersion posts without body and returns the template', function (): void {
    $transport = DomainFixtures::transport(['id' => CONTRACT_TEMPLATE_ID, 'name' => 'Boas-vindas']);

    $data = DomainFixtures::client($transport)->templates->restoreVersion(CONTRACT_TEMPLATE_ID, 1)->data;

    expect($transport->lastMethod)->toBe('POST')
        ->and($transport->lastPath)->toBe('/v1/product/templates/' . CONTRACT_TEMPLATE_ID . '/versions/1/restore')
        ->and($transport->lastBody)->toBeNull()
        ->and($data)->toMatchArray(['id' => CONTRACT_TEMPLATE_ID]);
});

test('templates version methods map TEMPLATE_NOT_FOUND and TEMPLATE_VERSION_NOT_FOUND', function (): void {
    $template = DomainFixtures::failing(404, 'TEMPLATE_NOT_FOUND')
        ->templates->listVersions(CONTRACT_TEMPLATE_ID)->error;
    $version = DomainFixtures::failing(404, 'TEMPLATE_VERSION_NOT_FOUND')
        ->templates->restoreVersion(CONTRACT_TEMPLATE_ID, 9)->error;

    expect($template)->toBeInstanceOf(NotFoundError::class)
        ->and($template?->apiCode)->toBe('TEMPLATE_NOT_FOUND')
        ->and($version)->toBeInstanceOf(NotFoundError::class)
        ->and($version?->apiCode)->toBe('TEMPLATE_VERSION_NOT_FOUND');
});

test('emails send accepts a template alias in templateId', function (): void {
    $transport = DomainFixtures::transport(['id' => 'email_1']);

    DomainFixtures::client($transport)->emails->send([
        'from' => 'noreply@acme.com',
        'to' => 'ana@exemplo.com',
        'templateId' => 'boas-vindas_v2',
        'variables' => ['nome' => 'Ana'],
    ]);

    expect($transport->lastBody)->toMatchArray(['templateId' => 'boas-vindas_v2']);
});

test('validation validate posts the emails and returns items, counts and credits', function (): void {
    $result = [
        'items' => [
            ['email' => 'ana@acme.com', 'formatValid' => true, 'mxValid' => true, 'isDisposable' => false],
            [
                'email' => 'x@mailinator.com',
                'formatValid' => true,
                'mxValid' => true,
                'isDisposable' => true,
                'reason' => 'disposable',
            ],
        ],
        'validCount' => 1,
        'invalidCount' => 1,
        'credits' => ['limit' => null, 'used' => 2, 'remaining' => null],
    ];
    $transport = DomainFixtures::transport($result);

    $response = DomainFixtures::client($transport)->validation->validate(['ana@acme.com', 'x@mailinator.com']);

    expect($transport->lastMethod)->toBe('POST')
        ->and($transport->lastPath)->toBe('/v1/product/validate')
        ->and($transport->lastBody)->toBe(['emails' => ['ana@acme.com', 'x@mailinator.com']])
        ->and($response->data)->toBe($result);
});

test('validation validate maps VALIDATION_CREDITS_EXHAUSTED to RateLimitError without retrying', function (): void {
    $body = json_encode(
        ['error' => ['code' => 'VALIDATION_CREDITS_EXHAUSTED', 'message' => 'Créditos esgotados']],
        JSON_THROW_ON_ERROR,
    );
    $transport = new FakeTransport(defaultResponse: ResponseParser::parse(429, $body, [], 'pt-BR'));

    $error = DomainFixtures::client($transport)->validation->validate(['ana@acme.com'])->error;

    expect($error)->toBeInstanceOf(RateLimitError::class)
        ->and($error?->apiCode)->toBe('VALIDATION_CREDITS_EXHAUSTED')
        ->and($transport->history)->toHaveCount(1);
});

test('validation validate maps a rejected payload to ValidationError', function (): void {
    $error = DomainFixtures::failing(400, 'VALIDATION_ERROR')->validation->validate([])->error;

    expect($error)->toBeInstanceOf(ValidationError::class);
});

test('webhooks getDelivery returns the delivery with requestPayload', function (): void {
    $delivery = ['id' => CONTRACT_DELIVERY_ID, 'status' => 'success', 'requestPayload' => ['type' => 'email.sent']];
    $transport = DomainFixtures::transport($delivery);

    $data = DomainFixtures::client($transport)->webhooks->getDelivery(CONTRACT_WEBHOOK_ID, CONTRACT_DELIVERY_ID)->data;

    expect($transport->lastMethod)->toBe('GET')
        ->and($transport->lastPath)
        ->toBe('/v1/product/webhooks/' . CONTRACT_WEBHOOK_ID . '/deliveries/' . CONTRACT_DELIVERY_ID)
        ->and($data)->toBe($delivery);
});

test('webhooks replayDelivery posts without body and returns the new delivery', function (): void {
    $transport = new FakeTransport(defaultResponse: new CoffeeMailResponse(
        data: ['id' => 'new-delivery', 'status' => 'pending'],
        error: null,
        statusCode: 202,
    ));

    $response = DomainFixtures::client($transport)->webhooks->replayDelivery(CONTRACT_WEBHOOK_ID, CONTRACT_DELIVERY_ID);

    expect($transport->lastMethod)->toBe('POST')
        ->and($transport->lastPath)
        ->toBe('/v1/product/webhooks/' . CONTRACT_WEBHOOK_ID . '/deliveries/' . CONTRACT_DELIVERY_ID . '/replay')
        ->and($transport->lastBody)->toBeNull()
        ->and($response->statusCode)->toBe(202)
        ->and($response->data)->toMatchArray(['id' => 'new-delivery']);
});

test('webhooks delivery methods map not found and conflict codes', function (): void {
    $webhook = DomainFixtures::failing(404, 'WEBHOOK_NOT_FOUND')
        ->webhooks->getDelivery(CONTRACT_WEBHOOK_ID, CONTRACT_DELIVERY_ID)->error;
    $delivery = DomainFixtures::failing(404, 'WEBHOOK_DELIVERY_NOT_FOUND')
        ->webhooks->replayDelivery(CONTRACT_WEBHOOK_ID, CONTRACT_DELIVERY_ID)->error;
    $pending = DomainFixtures::failing(409, 'WEBHOOK_DELIVERY_PENDING')
        ->webhooks->replayDelivery(CONTRACT_WEBHOOK_ID, CONTRACT_DELIVERY_ID)->error;
    $inactive = DomainFixtures::failing(409, 'WEBHOOK_NOT_ACTIVE')
        ->webhooks->replayDelivery(CONTRACT_WEBHOOK_ID, CONTRACT_DELIVERY_ID)->error;

    expect($webhook)->toBeInstanceOf(NotFoundError::class)
        ->and($webhook?->apiCode)->toBe('WEBHOOK_NOT_FOUND')
        ->and($delivery)->toBeInstanceOf(NotFoundError::class)
        ->and($delivery?->apiCode)->toBe('WEBHOOK_DELIVERY_NOT_FOUND')
        ->and($pending)->toBeInstanceOf(ConflictError::class)
        ->and($pending?->apiCode)->toBe('WEBHOOK_DELIVERY_PENDING')
        ->and($inactive)->toBeInstanceOf(ConflictError::class)
        ->and($inactive?->apiCode)->toBe('WEBHOOK_NOT_ACTIVE');
});
