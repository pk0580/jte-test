<?php

namespace App\Domain\Entity;

use App\Domain\ValueObject\CustomerInfo;
use App\Domain\ValueObject\DeliveryAddress;
use App\Domain\ValueObject\DeliveryTerms;
use App\Domain\ValueObject\ManagerInfo;
use App\Domain\ValueObject\FinancialTerms;
use App\Domain\ValueObject\DeliveryConfig;
use App\Domain\ValueObject\OrderDates;
use App\Domain\ValueObject\OrderMetadata;
use App\Domain\ValueObject\OrderReview;
use App\Domain\Trait\AggregateRootTrait;
use App\Domain\Event\OrderCreatedEvent;
use App\Domain\Event\OrderUpdatedEvent;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Infrastructure\Persistence\Doctrine\Repository\OrderRepository')]
#[ORM\Table(name: 'orders')]
#[ORM\Index(columns: ['metadata_hash'], name: 'idx_orders_hash')]
#[ORM\Index(columns: ['metadata_token'], name: 'idx_orders_token')]
#[ORM\Index(columns: ['pay_type_id'], name: 'idx_orders_pay_type')]
#[ORM\Index(columns: ['status'], name: 'idx_orders_status')]
class Order
{
    use AggregateRootTrait;

    public const STATUS_NEW = 1;
    public const STATUS_PROCESSING = 2;
    public const STATUS_SHIPPED = 3;
    public const STATUS_DELIVERED = 4;
    public const STATUS_CANCELLED = 5;


    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Embedded(class: OrderMetadata::class)]
    private OrderMetadata $metadata;

    #[ORM\Column(nullable: true)]
    private ?int $userId = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $number = null;

    #[ORM\Column(type: 'smallint', options: ['unsigned' => true, 'default' => 1])]
    private int $status = 1;

    public function setInternalStatus(int $status): void
    {
        $this->status = $status;
        $this->recordEvent(new OrderUpdatedEvent($this));
    }

    public function setDates(OrderDates $dates): void
    {
        $this->dates = $dates;
    }

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

    #[ORM\Embedded(class: OrderDates::class)]
    private OrderDates $dates;

    #[ORM\Column(type: 'smallint', nullable: true, options: ['unsigned' => true])]
    private ?int $offsetReason = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $trackingNumber = null;

    #[ORM\Embedded(class: ManagerInfo::class)]
    private ManagerInfo $managerInfo;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $warehouseData = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $addressEqual = true;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $bankTransferRequested = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $acceptPay = null;

    #[ORM\Embedded(class: OrderReview::class)]
    private OrderReview $review;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $specPrice = null;

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
        CustomerInfo $customerInfo,
        DeliveryAddress $deliveryAddress,
        DeliveryTerms $deliveryTerms,
        ManagerInfo $managerInfo,
        FinancialTerms $financialTerms,
        DeliveryConfig $deliveryConfig,
        string $locale = 'ru',
        string $currency = 'EUR',
        string $measure = 'm',
        ?string $hash = null,
        ?string $token = null,
    ) {
        $this->metadata = new OrderMetadata(
            hash: $hash ?? bin2hex(random_bytes(16)),
            token: $token ?? bin2hex(random_bytes(32)),
            locale: $locale,
            measure: $measure
        );
        $this->dates = new OrderDates();
        $this->review = new OrderReview();
        $this->articles = new ArrayCollection();
        $this->payType = $payType;
        $this->name = $name;
        $this->customerInfo = $customerInfo;
        $this->deliveryAddress = $deliveryAddress;
        $this->deliveryTerms = $deliveryTerms;
        $this->managerInfo = $managerInfo;
        $this->financialTerms = $financialTerms;
        $this->deliveryConfig = $deliveryConfig;
        $this->status = self::STATUS_NEW;

        $this->recordEvent(new OrderCreatedEvent($this));
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

    public function getMetadata(): OrderMetadata
    {
        return $this->metadata;
    }

    public function getDates(): OrderDates
    {
        return $this->dates;
    }

    public function getReview(): OrderReview
    {
        return $this->review;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHash(): string
    {
        return $this->metadata->hash;
    }

    public function getToken(): string
    {
        return $this->metadata->token;
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
        (new \App\Domain\Service\OrderStatusManager())->changeStatus($this, $newStatus);

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
        return $this->dates->payDateExecution;
    }

    public function setPayDateExecution(?\DateTimeInterface $payDateExecution): self
    {
        $this->dates = new OrderDates(
            $this->dates->createAt,
            $this->dates->updateAt,
            $payDateExecution,
            $this->dates->offsetDate,
            $this->dates->proposedDate,
            $this->dates->shipDate,
            $this->dates->cancelDate,
            $this->dates->fullPaymentDate
        );
        return $this;
    }

    public function getOffsetDate(): ?\DateTimeInterface
    {
        return $this->dates->offsetDate;
    }

    public function setOffsetDate(?\DateTimeInterface $offsetDate): self
    {
        $this->dates = new OrderDates(
            $this->dates->createAt,
            $this->dates->updateAt,
            $this->dates->payDateExecution,
            $offsetDate,
            $this->dates->proposedDate,
            $this->dates->shipDate,
            $this->dates->cancelDate,
            $this->dates->fullPaymentDate
        );
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
        return $this->dates->proposedDate;
    }

    public function setProposedDate(?\DateTimeInterface $proposedDate): self
    {
        $this->dates = new OrderDates(
            $this->dates->createAt,
            $this->dates->updateAt,
            $this->dates->payDateExecution,
            $this->dates->offsetDate,
            $proposedDate,
            $this->dates->shipDate,
            $this->dates->cancelDate,
            $this->dates->fullPaymentDate
        );
        return $this;
    }

    public function getShipDate(): ?\DateTimeInterface
    {
        return $this->dates->shipDate;
    }

    public function setShipDate(?\DateTimeInterface $shipDate): self
    {
        $this->dates = new OrderDates(
            $this->dates->createAt,
            $this->dates->updateAt,
            $this->dates->payDateExecution,
            $this->dates->offsetDate,
            $this->dates->proposedDate,
            $shipDate,
            $this->dates->cancelDate,
            $this->dates->fullPaymentDate
        );
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
        return $this->metadata->locale;
    }

    protected function setLocale(string $locale): self
    {
        $this->metadata = new OrderMetadata(
            $this->metadata->hash,
            $this->metadata->token,
            $locale,
            $this->metadata->measure,
            $this->metadata->step,
            $this->metadata->mirror,
            $this->metadata->process,
            $this->metadata->showMsg
        );
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
        return $this->metadata->measure;
    }

    protected function setMeasure(string $measure): self
    {
        $this->metadata = new OrderMetadata(
            $this->metadata->hash,
            $this->metadata->token,
            $this->metadata->locale,
            $measure,
            $this->metadata->step,
            $this->metadata->mirror,
            $this->metadata->process,
            $this->metadata->showMsg
        );
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
        return $this->dates->createAt;
    }


    public function getUpdateDate(): ?\DateTimeInterface
    {
        return $this->dates->updateAt;
    }

    public function setUpdateDate(?\DateTimeInterface $updateDate): self
    {
        $this->dates = $this->dates->withUpdateAt($updateDate);
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
        return $this->metadata->step;
    }

    public function setStep(int $step): self
    {
        $this->metadata = new OrderMetadata(
            $this->metadata->hash,
            $this->metadata->token,
            $this->metadata->locale,
            $this->metadata->measure,
            $step,
            $this->metadata->mirror,
            $this->metadata->process,
            $this->metadata->showMsg
        );
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
        return $this->dates->cancelDate;
    }

    public function setCancelDate(?\DateTimeInterface $cancelDate): self
    {
        $this->dates = new OrderDates(
            $this->dates->createAt,
            $this->dates->updateAt,
            $this->dates->payDateExecution,
            $this->dates->offsetDate,
            $this->dates->proposedDate,
            $this->dates->shipDate,
            $cancelDate,
            $this->dates->fullPaymentDate
        );
        return $this;
    }

    public function getWeightGross(): ?string
    {
        return $this->deliveryConfig->weightGross;
    }

    public function getProductReview(): ?bool
    {
        return $this->review->productReview;
    }

    public function setProductReview(?bool $productReview): self
    {
        $this->review = new OrderReview(
            $productReview,
            $this->review->entranceReview
        );
        return $this;
    }

    public function getMirror(): ?int
    {
        return $this->metadata->mirror;
    }

    public function setMirror(?int $mirror): self
    {
        $this->metadata = new OrderMetadata(
            $this->metadata->hash,
            $this->metadata->token,
            $this->metadata->locale,
            $this->metadata->measure,
            $this->metadata->step,
            $mirror,
            $this->metadata->process,
            $this->metadata->showMsg
        );
        return $this;
    }

    public function getProcess(): ?bool
    {
        return $this->metadata->process;
    }

    public function setProcess(?bool $process): self
    {
        $this->metadata = new OrderMetadata(
            $this->metadata->hash,
            $this->metadata->token,
            $this->metadata->locale,
            $this->metadata->measure,
            $this->metadata->step,
            $this->metadata->mirror,
            $process,
            $this->metadata->showMsg
        );
        return $this;
    }

    public function getFactDate(): ?\DateTimeInterface
    {
        return $this->deliveryConfig->factDate;
    }

    public function getEntranceReview(): ?int
    {
        return $this->review->entranceReview;
    }

    public function setEntranceReview(?int $entranceReview): self
    {
        $this->review = new OrderReview(
            $this->review->productReview,
            $entranceReview
        );
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
        return $this->metadata->showMsg;
    }

    public function setShowMsg(?bool $showMsg): self
    {
        $this->metadata = new OrderMetadata(
            $this->metadata->hash,
            $this->metadata->token,
            $this->metadata->locale,
            $this->metadata->measure,
            $this->metadata->step,
            $this->metadata->mirror,
            $this->metadata->process,
            $showMsg
        );
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
        return $this->dates->fullPaymentDate;
    }

    public function setFullPaymentDate(?\DateTimeInterface $fullPaymentDate): self
    {
        $this->dates = new OrderDates(
            $this->dates->createAt,
            $this->dates->updateAt,
            $this->dates->payDateExecution,
            $this->dates->offsetDate,
            $this->dates->proposedDate,
            $this->dates->shipDate,
            $this->dates->cancelDate,
            $fullPaymentDate
        );
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


    public function setTotalAmount(string $totalAmount): self
    {
        $this->totalAmount = $totalAmount;
        return $this;
    }

    public function setTotalWeight(string $totalWeight): self
    {
        $this->totalWeight = $totalWeight;
        return $this;
    }

    public function addArticle(OrderArticle $article): self
    {
        if (!$this->articles->contains($article)) {
            $this->articles->add($article);
            $article->setOrder($this);
        }
        return $this;
    }

    public function removeArticle(OrderArticle $article): self
    {
        if ($this->articles->removeElement($article)) {
            if ($article->getOrder() === $this) {
                $article->setOrder(null);
            }
        }
        return $this;
    }
}
