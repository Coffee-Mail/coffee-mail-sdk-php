<?php

declare(strict_types=1);

namespace CoffeeMail\Payloads;

final readonly class EmailPayload implements PayloadInterface
{
    /**
     * @param string|array{email: string, name?: string} $from
     * @param string|list<string>|list<array{email: string, name?: string}> $to
     * @param string|list<string>|list<array{email: string, name?: string}>|null $cc
     * @param string|list<string>|list<array{email: string, name?: string}>|null $bcc
     * @param string|list<string>|null $replyTo
     * @param array<string, string>|null $headers
     * @param list<AttachmentPayload|array<string, mixed>>|null $attachments
     * @param list<string>|array<string, string>|null $tags
     */
    public function __construct(
        public string|array $from,
        public string|array $to,
        public string $subject,
        public ?string $html = null,
        public ?string $text = null,
        public string|array|null $cc = null,
        public string|array|null $bcc = null,
        public string|array|null $replyTo = null,
        public ?array $headers = null,
        public ?array $attachments = null,
        public ?array $tags = null,
        public ?string $idempotencyKey = null,
        public ?bool $isSandbox = null,
        public ?string $scheduledAt = null,
    ) {
    }

    /**
     * @param string|array{email: string, name?: string} $from
     * @param string|list<string>|list<array{email: string, name?: string}> $to
     * @param string|list<string>|list<array{email: string, name?: string}>|null $cc
     * @param string|list<string>|list<array{email: string, name?: string}>|null $bcc
     * @param string|list<string>|null $replyTo
     * @param array<string, string>|null $headers
     * @param list<AttachmentPayload|array<string, mixed>>|null $attachments
     * @param list<string>|array<string, string>|null $tags
     */
    public static function create(
        string|array $from,
        string|array $to,
        string $subject,
        ?string $html = null,
        ?string $text = null,
        string|array|null $cc = null,
        string|array|null $bcc = null,
        string|array|null $replyTo = null,
        ?array $headers = null,
        ?array $attachments = null,
        ?array $tags = null,
        ?string $idempotencyKey = null,
        ?bool $isSandbox = null,
        ?string $scheduledAt = null,
    ): self {
        return new self(
            from: $from,
            to: $to,
            subject: $subject,
            html: $html,
            text: $text,
            cc: $cc,
            bcc: $bcc,
            replyTo: $replyTo,
            headers: $headers,
            attachments: $attachments,
            tags: $tags,
            idempotencyKey: $idempotencyKey,
            isSandbox: $isSandbox,
            scheduledAt: $scheduledAt,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [
            'from' => $this->from,
            'to' => $this->to,
            'subject' => $this->subject,
        ];

        if ($this->html !== null) {
            $payload['html'] = $this->html;
        }

        if ($this->text !== null) {
            $payload['text'] = $this->text;
        }

        if ($this->cc !== null) {
            $payload['cc'] = $this->cc;
        }

        if ($this->bcc !== null) {
            $payload['bcc'] = $this->bcc;
        }

        if ($this->replyTo !== null) {
            $payload['replyTo'] = $this->replyTo;
        }

        if ($this->headers !== null && $this->headers !== []) {
            $payload['headers'] = $this->headers;
        }

        if ($this->attachments !== null && $this->attachments !== []) {
            $payload['attachments'] = array_map(
                static fn (mixed $att): mixed => $att instanceof AttachmentPayload ? $att->toArray() : $att,
                $this->attachments,
            );
        }

        if ($this->tags !== null && $this->tags !== []) {
            $payload['tags'] = $this->tags;
        }

        if ($this->idempotencyKey !== null) {
            $payload['idempotencyKey'] = $this->idempotencyKey;
        }

        if ($this->isSandbox !== null) {
            $payload['isSandbox'] = $this->isSandbox;
        }

        if ($this->scheduledAt !== null) {
            $payload['scheduledAt'] = $this->scheduledAt;
        }

        return $payload;
    }
}
