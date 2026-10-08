<?php

declare(strict_types=1);

namespace CoffeeMail\Tests\Unit;

use CoffeeMail\CoffeeMail;
use CoffeeMail\Http\CoffeeMailResponse;
use CoffeeMail\Http\HttpTransportInterface;
use CoffeeMail\Payloads\AttachmentPayload;
use CoffeeMail\Payloads\DomainPayload;
use CoffeeMail\Payloads\EmailPayload;
use CoffeeMail\Payloads\WebhookPayload;
use InvalidArgumentException;

test('EmailPayload converts properly to array omitting nulls and formatting attachments', function (): void {
    $attachment = AttachmentPayload::fromRaw('relatorio.csv', "id,nome\n1,Teste", 'text/csv');

    $payload = new EmailPayload(
        from: 'financeiro@empresa.com.br',
        to: 'diretor@empresa.com.br',
        subject: 'Relatório Mensal',
        html: '<p>Segue o anexo.</p>',
        attachments: [$attachment],
        idempotencyKey: 'idem_123',
        isSandbox: true,
    );

    $array = $payload->toArray();

    assert(isset($array['attachments']) && is_array($array['attachments']));
    assert(isset($array['attachments'][0]) && is_array($array['attachments'][0]));
    $firstAtt = $array['attachments'][0];
    assert(isset($firstAtt['filename']) && is_string($firstAtt['filename']));
    assert(isset($firstAtt['contentType']) && is_string($firstAtt['contentType']));
    assert(isset($firstAtt['content']) && is_string($firstAtt['content']));

    expect($array['from'])->toBe('financeiro@empresa.com.br')
        ->and($array['to'])->toBe('diretor@empresa.com.br')
        ->and($array['subject'])->toBe('Relatório Mensal')
        ->and($array['html'])->toBe('<p>Segue o anexo.</p>')
        ->and($array['idempotencyKey'])->toBe('idem_123')
        ->and($array['isSandbox'])->toBeTrue()
        ->and($array)->not->toHaveKey('text')
        ->and($array)->not->toHaveKey('cc')
        ->and($array['attachments'])->toHaveCount(1)
        ->and($firstAtt['filename'])->toBe('relatorio.csv')
        ->and($firstAtt['contentType'])->toBe('text/csv')
        ->and(base64_decode($firstAtt['content']))->toBe("id,nome\n1,Teste");
});

test('AttachmentPayload fromPath loads file and encodes base64', function (): void {
    $tempFile = tempnam(sys_get_temp_dir(), 'cm_test_');
    assert(is_string($tempFile));
    file_put_contents($tempFile, 'Conteudo em PDF simulado');

    try {
        $attachment = AttachmentPayload::fromPath($tempFile, 'documento.pdf', 'application/pdf');
        $data = $attachment->toArray();
        assert(isset($data['content']) && is_string($data['content']));

        expect($data['filename'])->toBe('documento.pdf')
            ->and($data['contentType'])->toBe('application/pdf')
            ->and(base64_decode($data['content']))->toBe('Conteudo em PDF simulado');
    } finally {
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }
});

test('AttachmentPayload throws exception for nonexistent path', function (): void {
    expect(fn () => AttachmentPayload::fromPath('/caminho/arquivo/inexistente.txt'))
        ->toThrow(InvalidArgumentException::class);
});

test('DomainPayload and WebhookPayload convert properly to array', function (): void {
    $domain = DomainPayload::create('novodominio.com.br');
    expect($domain->toArray())->toBe(['name' => 'novodominio.com.br']);

    $webhook = WebhookPayload::create(
        url: 'https://api.empresa.com.br/hooks',
        events: ['email.delivered', 'email.bounced'],
        name: 'Hooks Primários',
    );
    expect($webhook->toArray())->toBe([
        'url' => 'https://api.empresa.com.br/hooks',
        'events' => ['email.delivered', 'email.bounced'],
        'name' => 'Hooks Primários',
    ]);
});

test('Emails resource accepts EmailPayload in send and sendBatch', function (): void {
    $capturedBody = null;

    $mockTransport = new class($capturedBody) implements HttpTransportInterface {
        public function __construct(public mixed &$body) {}

        public function request(string $method, string $path, ?array $body = null, array $query = [], array $headers = []): CoffeeMailResponse
        {
            $this->body = $body;
            return new CoffeeMailResponse(data: ['id' => 'eml_dto_123'], error: null, statusCode: 201);
        }

        public function get(string $path, array $query = [], array $headers = []): CoffeeMailResponse { return $this->request('GET', $path); }
        public function post(string $path, ?array $body = null, array $query = [], array $headers = []): CoffeeMailResponse { return $this->request('POST', $path, $body, headers: $headers); }
        public function put(string $path, ?array $body = null, array $query = [], array $headers = []): CoffeeMailResponse { return $this->request('PUT', $path, $body); }
        public function patch(string $path, ?array $body = null, array $query = [], array $headers = []): CoffeeMailResponse { return $this->request('PATCH', $path, $body); }
        public function delete(string $path, array $query = [], array $headers = []): CoffeeMailResponse { return $this->request('DELETE', $path); }
        public function getLocale(): string { return 'pt-BR'; }
    };

    $client = new CoffeeMail('cm_live_dummy', transport: $mockTransport);

    $payload = new EmailPayload(
        from: 'notificacoes@sistema.com',
        to: 'usuario@gmail.com',
        subject: 'Teste DTO',
        html: '<p>Olá via DTO</p>',
    );

    [$data, $error] = $client->emails->send($payload);

    assert(is_array($data));
    assert(is_array($capturedBody));

    expect($error)->toBeNull()
        ->and($data['id'])->toBe('eml_dto_123')
        ->and($capturedBody['subject'])->toBe('Teste DTO')
        ->and($capturedBody['to'])->toBe([['email' => 'usuario@gmail.com']]);

    // Teste sendBatch com lista de DTOs
    $client->emails->sendBatch([$payload]);
    assert(isset($capturedBody[0]) && is_array($capturedBody[0]));
    expect($capturedBody[0]['subject'])->toBe('Teste DTO');
});
