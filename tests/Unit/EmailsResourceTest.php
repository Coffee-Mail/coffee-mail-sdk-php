<?php

declare(strict_types=1);

use CoffeeMail\CoffeeMail;
use CoffeeMail\Exceptions\ValidationError;
use CoffeeMail\Http\CoffeeMailResponse;
use CoffeeMail\Tests\Support\FakeTransport;

test('emails send normalizes from and to and dispatches to POST /v1/product/emails', function (): void {
    $mockTransport = new FakeTransport(
        defaultResponse: new CoffeeMailResponse(
            data: ['id' => 'eml_abc123', 'status' => 'queued'],
            error: null,
            statusCode: 201,
        )
    );

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
    assert(is_array($mockTransport->lastBody));

    expect($error)->toBeNull()
        ->and($data)->toBeArray()
        ->and($data['id'])->toBe('eml_abc123')
        ->and($mockTransport->lastPath)->toBe('/v1/product/emails')
        ->and($mockTransport->lastBody['from'])->toBe(['email' => 'contato@seudominio.com.br'])
        ->and($mockTransport->lastBody['to'])->toBe([['email' => 'cliente@gmail.com']])
        ->and($mockTransport->lastBody['subject'])->toBe('Pedido #123 Confirmado')
        ->and($mockTransport->lastHeaders['x-idempotency-key'])->toBe('idem_key_999')
        ->and($mockTransport->lastHeaders['x-coffeemail-sandbox'])->toBe('true');
});

test('emails send normalizes from and to with RFC 5322 names', function (): void {
    $mockTransport = new FakeTransport(
        defaultResponse: new CoffeeMailResponse(
            data: ['id' => 'eml_abc124', 'status' => 'queued'],
            error: null,
            statusCode: 201,
        )
    );

    $client = new CoffeeMail('cm_live_dummy', transport: $mockTransport);

    [$data, $error] = $client->emails->send([
        'from' => 'Empresa Exemplo <contato@seudominio.com.br>',
        'to' => 'Cliente VIP <cliente@gmail.com>',
        'subject' => 'Assunto Teste',
        'html' => '<p>Conteúdo</p>',
    ]);

    expect($error)->toBeNull()
        ->and(is_array($mockTransport->lastBody))->toBeTrue()
        ->and($mockTransport->lastBody['from'] ?? null)->toBe([
            'email' => 'contato@seudominio.com.br',
            'name' => 'Empresa Exemplo',
        ])
        ->and($mockTransport->lastBody['to'] ?? null)->toBe([
            [
                'email' => 'cliente@gmail.com',
                'name' => 'Cliente VIP',
            ],
        ]);
});

test('emails resource exposes batch, get, list, cancel, resend, tags and events operations', function (): void {
    $mockTransport = new FakeTransport(
        defaultResponse: new CoffeeMailResponse(
            data: ['ok' => true],
            error: null,
            statusCode: 200,
        )
    );

    $client = new CoffeeMail('cm_live_dummy', transport: $mockTransport);

    $client->emails->get('eml_123');
    expect($mockTransport->lastMethod)->toBe('GET')
        ->and($mockTransport->lastPath)->toBe('/v1/product/emails/eml_123');

    $client->emails->list(['status' => 'delivered', 'limit' => 20]);
    expect($mockTransport->lastMethod)->toBe('GET')
        ->and($mockTransport->lastPath)->toBe('/v1/product/emails');

    $client->emails->getEvents('eml_123');
    expect($mockTransport->lastMethod)->toBe('GET')
        ->and($mockTransport->lastPath)->toBe('/v1/product/emails/eml_123/events');

    $client->emails->getTags();
    expect($mockTransport->lastMethod)->toBe('GET')
        ->and($mockTransport->lastPath)->toBe('/v1/product/emails/tags');

    $client->emails->cancel('eml_123');
    expect($mockTransport->lastMethod)->toBe('POST')
        ->and($mockTransport->lastPath)->toBe('/v1/product/emails/eml_123/cancel');

    $client->emails->resend('eml_123');
    expect($mockTransport->lastMethod)->toBe('POST')
        ->and($mockTransport->lastPath)->toBe('/v1/product/emails/eml_123/resend');

    $client->emails->sendBatch([
        ['from' => 'a@b.com', 'to' => 'c@d.com', 'subject' => 'S1', 'text' => 'T1'],
        ['from' => 'a@b.com', 'to' => 'e@f.com', 'subject' => 'S2', 'text' => 'T2'],
    ]);
    expect($mockTransport->lastMethod)->toBe('POST')
        ->and($mockTransport->lastPath)->toBe('/v1/product/emails/batch');
});

test('emails send forwards metadata, emailType and suppressionCategory, also in batch', function (): void {
    $mockTransport = new FakeTransport();
    $client = new CoffeeMail('cm_live_dummy', transport: $mockTransport);
    $email = [
        'from' => 'no-reply@exemplo.com.br',
        'to' => 'cliente@gmail.com',
        'subject' => 'Pedido',
        'text' => 'Seu pedido saiu.',
        'metadata' => ['pedido' => 42],
        'emailType' => 'transactional',
        'suppressionCategory' => 'pedidos',
    ];

    $client->emails->send($email);
    expect($mockTransport->lastBody)->toMatchArray([
        'metadata' => ['pedido' => 42],
        'emailType' => 'transactional',
        'suppressionCategory' => 'pedidos',
    ]);

    $client->emails->sendBatch([$email]);
    $first = is_array($mockTransport->lastBody) ? ($mockTransport->lastBody[0] ?? null) : null;
    expect($first)->toMatchArray(['metadata' => ['pedido' => 42], 'emailType' => 'transactional']);
});

test('emails send with templateId does not require subject', function (): void {
    $mockTransport = new FakeTransport();
    $client = new CoffeeMail('cm_live_dummy', transport: $mockTransport);

    $client->emails->send([
        'from' => 'no-reply@exemplo.com.br',
        'to' => 'cliente@gmail.com',
        'templateId' => '6f1c2a7e-3b4d-4e5f-8a9b-0c1d2e3f4a5b',
        'variables' => ['nome' => 'Ana'],
    ]);

    expect($mockTransport->lastBody)->not->toHaveKey('subject')
        ->and($mockTransport->lastBody)->toMatchArray(['templateId' => '6f1c2a7e-3b4d-4e5f-8a9b-0c1d2e3f4a5b']);
});

test('emails send rejects content without subject and template mixed with html', function (): void {
    $mockTransport = new FakeTransport();
    $client = new CoffeeMail('cm_live_dummy', transport: $mockTransport);
    $base = ['from' => 'no-reply@exemplo.com.br', 'to' => 'cliente@gmail.com'];

    expect(fn () => $client->emails->send([...$base, 'html' => '<p>Oi</p>']))
        ->toThrow(ValidationError::class, 'subject')
        ->and(fn () => $client->emails->send([
            ...$base,
            'subject' => 'Oi',
            'html' => '<p>Oi</p>',
            'templateId' => '6f1c2a7e-3b4d-4e5f-8a9b-0c1d2e3f4a5b',
        ]))->toThrow(ValidationError::class, 'não os dois')
        ->and(fn () => $client->emails->send([...$base, 'subject' => 'Sem corpo']))
        ->toThrow(ValidationError::class, 'não os dois')
        ->and($mockTransport->history)->toBe([]);
});
