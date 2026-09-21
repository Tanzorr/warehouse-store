<?php

namespace App\Entity;

use App\Repository\StockAvailabilityRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: StockAvailabilityRepository::class)]
class StockAvailability
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'stockAvailabilities')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Product $product = null;


    #[ORM\ManyToOne(targetEntity: Warehouse::class, inversedBy: 'stockAvailabilities')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Warehouse $warehouse = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    private ?int $amount = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;
        return $this;
    }

    public function getWarehouse(): ?Warehouse
    {
        return $this->warehouse;
    }

    public function setWarehouse(?Warehouse $warehouse): static
    {
        $this->warehouse = $warehouse;
        return $this;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(?int $amount): static
    {
        $this->amount = $amount;
        return $this;
    }

    public function __toString()
    {
        return sprintf(
            'Stock of %s in %s: %d',
            $this->product ? $this->product->getName() : 'Unknown Product',
            $this->warehouse ? $this->warehouse->getName() : 'Unknown Warehouse',
            $this->amount ?: 0
        );
    }
}
