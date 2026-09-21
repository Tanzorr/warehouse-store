<?php

namespace App\Repository;

use App\Entity\StockAvailability;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StockAvailability>
 */
class StockAvailabilityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StockAvailability::class);
    }

    public function findByProductInStocks(int $productId): ?array
    {
        return $this->findBy(
            ['product' => $productId],
            ['amount' => 'ASC']
        );
    }

    public function findByProductAndWarehouse(int $productId, int $warehouseId): ?StockAvailability
    {
        return $this->findOneBy([
            'product' => $productId,
            'warehouse' => $warehouseId,
        ]);
    }

    public function save(StockAvailability $stockAvailability): void
    {
        $this->getEntityManager()->persist($stockAvailability);
        $this->getEntityManager()->flush();
    }

//    /**
//     * @return StockAvailability[] Returns an array of StockAvailability objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?StockAvailability
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
