<?php

namespace App\Entity;

use App\Repository\ProductReservationItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use App\Validator\Constraints as CustomAssert;

#[CustomAssert\ReservationItemAmount]
#[ORM\Entity(repositoryClass: ProductReservationItemRepository::class)]
class ProductReservationItem
{
    const GROUP_CREATE = 'product_reservation_item_create';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ProductReservation::class, inversedBy: "reservationItems")]
    #[ORM\JoinColumn(nullable: false)]
    private ProductReservation $productReservation;
    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: "reservationItems")]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(self::GROUP_CREATE)]
    private ?Product $product = null;

    #[ORM\Column]
    #[Groups(self::GROUP_CREATE)]
    private ?int $amount = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public  function getProductReservation(): ProductReservation
    {
        return $this->productReservation;
    }

    public function setProductReservation(ProductReservation $productReservation): static
    {
        $this->productReservation = $productReservation;

        return $this;
    }



    public function __toString(): string
    {
        return sprintf(
            '%s (%d)',
            $this->product ? $this->product->getName() : 'Unknown Product',
            $this->amount ? $this->amount : 0
        );
    }
}
