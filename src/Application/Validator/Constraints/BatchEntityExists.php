<?php

namespace App\Application\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class BatchEntityExists extends Constraint
{
    public string $message = 'The record for entity "{{ entity }}" with values {{ values }} does not exist.';
    public array $fields = ['id'];

    public function __construct(
        public string $entity,
        array|string $fields = ['id'],
        ?string $message = null,
        public bool $allowNull = false,
        ?array $groups = null,
        mixed $payload = null
    ) {
        parent::__construct([], $groups, $payload);

        $this->fields = (array) $fields;
        $this->message = $message ?? $this->message;
    }

    public function getRequiredOptions(): array
    {
        return ['entity'];
    }
}
