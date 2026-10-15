<?php

declare(strict_types=1);

namespace CoffeeMail\Payloads;

final class EmailPayload implements PayloadInterface
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
     * @param array<string, mixed>|null $variables
     * @param array<string, mixed>|null $metadata
     * @param 'transactional'|'broadcast'|null $emailType
     */
    public function __construct(
        public readonly string|array $from,
        public readonly string|array $to,
        public readonly ?string $subject = null,
        public readonly ?string $html = null,
        public readonly ?string $text = null,
        public readonly string|array|null $cc = null,
        public readonly string|array|null $bcc = null,
        public readonly string|array|null $replyTo = null,
        public readonly ?array $headers = null,
        public readonly ?array $attachments = null,
        public readonly ?array $tags = null,
        public readonly ?string $idempotencyKey = null,
        public readonly ?bool $isSandbox = null,
        public readonly ?string $scheduledAt = null,
        public readonly ?string $templateId = null,
        public readonly ?array $variables = null,
        public readonly ?array $metadata = null,
        public readonly ?string $emailType = null,
        public readonly ?string $suppressionCategory = null,
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
     * @param array<string, mixed>|null $variables
     * @param array<string, mixed>|null $metadata
     * @param 'transactional'|'broadcast'|null $emailType
     */
    public static function create(
        string|array $from,
        string|array $to,
        ?string $subject = null,
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
        ?string $templateId = null,
        ?array $variables = null,
        ?array $metadata = null,
        ?string $emailType = null,
        ?string $suppressionCategory = null,
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
            templateId: $templateId,
            variables: $variables,
            metadata: $metadata,
            emailType: $emailType,
            suppressionCategory: $suppressionCategory,
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
        ];

        $optional = [
            'subject' => $this->subject,
            'templateId' => $this->templateId,
            'variables' => $this->variables,
            'metadata' => $this->metadata,
            'emailType' => $this->emailType,
            'suppressionCategory' => $this->suppressionCategory,
        ];

        foreach ($optional as $field => $value) {
            if ($value !== null) {
                $payload[$field] = $value;
            }
        }

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
