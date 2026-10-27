<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\ApiProperty;
use App\ApiResource\StateProcessor\ProductReservationTransitionProcessor;
use App\Constants\ReservationStatusMessage;
use App\DTO\ReservationStatusInput;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Post(
            denormalizationContext: ['groups' => [self::GROUP_CREATE, ProductReservationItem::GROUP_CREATE]]
        ),
        new Patch(
            input: ReservationStatusInput::class,
            processor: ProductReservationTransitionProcessor::class
        ),
        new Delete()
    ]
)]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[ORM\EntityListeners(['App\EventListener\ProductReservationListener'])]
class ProductReservation
{
    const GROUP_CREATE = 'product_reservation_create';
    const GROUP_UPDATE = 'product_reservation_update';

    public const STATUS_PENDING = 'pending';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_COMMITTED = 'committed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(
        choices: [
            self::STATUS_CANCELED,
            self::STATUS_COMMITTED,
            self::STATUS_PENDING
        ],
        message: ReservationStatusMessage::ERROR_WRONG_STATUS
    )]
    #[Groups([self::GROUP_CREATE, self::GROUP_UPDATE])]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column]
    private \DateTimeImmutable $reservedAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $releasedAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(self::GROUP_CREATE)]
    private ?string $comment = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(
        targetEntity: ProductReservationItem::class,
        mappedBy: 'productReservation',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[Groups([self::GROUP_CREATE])]
    #[Assert\Valid]
    private Collection $items;

    private ?string $marking = null;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->reservedAt = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function updateTimestamps(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getReservedAt(): \DateTimeImmutable
    {
        return $this->reservedAt;
    }

    public function setReservedAt(\DateTimeImmutable $reservedAt): self
    {
        $this->reservedAt = $reservedAt;
        return $this;
    }

    public function getReleasedAt(): ?\DateTimeImmutable
    {
        return $this->releasedAt;
    }

    public function setReleasedAt(?\DateTimeImmutable $releasedAt): self
    {
        $this->releasedAt = $releasedAt;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(ProductReservationItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items[] = $item;
            $item->setProductReservation($this);
        }
        return $this;
    }

    public function removeItem(ProductReservationItem $item): self
    {
        if ($this->items->removeElement($item)) {
            if ($item->getProductReservation() === $this) {
                $item->setProductReservation(null);
            }
        }
        return $this;
    }

    public function getMarking(): ?string
    {
        return $this->marking;
    }

    public function setMarking(?string $marking): void
    {
        $this->marking = $marking;
    }

    public function __toString(): string
    {
        return $this->getComment() ?? 'Reservation ' . $this->getId() . ' at ' . $this->getReservedAt()->format('Y-m-d H:i:s');
    }
}
