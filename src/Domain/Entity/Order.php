<?php

namespace App\Domain\Entity;

use App\Domain\ValueObject\CustomerInfo;
use App\Domain\ValueObject\DeliveryAddress;
use App\Domain\ValueObject\DeliveryTerms;
use App\Domain\ValueObject\ManagerInfo;
use App\Domain\ValueObject\FinancialTerms;
use App\Domain\ValueObject\DeliveryConfig;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Infrastructure\Persistence\Doctrine\Repository\OrderRepository')]
#[ORM\Table(name: 'orders')]
#[ORM\Index(columns: ['hash'], name: 'idx_orders_hash')]
#[ORM\Index(columns: ['token'], name: 'idx_orders_token')]
class Order
{
    public const STATUS_NEW = 1;
    public const STATUS_PROCESSING = 2;
    public const STATUS_SHIPPED = 3;
    public const STATUS_DELIVERED = 4;
    public const STATUS_CANCELLED = 5;

    private static array $allowedTransitions = [
        self::STATUS_NEW => [self::STATUS_PROCESSING, self::STATUS_CANCELLED],
        self::STATUS_PROCESSING => [self::STATUS_SHIPPED, self::STATUS_CANCELLED],
        self::STATUS_SHIPPED => [self::STATUS_DELIVERED],
        self::STATUS_DELIVERED => [],
        self::STATUS_CANCELLED => [],
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 32)]
    private string $hash;

    #[ORM\Column(nullable: true)]
    private ?int $userId = null;

    #[ORM\Column(length: 64)]
    private string $token;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $number = null;

    #[ORM\Column(type: 'smallint', options: ['unsigned' => true, 'default' => 1])]
    private int $status = 1;

    #[ORM\Embedded(class: CustomerInfo::class)]
    private CustomerInfo $customerInfo;

    #[ORM\Embedded(class: FinancialTerms::class)]
    private FinancialTerms $financialTerms;

    #[ORM\Embedded(class: DeliveryTerms::class)]
    private DeliveryTerms $deliveryTerms;

    #[ORM\Embedded(class: DeliveryConfig::class)]
    private DeliveryConfig $deliveryConfig;

    #[ORM\Embedded(class: DeliveryAddress::class)]
    private DeliveryAddress $deliveryAddress;

    #[ORM\ManyToOne(targetEntity: PayType::class)]
    #[ORM\JoinColumn(name: 'pay_type_id', referencedColumnName: 'id', nullable: false)]
    private PayType $payType;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $payDateExecution = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $offsetDate = null;

    #[ORM\Column(type: 'smallint', nullable: true, options: ['unsigned' => true])]
    private ?int $offsetReason = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $proposedDate = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $shipDate = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $trackingNumber = null;

    #[ORM\Embedded(class: ManagerInfo::class)]
    private ManagerInfo $managerInfo;

    #[ORM\Column(length: 5)]
    private string $locale;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 3, options: ['default' => 'EUR'])]
    private string $currency = 'EUR';

    #[ORM\Column(length: 10, options: ['default' => 'unit'])]
    private string $measure = 'unit';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTimeImmutable $createDate;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updateDate = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $warehouseData = null;

    #[ORM\Column(type: 'smallint', options: ['unsigned' => true, 'default' => 1])]
    private int $step = 1;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $addressEqual = true;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $bankTransferRequested = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $acceptPay = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $cancelDate = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $productReview = null;

    #[ORM\Column(type: 'smallint', nullable: true, options: ['unsigned' => true])]
    private ?int $mirror = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $process = null;

    #[ORM\Column(type: 'smallint', nullable: true, options: ['unsigned' => true])]
    private ?int $entranceReview = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $specPrice = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $showMsg = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?string $deliveryPriceEuro = null;

    #[ORM\Column(type: 'bigint', nullable: true, options: ['unsigned' => true])]
    private ?string $addressPayer = null;

    /**
     * @var Collection<int, OrderArticle>
     */
    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderArticle::class, cascade: ['persist', 'remove'])]
    private Collection $articles;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, options: ['default' => '0.00'])]
    private string $totalAmount = '0.00';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 3, options: ['default' => '0.000'])]
    private string $totalWeight = '0.000';

    public function __construct(
        PayType $payType,
        string $name,
        string $locale = 'ru',
        string $currency = 'EUR',
        string $measure = 'm',
        CustomerInfo $customerInfo = new CustomerInfo(),
        DeliveryAddress $deliveryAddress = new DeliveryAddress(),
        DeliveryTerms $deliveryTerms = new DeliveryTerms(),
        ManagerInfo $managerInfo = new ManagerInfo(),
        FinancialTerms $financialTerms = new FinancialTerms(),
        DeliveryConfig $deliveryConfig = new DeliveryConfig(),
        ?string $hash = null,
        ?string $token = null,
    ) {
        $this->hash = $hash ?? bin2hex(random_bytes(16));
        $this->token = $token ?? bin2hex(random_bytes(32));
        $this->articles = new ArrayCollection();
        $this->createDate = new \DateTimeImmutable();
        $this->payType = $payType;
        $this->name = $name;
        $this->locale = $locale;
        $this->currency = $currency;
        $this->measure = $measure;
        $this->customerInfo = $customerInfo;
        $this->deliveryAddress = $deliveryAddress;
        $this->deliveryTerms = $deliveryTerms;
        $this->managerInfo = $managerInfo;
        $this->financialTerms = $financialTerms;
        $this->deliveryConfig = $deliveryConfig;
        $this->status = self::STATUS_NEW;
    }

    public function getCustomerInfo(): CustomerInfo
    {
        return $this->customerInfo;
    }

    public function getClientName(): ?string
    {
        return $this->customerInfo->name;
    }

    public function getClientSurname(): ?string
    {
        return $this->customerInfo->surname;
    }

    public function getEmail(): ?string
    {
        return $this->customerInfo->email;
    }

    protected function setCustomerInfo(CustomerInfo $customerInfo): self
    {
        $this->customerInfo = $customerInfo;
        return $this;
    }

    public function getDeliveryAddress(): DeliveryAddress
    {
        return $this->deliveryAddress;
    }

    protected function setDeliveryAddress(DeliveryAddress $deliveryAddress): self
    {
        $this->deliveryAddress = $deliveryAddress;
        return $this;
    }

    public function getDeliveryTerms(): DeliveryTerms
    {
        return $this->deliveryTerms;
    }

    protected function setDeliveryTerms(DeliveryTerms $deliveryTerms): self
    {
        $this->deliveryTerms = $deliveryTerms;
        return $this;
    }

    /**
     * @return Collection<int, OrderArticle>
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }

    public function getPayType(): PayType
    {
        return $this->payType;
    }

    protected function setPayType(PayType $payType): self
    {
        $this->payType = $payType;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getFinancialTerms(): FinancialTerms
    {
        return $this->financialTerms;
    }

    public function getDeliveryConfig(): DeliveryConfig
    {
        return $this->deliveryConfig;
    }

    public function getManagerInfo(): ManagerInfo
    {
        return $this->managerInfo;
    }

    public function updateManagerInfo(ManagerInfo $managerInfo): self
    {
        $this->managerInfo = $managerInfo;
        return $this;
    }

    public function updateFinancialTerms(FinancialTerms $financialTerms): self
    {
        $this->financialTerms = $financialTerms;
        return $this;
    }

    public function updateDeliveryConfig(DeliveryConfig $deliveryConfig): self
    {
        $this->deliveryConfig = $deliveryConfig;
        return $this;
    }

    public function assignNumber(string $number): self
    {
        $this->number = $number;
        return $this;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function changeStatus(int $newStatus): self
    {
        if ($this->status === $newStatus) {
            return $this;
        }

        if (!isset(self::$allowedTransitions[$this->status]) || !in_array($newStatus, self::$allowedTransitions[$this->status], true)) {
            throw \App\Domain\Exception\InvalidOrderStateException::transitionNotAllowed($this->status, $newStatus);
        }

        $this->status = $newStatus;
        $this->updateDate = new \DateTime();

        if ($newStatus === self::STATUS_CANCELLED) {
            $this->cancelDate = new \DateTime();
        }

        return $this;
    }


    public function getVatType(): int
    {
        return $this->financialTerms->vatType;
    }

    public function getVatNumber(): ?string
    {
        return $this->financialTerms->vatNumber;
    }

    public function getTaxNumber(): ?string
    {
        return $this->financialTerms->taxNumber;
    }

    public function getDiscount(): ?string
    {
        return $this->financialTerms->discount;
    }


    public function getDeliveryTimeConfirmMin(): ?\DateTimeInterface
    {
        return $this->deliveryConfig->deliveryTimeConfirmMin;
    }

    public function getDeliveryTimeConfirmMax(): ?\DateTimeInterface
    {
        return $this->deliveryConfig->deliveryTimeConfirmMax;
    }

    public function getDeliveryTimeFastPayMin(): ?\DateTimeInterface
    {
        return $this->deliveryConfig->deliveryTimeFastPayMin;
    }

    public function getDeliveryTimeFastPayMax(): ?\DateTimeInterface
    {
        return $this->deliveryConfig->deliveryTimeFastPayMax;
    }

    public function getDeliveryOldTimeMin(): ?\DateTimeInterface
    {
        return $this->deliveryConfig->deliveryOldTimeMin;
    }

    public function getDeliveryOldTimeMax(): ?\DateTimeInterface
    {
        return $this->deliveryConfig->deliveryOldTimeMax;
    }


    public function getPayDateExecution(): ?\DateTimeInterface
    {
        return $this->payDateExecution;
    }

    public function setPayDateExecution(?\DateTimeInterface $payDateExecution): self
    {
        $this->payDateExecution = $payDateExecution;
        return $this;
    }

    public function getOffsetDate(): ?\DateTimeInterface
    {
        return $this->offsetDate;
    }

    public function setOffsetDate(?\DateTimeInterface $offsetDate): self
    {
        $this->offsetDate = $offsetDate;
        return $this;
    }

    public function getOffsetReason(): ?int
    {
        return $this->offsetReason;
    }

    public function setOffsetReason(?int $offsetReason): self
    {
        $this->offsetReason = $offsetReason;
        return $this;
    }

    public function getProposedDate(): ?\DateTimeInterface
    {
        return $this->proposedDate;
    }

    public function setProposedDate(?\DateTimeInterface $proposedDate): self
    {
        $this->proposedDate = $proposedDate;
        return $this;
    }

    public function getShipDate(): ?\DateTimeInterface
    {
        return $this->shipDate;
    }

    public function setShipDate(?\DateTimeInterface $shipDate): self
    {
        $this->shipDate = $shipDate;
        return $this;
    }

    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    public function setTrackingNumber(?string $trackingNumber): self
    {
        $this->trackingNumber = $trackingNumber;
        return $this;
    }

    public function getManagerName(): ?string
    {
        return $this->managerInfo->name;
    }

    public function getManagerEmail(): ?string
    {
        return $this->managerInfo->email;
    }

    public function getManagerPhone(): ?string
    {
        return $this->managerInfo->phone;
    }

    public function getCarrierName(): ?string
    {
        return $this->deliveryConfig->carrierName;
    }

    public function getCarrierContactData(): ?string
    {
        return $this->deliveryConfig->carrierContactData;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    protected function setLocale(string $locale): self
    {
        $this->locale = $locale;
        return $this;
    }

    public function getCurRate(): ?string
    {
        return $this->financialTerms->curRate;
    }

    public function getCurrency(): string
    {
        return $this->financialTerms->currency;
    }

    public function getMeasure(): string
    {
        return $this->measure;
    }

    protected function setMeasure(string $measure): self
    {
        $this->measure = $measure;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    protected function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getCreateDate(): \DateTimeImmutable
    {
        return $this->createDate;
    }


    public function getUpdateDate(): ?\DateTimeInterface
    {
        return $this->updateDate;
    }

    public function setUpdateDate(?\DateTimeInterface $updateDate): self
    {
        $this->updateDate = $updateDate;
        return $this;
    }

    public function getWarehouseData(): ?array
    {
        return $this->warehouseData;
    }

    public function setWarehouseData(?array $warehouseData): self
    {
        $this->warehouseData = $warehouseData;
        return $this;
    }

    public function getStep(): int
    {
        return $this->step;
    }

    public function setStep(int $step): self
    {
        $this->step = $step;
        return $this;
    }

    public function isAddressEqual(): bool
    {
        return $this->addressEqual;
    }

    public function setAddressEqual(bool $addressEqual): self
    {
        $this->addressEqual = $addressEqual;
        return $this;
    }

    public function getBankTransferRequested(): ?bool
    {
        return $this->bankTransferRequested;
    }

    public function setBankTransferRequested(?bool $bankTransferRequested): self
    {
        $this->bankTransferRequested = $bankTransferRequested;
        return $this;
    }

    public function getAcceptPay(): ?bool
    {
        return $this->acceptPay;
    }

    public function setAcceptPay(?bool $acceptPay): self
    {
        $this->acceptPay = $acceptPay;
        return $this;
    }

    public function getCancelDate(): ?\DateTimeInterface
    {
        return $this->cancelDate;
    }

    public function setCancelDate(?\DateTimeInterface $cancelDate): self
    {
        $this->cancelDate = $cancelDate;
        return $this;
    }

    public function getWeightGross(): ?string
    {
        return $this->deliveryConfig->weightGross;
    }

    public function getProductReview(): ?bool
    {
        return $this->productReview;
    }

    public function setProductReview(?bool $productReview): self
    {
        $this->productReview = $productReview;
        return $this;
    }

    public function getMirror(): ?int
    {
        return $this->mirror;
    }

    public function setMirror(?int $mirror): self
    {
        $this->mirror = $mirror;
        return $this;
    }

    public function getProcess(): ?bool
    {
        return $this->process;
    }

    public function setProcess(?bool $process): self
    {
        $this->process = $process;
        return $this;
    }

    public function getFactDate(): ?\DateTimeInterface
    {
        return $this->deliveryConfig->factDate;
    }

    public function getEntranceReview(): ?int
    {
        return $this->entranceReview;
    }

    public function setEntranceReview(?int $entranceReview): self
    {
        $this->entranceReview = $entranceReview;
        return $this;
    }

    public function isPaymentEuro(): bool
    {
        return $this->financialTerms->paymentEuro;
    }

    public function getSpecPrice(): ?bool
    {
        return $this->specPrice;
    }

    public function setSpecPrice(?bool $specPrice): self
    {
        $this->specPrice = $specPrice;
        return $this;
    }

    public function getShowMsg(): ?bool
    {
        return $this->showMsg;
    }

    public function setShowMsg(?bool $showMsg): self
    {
        $this->showMsg = $showMsg;
        return $this;
    }

    public function getDeliveryPriceEuro(): ?string
    {
        return $this->deliveryPriceEuro;
    }

    public function setDeliveryPriceEuro(?string $deliveryPriceEuro): self
    {
        $this->deliveryPriceEuro = $deliveryPriceEuro;
        return $this;
    }

    public function getAddressPayer(): ?string
    {
        return $this->addressPayer;
    }

    public function setAddressPayer(?string $addressPayer): self
    {
        $this->addressPayer = $addressPayer;
        return $this;
    }

    public function getSendingDate(): ?\DateTimeInterface
    {
        return $this->deliveryConfig->sendingDate;
    }

    public function getDeliveryCalculateType(): ?int
    {
        return $this->deliveryConfig->deliveryCalculateType;
    }

    public function getFullPaymentDate(): ?\DateTimeInterface
    {
        return $this->fullPaymentDate;
    }

    public function setFullPaymentDate(?\DateTimeInterface $fullPaymentDate): self
    {
        $this->fullPaymentDate = $fullPaymentDate;
        return $this;
    }

    public function getBankDetails(): ?string
    {
        return $this->financialTerms->bankDetails;
    }

    /**
     * @return Collection<int, OrderArticle>
     */
    public function getTotalAmount(): string
    {
        return $this->totalAmount;
    }

    public function getTotalWeight(): string
    {
        return $this->totalWeight;
    }


    public function recalculateTotals(): self
    {
        $amount = '0.00';
        $weight = '0.000';

        foreach ($this->articles as $article) {
            $amount = bcadd($amount, bcmul($article->getAmount(), $article->getPrice(), 10), 2);
            $weight = bcadd($weight, bcmul($article->getAmount(), $article->getWeight(), 10), 3);
        }

        $this->totalAmount = $amount;
        $this->totalWeight = $weight;

        return $this;
    }

    public function addArticle(OrderArticle $article): self
    {
        if (!$this->articles->contains($article)) {
            $this->articles->add($article);
            $article->setOrder($this);
            $this->recalculateTotals();
        }
        return $this;
    }

    public function removeArticle(OrderArticle $article): self
    {
        if ($this->articles->removeElement($article)) {
            if ($article->getOrder() === $this) {
                $article->setOrder(null);
            }
            $this->recalculateTotals();
        }
        return $this;
    }
}
