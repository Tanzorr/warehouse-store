<?php

namespace App\Application\Query\GetReservations;

use App\Entity\ProductReservation;
use App\Repository\ProductReservationRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class GetReservationsHandler
{
    public function __construct(
        private ProductReservationRepository $reservations,
    ) {}

    /** @return array{items: ReservationItem[], total: int} */
    public function __invoke(GetReservationsQuery $query): array
    {
        $qb = $this->reservations->createQueryBuilder('r')
            ->leftJoin('r.items', 'i')
            ->addSelect('i')
            ->leftJoin('i.product', 'p')
            ->addSelect('p')
            ->orderBy('r.reservedAt', 'DESC');

        if ($query->status !== null) {
            $qb->andWhere('r.status = :status')
                ->setParameter('status', $query->status);
        }

        if ($query->productId !== null) {
            $qb->andWhere('i.product = :productId')
                ->setParameter('productId', $query->productId);
        }

        $total = count($qb->getQuery()->getResult());

        $reservations = $qb
            ->setFirstResult(($query->page - 1) * $query->perPage)
            ->setMaxResults($query->perPage)
            ->getQuery()
            ->getResult();

        $items = array_map(function (ProductReservation $r): ReservationItem {
            return new ReservationItem(
                id: $r->getId(),
                status: $r->getStatus(),
                comment: $r->getComment() ?? '',
                reservedAt: $r->getReservedAt()->format('Y-m-d H:i'),
                releasedAt: $r->getReleasedAt()?->format('Y-m-d H:i'),
                items: array_map(
                    fn ($item) => [
                        'productId' => $item->getProduct()->getId(),
                        'name' => $item->getProduct()->getName(),
                        'sku' => $item->getProduct()->getSku(),
                        'amount' => $item->getAmount(),
                    ],
                    $r->getItems()->toArray()
                ),
                totalItems: $r->getItems()->count(),
            );
        }, $reservations);

        return ['items' => $items, 'total' => $total];
    }
}
