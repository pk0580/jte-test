<?php

namespace App\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final readonly class FinancialTerms
{
    public function __construct(
        #[ORM\Column(type: 'smallint', options: ['unsigned' => true, 'default' => 0])]
        public int $vatType = 0,
        #[ORM\Column(length: 100, nullable: true)]
        public ?string $vatNumber = null,
        #[ORM\Column(length: 50, nullable: true)]
        public ?string $taxNumber = null,
        #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
        public ?string $discount = null,
        #[ORM\Column(type: 'decimal', precision: 14, scale: 6, nullable: true, options: ['default' => 1.0])]
        public ?string $curRate = '1.000000',
        #[ORM\Column(length: 3, options: ['default' => 'EUR'])]
        public string $currency = 'EUR',
        #[ORM\Column(type: 'boolean', options: ['default' => false])]
        public bool $paymentEuro = false,
        #[ORM\Column(type: 'text', nullable: true)]
        public ?string $bankDetails = null,
    ) {
    }
}
