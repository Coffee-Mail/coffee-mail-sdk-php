<?php

declare(strict_types=1);

namespace CoffeeMail\Payloads;

final class WebhookPayload implements PayloadInterface
{
    /**
     * @param list<string> $events
     */
    public function __construct(
        public readonly string $url,
        public readonly array $events,
        public readonly ?string $description = null,
    ) {
    }

    /**
     * @param list<string> $events
     */
    public static function create(string $url, array $events, ?string $description = null): self
    {
        return new self($url, $events, $description);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'events' => $this->events,
            ...($this->description !== null ? ['description' => $this->description] : []),
        ];
    }
}
