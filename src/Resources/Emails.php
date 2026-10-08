<?php

declare(strict_types=1);

namespace CoffeeMail\Resources;

use CoffeeMail\Exceptions\ValidationError;
use CoffeeMail\Http\CoffeeMailResponse;
use CoffeeMail\Http\HttpTransportInterface;
use CoffeeMail\Payloads\AttachmentPayload;
use CoffeeMail\Payloads\EmailPayload;
use DateTimeInterface;

final readonly class Emails
{
    public function __construct(
        private HttpTransportInterface $http,
    ) {}

    /**
     * Dispara um e-mail transacional único.
     *
     * @param array<string, mixed>|EmailPayload $payload
     * @return CoffeeMailResponse<mixed>
     */
    public function send(array|EmailPayload $payload): CoffeeMailResponse
    {
        $rawPayload = $payload instanceof EmailPayload ? $payload->toArray() : $payload;
        $body = $this->formatSendBody($rawPayload);
        $headers = $this->formatSendHeaders($rawPayload);

        return $this->http->post('/v1/product/emails', $body, headers: $headers);
    }

    /**
     * Envia múltiplos e-mails transacionais em lote (batch).
     *
     * @param list<array<string, mixed>|EmailPayload> $items
     * @return CoffeeMailResponse<mixed>
     */
    public function sendBatch(array $items): CoffeeMailResponse
    {
        /** @var list<array<string, mixed>> $normalizedItems */
        $normalizedItems = array_map(
            static fn (mixed $item): array => $item instanceof EmailPayload ? $item->toArray() : (array) $item,
            $items,
        );

        $formattedItems = array_map(fn (array $item): array => $this->formatSendBody($item), $normalizedItems);
        $batchHeaders = [];

        foreach ($normalizedItems as $item) {
            $batchHeaders = array_merge($batchHeaders, $this->formatSendHeaders($item));
        }

        /** @var list<mixed> $rawList */
        $rawList = $formattedItems;

        return $this->http->post('/v1/product/emails/batch', $rawList, headers: $batchHeaders);
    }

    /**
     * Obtém os detalhes completos de um e-mail pelo ID.
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function get(string $id): CoffeeMailResponse
    {
        return $this->http->get('/v1/product/emails/' . rawurlencode($id));
    }

    /**
     * Lista o histórico de e-mails enviados com suporte a paginação e filtros.
     *
     * @param array<string, mixed> $query
     * @return CoffeeMailResponse<mixed>
     */
    public function list(array $query = []): CoffeeMailResponse
    {
        return $this->http->get('/v1/product/emails', $query);
    }

    /**
     * Consulta a linha do tempo completa de eventos de entrega (queued -> sent -> delivered / bounced).
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function getEvents(string $id): CoffeeMailResponse
    {
        return $this->http->get('/v1/product/emails/' . rawurlencode($id) . '/events');
    }

    /**
     * Retorna a lista de tags distintas já utilizadas pela organização.
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function getTags(): CoffeeMailResponse
    {
        return $this->http->get('/v1/product/emails/tags');
    }

    /**
     * Cancela o disparo de um e-mail previamente agendado.
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function cancel(string $id): CoffeeMailResponse
    {
        return $this->http->post('/v1/product/emails/' . rawurlencode($id) . '/cancel');
    }

    /**
     * Reenvia um e-mail existente.
     *
     * @return CoffeeMailResponse<mixed>
     */
    public function resend(string $id): CoffeeMailResponse
    {
        return $this->http->post('/v1/product/emails/' . rawurlencode($id) . '/resend');
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function formatSendBody(array $payload): array
    {
        if (! isset($payload['from'])) {
            throw new ValidationError('O campo `from` é obrigatório para envio de e-mail.');
        }

        if (! isset($payload['to'])) {
            throw new ValidationError('O campo `to` é obrigatório para envio de e-mail.');
        }

        if (! isset($payload['subject']) || ! is_string($payload['subject'])) {
            throw new ValidationError('O campo `subject` é obrigatório para envio de e-mail.');
        }

        $body = [
            'from' => $this->normalizeParticipant($payload['from']),
            'to' => $this->normalizeList($payload['to']),
            'subject' => $payload['subject'],
        ];

        if (isset($payload['cc'])) {
            $body['cc'] = $this->normalizeList($payload['cc']);
        }

        if (isset($payload['bcc'])) {
            $body['bcc'] = $this->normalizeList($payload['bcc']);
        }

        if (isset($payload['replyTo'])) {
            $body['replyTo'] = $this->normalizeParticipant($payload['replyTo']);
        }

        foreach (['html', 'text', 'templateId', 'variables', 'headers', 'tags'] as $field) {
            if (isset($payload[$field])) {
                $body[$field] = $payload[$field];
            }
        }

        if (isset($payload['scheduledAt'])) {
            if ($payload['scheduledAt'] instanceof DateTimeInterface) {
                $body['scheduledAt'] = $payload['scheduledAt']->format(DateTimeInterface::ATOM);
            } elseif (is_string($payload['scheduledAt'])) {
                $body['scheduledAt'] = $payload['scheduledAt'];
            }
        }

        if (isset($payload['attachments']) && is_array($payload['attachments'])) {
            $body['attachments'] = $this->serializeAttachments($payload['attachments']);
        }

        return $body;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, string>
     */
    private function formatSendHeaders(array $payload): array
    {
        $headers = [];

        if (isset($payload['idempotencyKey']) && is_string($payload['idempotencyKey'])) {
            $headers['x-idempotency-key'] = $payload['idempotencyKey'];
        }

        if (isset($payload['isSandbox']) && $payload['isSandbox'] === true) {
            $headers['x-coffeemail-sandbox'] = 'true';
        }

        return $headers;
    }

    /**
     * @return array{email: string, name?: string}
     */
    private function normalizeParticipant(mixed $input): array
    {
        if (is_string($input)) {
            return ['email' => trim($input)];
        }

        if (is_array($input) && isset($input['email']) && is_string($input['email'])) {
            $normalized = ['email' => trim($input['email'])];
            if (isset($input['name']) && is_string($input['name'])) {
                $normalized['name'] = trim($input['name']);
            }
            return $normalized;
        }

        throw new ValidationError('Participante de e-mail inválido: esperado string ou array com chave `email`.', $input);
    }

    /**
     * @return list<array{email: string, name?: string}>
     */
    private function normalizeList(mixed $input): array
    {
        if (is_string($input)) {
            return [$this->normalizeParticipant($input)];
        }

        if (is_array($input)) {
            if (isset($input['email'])) {
                return [$this->normalizeParticipant($input)];
            }

            $list = [];
            foreach ($input as $item) {
                $list[] = $this->normalizeParticipant($item);
            }
            return $list;
        }

        return [];
    }

    /**
     * @param array<mixed, mixed> $attachments
     * @return list<array<string, mixed>>
     */
    private function serializeAttachments(array $attachments): array
    {
        $result = [];

        foreach ($attachments as $att) {
            $attData = $att instanceof AttachmentPayload ? $att->toArray() : $att;

            if (! is_array($attData) || ! isset($attData['filename'], $attData['content'])) {
                continue;
            }

            $rawContent = $attData['content'];
            $encodedContent = is_string($rawContent)
                ? (base64_encode(base64_decode($rawContent, true) ?: '') === $rawContent ? $rawContent : base64_encode($rawContent))
                : base64_encode((string) $rawContent);

            $item = [
                'filename' => (string) $attData['filename'],
                'content' => $encodedContent,
                'contentType' => isset($attData['contentType']) ? (string) $attData['contentType'] : 'application/octet-stream',
                'disposition' => isset($attData['disposition']) ? (string) $attData['disposition'] : 'attachment',
            ];

            if (isset($attData['cid']) && is_string($attData['cid'])) {
                $item['cid'] = $attData['cid'];
            }

            $result[] = $item;
        }

        return $result;
    }
}
