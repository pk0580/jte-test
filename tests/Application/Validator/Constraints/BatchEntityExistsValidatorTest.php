<?php

namespace App\Tests\Application\Validator\Constraints;

use App\Application\Validator\Constraints\BatchEntityExists;
use App\Application\Validator\Constraints\BatchEntityExistsValidator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class BatchEntityExistsValidatorTest extends TestCase
{
    private $entityManager;
    private $context;
    private $validator;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator = new BatchEntityExistsValidator($this->entityManager);
        $this->validator->initialize($this->context);
    }

    public function testValidateWithNullValueAndAllowNullTrue()
    {
        $constraint = new BatchEntityExists(
            entity: 'App\Entity\SomeEntity',
            fields: ['id'],
            allowNull: true
        );

        // Значение null, которое должно быть пропущено благодаря allowNull: true
        $value = [
            ['id' => null]
        ];

        // Мы НЕ ожидаем вызова QueryBuilder, так как criteriaList должен быть пуст
        $this->entityManager->expects($this->never())
            ->method('createQueryBuilder');

        // Мы НЕ ожидаем нарушений валидации
        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($value, $constraint);
    }

    public function testValidateWithMixedValuesAndAllowNullTrue()
    {
        $constraint = new BatchEntityExists(
            entity: 'App\Entity\SomeEntity',
            fields: ['id'],
            allowNull: true
        );

        $value = [
            ['id' => 1],
            ['id' => null]
        ];

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $expr = new \Doctrine\ORM\Query\Expr();

        $this->entityManager->method('createQueryBuilder')->willReturn($qb);
        $qb->method('from')->willReturn($qb);
        $qb->method('select')->willReturn($qb);
        $qb->method('where')->willReturn($qb);
        $qb->method('setParameter')->willReturn($qb);
        $qb->method('expr')->willReturn($expr);
        $qb->method('getQuery')->willReturn($query);

        // Эмулируем, что '1' найден в базе
        $query->method('getArrayResult')->willReturn([
            ['id' => 1]
        ]);

        // Мы НЕ ожидаем нарушений валидации
        // Элемент с id=1 найден, а элемент с id=null должен быть пропущен
        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($value, $constraint);
    }
    public function testValidateWithMissingValue()
    {
        $constraint = new BatchEntityExists(
            entity: 'App\Entity\SomeEntity',
            fields: ['id'],
            allowNull: false
        );

        $value = [
            ['id' => 1]
        ];

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $expr = new \Doctrine\ORM\Query\Expr();

        $this->entityManager->method('createQueryBuilder')->willReturn($qb);
        $qb->method('from')->willReturn($qb);
        $qb->method('select')->willReturn($qb);
        $qb->method('where')->willReturn($qb);
        $qb->method('setParameter')->willReturn($qb);
        $qb->method('expr')->willReturn($expr);
        $qb->method('getQuery')->willReturn($query);

        // Эмулируем, что ничего не найдено
        $query->method('getArrayResult')->willReturn([]);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->message)
            ->willReturn($violationBuilder);

        $violationBuilder->expects($this->once())
            ->method('atPath')
            ->with('[0]')
            ->willReturn($violationBuilder);
        $violationBuilder->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturn($violationBuilder);
        $violationBuilder->expects($this->once())
            ->method('addViolation');

        $this->validator->validate($value, $constraint);
    }

    public function testValidateWithDateTime()
    {
        $dateTime = new \DateTime('2023-01-01 00:00:00');
        $constraint = new BatchEntityExists(
            entity: 'App\Entity\SomeEntity',
            fields: ['createdAt'],
            allowNull: false
        );

        $value = [
            ['createdAt' => $dateTime]
        ];

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $expr = new \Doctrine\ORM\Query\Expr();

        $this->entityManager->method('createQueryBuilder')->willReturn($qb);
        $qb->method('from')->willReturn($qb);
        $qb->method('select')->willReturn($qb);
        $qb->method('where')->willReturn($qb);
        $qb->method('setParameter')->willReturn($qb);
        $qb->method('expr')->willReturn($expr);
        $qb->method('getQuery')->willReturn($query);

        // SQL возвращает строку
        $query->method('getArrayResult')->willReturn([
            ['createdAt' => '2023-01-01 00:00:00']
        ]);

        // Мы НЕ ожидаем нарушений валидации
        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($value, $constraint);
    }
}
