<?php

namespace App\Repository;

use App\Entity\ProductReservation;
use App\Entity\Warehouse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Constraints\Date;

/**
 * @extends ServiceEntityRepository<ProductReservation>
 */
class ProductReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductReservation::class);
    }

    public function create(  ?string $comment,?\DateTimeImmutable $expiredAt): ProductReservation
    {
        return  (new ProductReservation())
            ->setReservedAt(new \DateTimeImmutable())
            ->setStatus(ProductReservation::STATUS_PENDING)
            ->setComment($comment);
    }

    public function save(ProductReservation $reservation): void
    {
        $this->persistAndFlush($reservation);
    }


    public function remove(ProductReservation $reservation): void
    {
        $this->getEntityManager()->remove($reservation);
        $this->getEntityManager()->flush();
    }

    public function  findExpiredPendingReservation(\DateTimeInterface $now): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.status = :status')
            ->andWhere('r.expired_at < :now')
            ->setParameter('status', ProductReservation::STATUS_PENDING)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }

    private function persistAndFlush(ProductReservation $reservation): void
    {
        $this->getEntityManager()->persist($reservation);
        $this->getEntityManager()->flush();
    }

    //    /**
    //     * @return ProductReservation[] Returns an array of ProductReservation objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?ProductReservation
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
