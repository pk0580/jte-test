<?php

namespace App\Application\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class EntityExists extends Constraint
{
    public string $message = 'The entity of type "{{ entity }}" with ID "{{ id }}" does not exist.';
    public string $entity;

    /**
     * @param class-string $entity
     * @param array<string>|null $groups
     */
    public function __construct(
        string $entity,
        ?string $message = null,
        ?array $groups = null,
        mixed $payload = null
    ) {
        parent::__construct([], $groups, $payload);
        $this->entity = $entity;
        $this->message = $message ?? $this->message;
    }
}
