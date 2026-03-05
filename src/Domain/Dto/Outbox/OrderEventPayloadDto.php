<?php

namespace App\Domain\Dto\Outbox;

class OrderEventPayloadDto
{
    public function __construct(
        public int $id,
        public array $extra = []
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'extra' => $this->extra
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['extra'] ?? []
        );
    }
}
