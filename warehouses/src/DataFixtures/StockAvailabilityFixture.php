<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Entity\StockAvailability;
use App\Entity\Warehouse;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class StockAvailabilityFixture extends Fixture implements DependentFixtureInterface
{


    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        $warehouses = $manager->getRepository(Warehouse::class)->findAll();
        $products = $manager->getRepository(Product::class)->findAll();

        if (empty($products) || empty($warehouses)) {
            return;
        }

        foreach ($products as $product) {
            $usedWarehouses = $faker->randomElements($warehouses, $faker->numberBetween(1, min(3, count($warehouses))));

            foreach ($usedWarehouses as $warehouse) {
                $stock = new StockAvailability();
                $stock->setProduct($product);
                $stock->setWarehouse($warehouse);
                $stock->setAmount($faker->numberBetween(5, 200));

                $manager->persist($stock);
            }
        }

        $manager->flush();
    }


    public function getDependencies(): array
    {
        return [
            ProductFixture::class,
        ];
    }
}
