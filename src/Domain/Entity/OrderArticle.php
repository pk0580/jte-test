<?php

namespace App\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'orders_article')]
class OrderArticle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'articles')]
    #[ORM\JoinColumn(name: 'orders_id', referencedColumnName: 'id')]
    private ?Order $order = null;

    #[ORM\Column(nullable: true)]
    private ?int $articleId = null;

    #[ORM\Column(type: 'decimal', precision: 14, scale: 3)]
    private string $amount;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $price;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?string $priceEur = null;

    #[ORM\Column(length: 3, nullable: true)]
    private ?string $currency = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $measure = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $deliveryTimeMin = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $deliveryTimeMax = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 3)]
    private string $weight;

    #[ORM\Column(type: 'smallint', nullable: true, options: ['unsigned' => true])]
    private ?int $multiplePallet = null;

    #[ORM\Column(type: 'decimal', precision: 14, scale: 3)]
    private string $packagingCount;

    #[ORM\Column(type: 'decimal', precision: 14, scale: 3)]
    private string $pallet;

    #[ORM\Column(type: 'decimal', precision: 14, scale: 3)]
    private string $packaging;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $swimmingPool = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): self
    {
        $this->order = $order;
        return $this;
    }

    public function getArticleId(): ?int
    {
        return $this->articleId;
    }

    public function setArticleId(?int $articleId): self
    {
        $this->articleId = $articleId;
        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getPrice(): string
    {
        return $this->price;
    }

    public function setPrice(string $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getPriceEur(): ?string
    {
        return $this->priceEur;
    }

    public function setPriceEur(?string $priceEur): self
    {
        $this->priceEur = $priceEur;
        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function getMeasure(): ?string
    {
        return $this->measure;
    }

    public function setMeasure(?string $measure): self
    {
        $this->measure = $measure;
        return $this;
    }

    public function getDeliveryTimeMin(): ?\DateTimeInterface
    {
        return $this->deliveryTimeMin;
    }

    public function setDeliveryTimeMin(?\DateTimeInterface $deliveryTimeMin): self
    {
        $this->deliveryTimeMin = $deliveryTimeMin;
        return $this;
    }

    public function getDeliveryTimeMax(): ?\DateTimeInterface
    {
        return $this->deliveryTimeMax;
    }

    public function setDeliveryTimeMax(?\DateTimeInterface $deliveryTimeMax): self
    {
        $this->deliveryTimeMax = $deliveryTimeMax;
        return $this;
    }

    public function getWeight(): string
    {
        return $this->weight;
    }

    public function setWeight(string $weight): self
    {
        $this->weight = $weight;
        return $this;
    }

    public function getMultiplePallet(): ?int
    {
        return $this->multiplePallet;
    }

    public function setMultiplePallet(?int $multiplePallet): self
    {
        $this->multiplePallet = $multiplePallet;
        return $this;
    }

    public function getPackagingCount(): string
    {
        return $this->packagingCount;
    }

    public function setPackagingCount(string $packagingCount): self
    {
        $this->packagingCount = $packagingCount;
        return $this;
    }

    public function getPallet(): string
    {
        return $this->pallet;
    }

    public function setPallet(string $pallet): self
    {
        $this->pallet = $pallet;
        return $this;
    }

    public function getPackaging(): string
    {
        return $this->packaging;
    }

    public function setPackaging(string $packaging): self
    {
        $this->packaging = $packaging;
        return $this;
    }

    public function isSwimmingPool(): bool
    {
        return $this->swimmingPool;
    }

    public function setSwimmingPool(bool $swimmingPool): self
    {
        $this->swimmingPool = $swimmingPool;
        return $this;
    }
}
