<?php

namespace App\Application\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

use Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidationException extends HttpException
{
    /**
     * @var array<string, string>
     */
    private array $violations;

    /**
     * @param array<string, string> $violations
     * @param array<string, mixed> $headers
     */
    public function __construct(array $violations, string $message = 'Validation failed', ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        $this->violations = $violations;
        parent::__construct(400, $message, $previous, $headers, $code);
    }

    /**
     * @return array<string, string>
     */
    public function getViolations(): array
    {
        return $this->violations;
    }
}
