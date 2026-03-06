<?php

namespace App\Application\Validator\Constraints;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class BatchEntityExistsValidator extends ConstraintValidator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof BatchEntityExists) {
            throw new UnexpectedTypeException($constraint, BatchEntityExists::class);
        }

        if ($this->isEmpty($value)) {
            return;
        }

        $items = is_array($value) ? $value : [$value];
        $criteriaList = $this->collectCriteriaList($items, $constraint);

        if (empty($criteriaList)) {
            return;
        }

        $foundHashes = $this->fetchExistingHashes($constraint->entity, $constraint->fields, $criteriaList);
        if (count($foundHashes) === count($criteriaList)) {
            // Если количество уникальных найденных записей совпадает с количеством уникальных запрошенных,
            // значит все искомые записи существуют в БД.
            return;
        }

        $this->processViolations($value, $items, $foundHashes, $constraint);
    }

    private function isEmpty(mixed $value): bool
    {
        return null === $value || (is_array($value) && empty($value));
    }

    /**
     * Собирает уникальные комбинации значений для поиска из переданных данных.
     *
     * @param array $items
     * @param BatchEntityExists $constraint
     * @return array<string, array> [hash => criteria]
     */
    private function collectCriteriaList(array $items, BatchEntityExists $constraint): array
    {
        $criteriaList = [];
        foreach ($items as $item) {
            $criteria = [];
            foreach ($constraint->fields as $field) {
                $val = $this->extractFieldValue($item, $field);

                if (null === $val && $constraint->allowNull) {
                    continue 2;
                }
                $criteria[$field] = $val;
            }

            $criteriaList[$this->getHash($criteria)] = $criteria;
        }

        return $criteriaList;
    }

    /**
     * Выполняет запрос к БД для поиска существующих сущностей по списку критериев
     * и возвращает массив хешей найденных записей.
     *
     * @param string $entityClass
     * @param array $fields
     * @param array $criteriaList
     * @return array<string, bool>
     */
    private function fetchExistingHashes(string $entityClass, array $fields, array $criteriaList): array
    {
        $found = [];
        $isSingle = count($fields) === 1;
        $field = $fields[0];

        foreach (array_chunk($criteriaList, 500) as $chunk) {
            $qb = $this->entityManager->createQueryBuilder()->from($entityClass, 'e');

            if ($isSingle) {
                $values = array_unique(array_column($chunk, $field));
                $qb->select("e.$field")
                    ->where($qb->expr()->in("e.$field", ':v'))
                    ->setParameter('v', $values);
            } else {
                $qb->select('partial e.{'.implode(',', $fields).'}');
                $orX = $qb->expr()->orX();

                foreach ($chunk as $i => $criteria) {
                    $andX = $qb->expr()->andX();
                    foreach ($criteria as $f => $v) {
                        $andX->add($qb->expr()->eq("e.$f", ":p_{$i}_{$f}"));
                        $qb->setParameter("p_{$i}_{$f}", $v);
                    }
                    $orX->add($andX);
                }
                $qb->where($orX);
            }

            foreach ($qb->getQuery()->toIterable() as $row) {
                $res = [];
                foreach ($fields as $f) {
                    $res[$f] = is_array($row) ? ($row[$f] ?? null) : $this->extractFieldValue($row, $f);
                }
                $found[$this->getHash($res)] = true;
            }
        }

        return $found;
    }

    /**
     * Проверяет исходные элементы и добавляет нарушения валидации для отсутствующих в БД записей.
     *
     * @param mixed $value Исходное значение для корректного определения пути (atPath)
     * @param array $items
     * @param array $foundHashes
     * @param BatchEntityExists $constraint
     */
    private function processViolations(mixed $value, array $items, array $foundHashes, BatchEntityExists $constraint): void
    {
        $isBatch = is_array($value);

        foreach ($items as $index => $item) {
            $currentCriteria = [];
            $hasNull = false;
            foreach ($constraint->fields as $field) {
                $val = $this->extractFieldValue($item, $field);
                if (null === $val && $constraint->allowNull) {
                    $hasNull = true;
                }
                $currentCriteria[$field] = $val;
            }

            if ($hasNull) {
                continue;
            }

            $hash = $this->getHash($currentCriteria);
            if (!isset($foundHashes[$hash])) {
                $violationBuilder = $this->context->buildViolation($constraint->message);
                if ($isBatch) {
                    $violationBuilder->atPath("[$index]");
                }
                $violationBuilder->setParameter('{{ entity }}', $constraint->entity)
                    ->setParameter('{{ values }}', json_encode($currentCriteria))
                    ->addViolation();
            }
        }
    }

    private function extractFieldValue(mixed $item, string $field): mixed
    {
        if (is_array($item)) {
            return $item[$field] ?? null;
        }

        if (is_object($item)) {
            if (property_exists($item, $field)) {
                return $item->$field;
            }
            $getter = 'get' . ucfirst($field);
            if (method_exists($item, $getter)) {
                return $item->$getter();
            }
        }

        return $item; // Если это просто скалярное значение
    }

    private function getHash(array $criteria): string
    {
        $normalized = array_map(function ($value) {
            if ($value instanceof \DateTimeInterface) {
                return $value->format('Y-m-d H:i:s');
            }
            return (string) $value;
        }, $criteria);

        ksort($normalized);

        return implode('|', $normalized);
    }
}
