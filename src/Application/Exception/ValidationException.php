<?php

namespace App\Application\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ValidationException extends HttpException
{
    private array $violations;

    public function __construct(array $violations, string $message = 'Validation failed', \Throwable $previous = null, int $code = 0, array $headers = [])
    {
        $this->violations = $violations;
        parent::__construct(400, $message, $previous, $headers, $code);
    }

    public function getViolations(): array
    {
        return $this->violations;
    }
}
