<?php

namespace App\Domain\Entity;

use App\Domain\Contract\HasDomainEventsInterface;
use App\Domain\ValueObject\CustomerInfo;
use App\Domain\ValueObject\DeliveryAddress;
use App\Domain\ValueObject\DeliveryTerms;
use App\Domain\ValueObject\ManagerInfo;
use App\Domain\ValueObject\FinancialTerms;
use App\Domain\ValueObject\DeliveryConfig;
use App\Domain\ValueObject\OrderLogistics;
use App\Domain\ValueObject\OrderPricing;
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
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(columns: ['metadata_hash'], name: 'idx_orders_hash')]
#[ORM\Index(columns: ['metadata_token'], name: 'idx_orders_token')]
#[ORM\Index(columns: ['pay_type_id'], name: 'idx_orders_pay_type')]
#[ORM\Index(columns: ['status'], name: 'idx_orders_status')]
class Order implements HasDomainEventsInterface
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

    #[ORM\Embedded(class: OrderLogistics::class)]
    private OrderLogistics $logistics;

    #[ORM\Embedded(class: OrderPricing::class)]
    private OrderPricing $pricing;

    #[ORM\Embedded(class: OrderMetadata::class)]
    private OrderMetadata $metadata;

    #[ORM\Column(nullable: true)]
    private ?int $userId = null;

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

    #[ORM\Embedded(class: OrderDates::class)]
    private OrderDates $dates;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Embedded(class: ManagerInfo::class)]
    private ManagerInfo $managerInfo;

    #[ORM\Embedded(class: OrderReview::class)]
    private OrderReview $review;

    /**
     * @var Collection<int, OrderArticle>
     */
    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderArticle::class, cascade: ['persist', 'remove'])]
    private Collection $articles;

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
        ?string $description = null,
    ) {
        $this->metadata = new OrderMetadata(
            hash: $hash ?? bin2hex(random_bytes(16)),
            token: $token ?? bin2hex(random_bytes(32)),
            locale: $locale,
            measure: $measure,
            description: $description
        );
        $this->dates = new OrderDates();
        $this->review = new OrderReview();
        $this->logistics = new OrderLogistics();
        $this->pricing = new OrderPricing();
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

    public function getDeliveryAddress(): DeliveryAddress
    {
        return $this->deliveryAddress;
    }

    public function getDeliveryTerms(): DeliveryTerms
    {
        return $this->deliveryTerms;
    }

    public function getArticles(): Collection
    {
        return $this->articles;
    }

    public function getPayType(): PayType
    {
        return $this->payType;
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

    public function getLogistics(): OrderLogistics
    {
        return $this->logistics;
    }

    public function getPricing(): OrderPricing
    {
        return $this->pricing;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function assignNumber(string $number): self
    {
        $this->number = $number;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function changeStatus(\App\Domain\Service\OrderStatusManager $statusManager, int $newStatus): self
    {
        $statusManager->changeStatus($this, $newStatus);

        return $this;
    }

    public function setInternalStatus(int $status): void
    {
        $this->status = $status;
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

    public function setDates(OrderDates $dates): self
    {
        $this->dates = $dates;
        return $this;
    }

    public function setTotalAmount(string $totalAmount): self
    {
        $this->pricing = $this->pricing->withTotalAmount($totalAmount);
        return $this;
    }

    public function setTotalWeight(string $totalWeight): self
    {
        $this->pricing = $this->pricing->withTotalWeight($totalWeight);
        return $this;
    }

    public function updateTimestamp(): void
    {
        $this->dates = $this->dates->withUpdateAt(new \DateTime());
        $this->recordEvent(new OrderUpdatedEvent($this));
    }

    public function recalculateTotals(\App\Domain\Service\OrderPriceCalculator $calculator): void
    {
        $calculator->recalculate($this);
    }
}
