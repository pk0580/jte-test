<?php

namespace App\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final readonly class OrderMetadata
{
    public function __construct(
        #[ORM\Column(length: 32)]
        public string $hash,

        #[ORM\Column(length: 64)]
        public string $token,

        #[ORM\Column(length: 5)]
        public string $locale = 'ru',

        #[ORM\Column(length: 10, options: ['default' => 'unit'])]
        public string $measure = 'unit',

        #[ORM\Column(type: 'smallint', options: ['unsigned' => true, 'default' => 1])]
        public int $step = 1,

        #[ORM\Column(type: 'smallint', nullable: true, options: ['unsigned' => true])]
        public ?int $mirror = null,

        #[ORM\Column(type: 'boolean', nullable: true)]
        public ?bool $process = null,

        #[ORM\Column(type: 'boolean', nullable: true)]
        public ?bool $showMsg = null
    ) {
    }
}
