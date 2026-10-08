<?php

declare(strict_types=1);

namespace CoffeeMail\Payloads;

final readonly class WebhookPayload implements PayloadInterface
{
    /**
     * @param list<string> $events
     */
    public function __construct(
        public string $url,
        public array $events,
        public ?string $name = null,
        public ?string $description = null,
    ) {}

    /**
     * @param list<string> $events
     */
    public static function create(
        string $url,
        array $events,
        ?string $name = null,
        ?string $description = null,
    ): self {
        return new self($url, $events, $name, $description);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'url' => $this->url,
            'events' => $this->events,
        ];

        if ($this->name !== null) {
            $data['name'] = $this->name;
        }

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        return $data;
    }
}
