<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Warehouse;


class WarehouseFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
       $warehouse = new Warehouse();

       for ($i = 0; $i < 10; $i++) {
            $warehouse = clone $warehouse;
            $warehouse->setName('Warehouse ' . ($i + 1));
            $warehouse->setDescription('Description for Warehouse ' . ($i + 1));
            $warehouse->setLocation('Location ' . ($i + 1));
            $manager->persist($warehouse);
       }

        $manager->flush();
    }
}
