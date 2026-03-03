<?php

namespace App\Application\Validator\Constraints;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class EntityExistsValidator extends ConstraintValidator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof EntityExists) {
            throw new UnexpectedTypeException($constraint, EntityExists::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $entity = $this->entityManager->getRepository($constraint->entity)->find($value);

        if (!$entity) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ entity }}', $constraint->entity)
                ->setParameter('{{ id }}', (string) $value)
                ->addViolation();
        }
    }
}
