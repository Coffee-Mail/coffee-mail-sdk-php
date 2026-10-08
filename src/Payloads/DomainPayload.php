<?php

declare(strict_types=1);

namespace CoffeeMail\Payloads;

final class DomainPayload implements PayloadInterface
{
    public function __construct(
        public readonly string $name,
    ) {
    }

    public static function create(string $name): self
    {
        return new self($name);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}
