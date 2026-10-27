<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class InventoryTransaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $entityId = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?int $warehouseId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $comment = null;

    #[ORM\Column]
    private ?int $productId = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column]
    private ?string $entityType = null;

    #[ORM\Column]
    private ?int $userId = null;

    public function __construct(
        ?int                $id = null,
        ?int                $entityId = null,
        ?\DateTimeImmutable $createdAt = null,
        ?int                $warehouseId = null,
        ?string             $comment = null,
        ?int                $productId = null,
        ?int                $quantity = null,
        ?int                $userId = null
        , ?string           $entityType = null
    )
    {
        $this->id = $id;
        $this->createdAt = $createdAt ?: new \DateTimeImmutable();
        $this->warehouseId = $warehouseId;
        $this->entityId = $entityId;
        $this->comment = $comment;
        $this->productId = $productId;
        $this->quantity = $quantity;
        $this->userId = $userId;
        $this->entityType = $entityType ?? 'inventory_transaction';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function setEntityId(int $reservation_id): static
    {
        $this->entityId = $reservation_id;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getWarehouseId(): ?int
    {
        return $this->warehouseId;
    }

    public function setWarehouseId(int $warehouseId): static
    {
        $this->warehouseId = $warehouseId;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;
        return $this;
    }

    public function getProductId(): ?int
    {
        return $this->productId;
    }

    public function setProductId(int $productId): static
    {
        $this->productId = $productId;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    public function getEntityType(): ?string
    {
        return $this->entityType;
    }


    public function setEntityType(string $entityType): static
    {
        $this->entityType = $entityType;

        return $this;
    }
}
