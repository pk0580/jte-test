<?php

namespace App\Domain\Dto\Outbox;

class OrderEventPayloadDto
{
    public function __construct(
        public int $id
    ) {}

    public function toArray(): array
    {
        return ['id' => $this->id];
    }

    public static function fromArray(array $data): self
    {
        return new self($data['id']);
    }
}
