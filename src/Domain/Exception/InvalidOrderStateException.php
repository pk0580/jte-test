<?php

namespace App\Domain\Exception;

class InvalidOrderStateException extends \DomainException
{
    public static function transitionNotAllowed(int $from, int $to): self
    {
        return new self(sprintf('Transition from status %d to %d is not allowed.', $from, $to));
    }
}
