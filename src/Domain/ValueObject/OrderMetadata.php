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
        public ?bool $showMsg = null,

        #[ORM\Column(type: 'text', nullable: true)]
        public ?string $description = null,

        #[ORM\Column(type: 'boolean', nullable: true)]
        public ?bool $bankTransferRequested = null,

        #[ORM\Column(type: 'boolean', nullable: true)]
        public ?bool $acceptPay = null
    ) {
    }

    public function withLocale(string $locale): self
    {
        return new self($this->hash, $this->token, $locale, $this->measure, $this->step, $this->mirror, $this->process, $this->showMsg, $this->description, $this->bankTransferRequested, $this->acceptPay);
    }

    public function withMeasure(string $measure): self
    {
        return new self($this->hash, $this->token, $this->locale, $measure, $this->step, $this->mirror, $this->process, $this->showMsg, $this->description, $this->bankTransferRequested, $this->acceptPay);
    }

    public function withStep(int $step): self
    {
        return new self($this->hash, $this->token, $this->locale, $this->measure, $step, $this->mirror, $this->process, $this->showMsg, $this->description, $this->bankTransferRequested, $this->acceptPay);
    }

    public function withMirror(?int $mirror): self
    {
        return new self($this->hash, $this->token, $this->locale, $this->measure, $this->step, $mirror, $this->process, $this->showMsg, $this->description, $this->bankTransferRequested, $this->acceptPay);
    }

    public function withProcess(?bool $process): self
    {
        return new self($this->hash, $this->token, $this->locale, $this->measure, $this->step, $this->mirror, $process, $this->showMsg, $this->description, $this->bankTransferRequested, $this->acceptPay);
    }

    public function withShowMsg(?bool $showMsg): self
    {
        return new self($this->hash, $this->token, $this->locale, $this->measure, $this->step, $this->mirror, $this->process, $showMsg, $this->description, $this->bankTransferRequested, $this->acceptPay);
    }

    public function withDescription(?string $description): self
    {
        return new self($this->hash, $this->token, $this->locale, $this->measure, $this->step, $this->mirror, $this->process, $this->showMsg, $description, $this->bankTransferRequested, $this->acceptPay);
    }

    public function withBankTransferRequested(?bool $bankTransferRequested): self
    {
        return new self($this->hash, $this->token, $this->locale, $this->measure, $this->step, $this->mirror, $this->process, $this->showMsg, $this->description, $bankTransferRequested, $this->acceptPay);
    }

    public function withAcceptPay(?bool $acceptPay): self
    {
        return new self($this->hash, $this->token, $this->locale, $this->measure, $this->step, $this->mirror, $this->process, $this->showMsg, $this->description, $this->bankTransferRequested, $acceptPay);
    }
}
