<?php

declare(strict_types=1);

use CoffeeMail\Exceptions\ConflictError;
use CoffeeMail\Exceptions\NotFoundError;
use CoffeeMail\Exceptions\ValidationError;
use CoffeeMail\Tests\Support\DomainFixtures;

test('domains getWarmupStatus exposes status, totalDays, startedAt and completedAt', function (): void {
    $transport = DomainFixtures::transport([
        'domainId' => DomainFixtures::DOMAIN_ID,
        'status' => 'completed',
        'currentDay' => 30,
        'totalDays' => 30,
        'dailyQuota' => 50000,
        'sentToday' => 10,
        'remainingToday' => 49990,
        'quotaUsedPercent' => 0,
        'lastSendDate' => '2026-10-15',
        'startedAt' => '2026-09-15T00:00:00.000Z',
        'completedAt' => '2026-10-15T00:00:00.000Z',
    ]);
    $client = DomainFixtures::client($transport);

    $warmup = $client->domains->getWarmupStatus(DomainFixtures::DOMAIN_ID)->data;

    expect($transport->lastMethod)->toBe('GET')
        ->and($transport->lastPath)->toBe('/v1/product/domains/' . DomainFixtures::DOMAIN_ID . '/warmup')
        ->and($warmup)->toMatchArray([
            'status' => 'completed',
            'totalDays' => 30,
            'startedAt' => '2026-09-15T00:00:00.000Z',
            'completedAt' => '2026-10-15T00:00:00.000Z',
        ]);
});

test('domains getWarmupStatus returns null data when the domain has no warmup', function (): void {
    $transport = DomainFixtures::transport(null);
    $client = DomainFixtures::client($transport);

    $response = $client->domains->getWarmupStatus(DomainFixtures::DOMAIN_ID);

    expect($response->isSuccess())->toBeTrue()
        ->and($response->data)->toBeNull();
});

test('domains get exposes dkimRotation, returnPath, trackingDomain and trackingDomainsAvailable', function (): void {
    $rotation = [
        'domainId' => DomainFixtures::DOMAIN_ID,
        'selector' => 'cm202610',
        'startedAt' => '2026-10-15T12:00:00.000Z',
        'record' => ['type' => 'TXT', 'host' => 'cm202610._domainkey.acme.com', 'value' => 'v=DKIM1; k=rsa; p=abc'],
    ];
    $transport = DomainFixtures::transport([
        'id' => DomainFixtures::DOMAIN_ID,
        'name' => 'acme.com',
        'dkimRotation' => $rotation,
        'returnPath' => DomainFixtures::customHost('bounces.acme.com', true),
        'trackingDomain' => null,
        'trackingDomainsAvailable' => true,
    ]);
    $client = DomainFixtures::client($transport);

    $domain = $client->domains->get(DomainFixtures::DOMAIN_ID)->data;

    expect($domain)->toMatchArray([
        'dkimRotation' => $rotation,
        'returnPath' => DomainFixtures::customHost('bounces.acme.com', true),
        'trackingDomain' => null,
        'trackingDomainsAvailable' => true,
    ]);
});

test('domains rotateDkim posts without body and returns the pending rotation', function (): void {
    $transport = DomainFixtures::transport([
        'domainId' => DomainFixtures::DOMAIN_ID,
        'selector' => 'cm202610',
        'startedAt' => '2026-10-15T12:00:00.000Z',
        'record' => [
            'type' => 'TXT',
            'host' => 'cm202610._domainkey.acme.com',
            'value' => 'v=DKIM1; k=rsa; p=abc',
            'ttl' => 3600,
            'isInfrastructure' => false,
        ],
    ]);
    $client = DomainFixtures::client($transport);

    $rotation = $client->domains->rotateDkim(DomainFixtures::DOMAIN_ID)->data;
    $record = is_array($rotation) && is_array($rotation['record'] ?? null) ? $rotation['record'] : [];

    expect($transport->lastMethod)->toBe('POST')
        ->and($transport->lastPath)->toBe('/v1/product/domains/' . DomainFixtures::DOMAIN_ID . '/dkim/rotate')
        ->and($transport->lastBody)->toBeNull()
        ->and($rotation)->toMatchArray(['selector' => 'cm202610', 'startedAt' => '2026-10-15T12:00:00.000Z'])
        ->and($record)->toMatchArray(['type' => 'TXT', 'ttl' => 3600, 'isInfrastructure' => false]);
});

test('domains rotateDkim maps DOMAIN_NOT_FOUND and DOMAIN_NOT_VERIFIED to typed errors', function (): void {
    $missing = DomainFixtures::failing(404, 'DOMAIN_NOT_FOUND')->domains->rotateDkim(DomainFixtures::DOMAIN_ID)->error;
    $unverified = DomainFixtures::failing(409, 'DOMAIN_NOT_VERIFIED')
        ->domains->rotateDkim(DomainFixtures::DOMAIN_ID)->error;

    expect($missing)->toBeInstanceOf(NotFoundError::class)
        ->and($missing?->apiCode)->toBe('DOMAIN_NOT_FOUND')
        ->and($unverified)->toBeInstanceOf(ConflictError::class)
        ->and($unverified?->apiCode)->toBe('DOMAIN_NOT_VERIFIED');
});

test('domains setTrackingDomain puts the host and returns the custom host status', function (): void {
    $transport = DomainFixtures::transport(DomainFixtures::customHost('links.acme.com', false));
    $client = DomainFixtures::client($transport);

    $status = $client->domains->setTrackingDomain(DomainFixtures::DOMAIN_ID, 'links.acme.com')->data;

    expect($transport->lastMethod)->toBe('PUT')
        ->and($transport->lastPath)->toBe('/v1/product/domains/' . DomainFixtures::DOMAIN_ID . '/tracking-domain')
        ->and($transport->lastBody)->toBe(['host' => 'links.acme.com'])
        ->and($status)->toMatchArray([
            'host' => 'links.acme.com',
            'target' => 'track.coffeemail.com.br',
            'verified' => false,
            'verifiedAt' => null,
        ]);
});

test('domains setTrackingDomain maps invalid host, disabled feature and missing domain', function (): void {
    $invalid = DomainFixtures::failing(400, 'INVALID_TRACKING_HOST')
        ->domains->setTrackingDomain(DomainFixtures::DOMAIN_ID, 'x')->error;
    $disabled = DomainFixtures::failing(409, 'TRACKING_DOMAINS_DISABLED')
        ->domains->setTrackingDomain(DomainFixtures::DOMAIN_ID, 'links.acme.com')->error;
    $missing = DomainFixtures::failing(404, 'DOMAIN_NOT_FOUND')
        ->domains->setTrackingDomain(DomainFixtures::DOMAIN_ID, 'links.acme.com')->error;

    expect($invalid)->toBeInstanceOf(ValidationError::class)
        ->and($invalid?->apiCode)->toBe('INVALID_TRACKING_HOST')
        ->and($disabled)->toBeInstanceOf(ConflictError::class)
        ->and($disabled?->apiCode)->toBe('TRACKING_DOMAINS_DISABLED')
        ->and($missing)->toBeInstanceOf(NotFoundError::class);
});

test('domains removeTrackingDomain deletes the tracking domain and accepts null data', function (): void {
    $transport = DomainFixtures::transport(null);
    $client = DomainFixtures::client($transport);

    $response = $client->domains->removeTrackingDomain(DomainFixtures::DOMAIN_ID);

    expect($transport->lastMethod)->toBe('DELETE')
        ->and($transport->lastPath)->toBe('/v1/product/domains/' . DomainFixtures::DOMAIN_ID . '/tracking-domain')
        ->and($response->isSuccess())->toBeTrue()
        ->and($response->data)->toBeNull();
});

test('domains removeTrackingDomain maps a missing domain to NotFoundError', function (): void {
    $error = DomainFixtures::failing(404, 'DOMAIN_NOT_FOUND')
        ->domains->removeTrackingDomain(DomainFixtures::DOMAIN_ID)->error;

    expect($error)->toBeInstanceOf(NotFoundError::class);
});
