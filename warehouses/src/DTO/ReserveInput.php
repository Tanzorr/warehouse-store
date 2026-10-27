<?php

namespace App\DTO;

use Symfony\Component\Serializer\Annotation\Type;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Type as AssertType;

class ReserveInput
{
    /**
     * @var ReserveProductInput[]
     */
    #[Assert\NotBlank]
    #[Assert\Valid]
    #[AssertType('array<App\DTO\ReserveProductInput>')]
    public array $products = [];

    #[Assert\Type('string')]
    public ?string $comment = null;

    public function __construct()
    {
    }
}
