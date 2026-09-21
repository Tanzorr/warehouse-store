<?php

namespace App\Tests\Api;

// The 'use' statement has been updated to the correct namespace for API Platform 3.x
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\ProductReservation;
use App\Entity\Warehouse;
use App\Entity\Stock; // Assuming you have a Stock entity to manage inventory.
use App\Entity\StockAvailability;
use Doctrine\ORM\EntityManagerInterface;
use Hautelook\AliceBundle\PhpUnit\RefreshDatabaseTrait;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class ProductReservationApiTest
 *
 * This class contains API tests for the ProductReservation resource.
 * It covers successful creation, various failure scenarios like insufficient stock,
 * and status transitions (canceling and committing reservations).
 *
 * @package App\Tests\Api
 */
class ProductReservationApiTest extends ApiTestCase
{
    // This trait resets the database before each test to ensure a clean state.
    use RefreshDatabaseTrait;

    private ?EntityManagerInterface $entityManager;

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Get the entity manager to interact with the database directly for setup.
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
    }

    /**
     * Test case for successfully creating a product reservation when stock is available.
     *
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function testCreateProductReservationSuccessfully(): void
    {
        // 1. Setup: Create necessary entities (Category, Product, Warehouse) and set initial stock.
        $product = $this->createProduct('Test Product 1', 'SKU001', '100.00');
        $warehouse = $this->createWarehouse('Main Warehouse');
        $this->deleteAllReservations();
        $this->setStock($product, $warehouse, 20);

        $initialStock = $this->getStockAmount($product, $warehouse);

        // $response = $this->entityManager->getRepository(Product::class)->findAll();
        // dd($response);

        // 2. Action: Send a POST request to create a reservation.
        $response = static::createClient()->request('POST', '/api/product_reservations', [
            'json' => [
                'comment' => 'Reservation for upcoming event',
                'items' => [
                    [
                        'product' => '/api/products/' . $product->getId(),
                        'amount' => 5,
                    ],
                ],
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
                'Accept' => 'application/ld+json',
            ]
        ]);

        // 3. Assertions: Verify the outcome.
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201, 'A new reservation should be created.');
        $this->assertJsonContains([
            '@context' => '/api/contexts/ProductReservation',
            '@type' => 'ProductReservation',
            'status' => 'pending', // Default status should be 'pending'.
            'comment' => 'Reservation for upcoming event',
        ]);

        // Verify that the item was created correctly within the reservation.
        $this->assertCount(1, $response->toArray()['items']);
        $this->assertSame('/api/products/' . $product->getId(), $response->toArray()['items'][0]['product']['@id']);
        $this->assertSame(5, $response->toArray()['items'][0]['amount']);
        
        // Verify that the stock in the database was correctly updated.
        $finalStock = $this->getStockAmount($product, $warehouse);
        $this->assertEquals($initialStock - 5, $finalStock, "Stock should be reduced by the reserved amount.");
    }

    /**
     * Test case to ensure a reservation fails when there is not enough product in stock.
     *
     * @throws TransportExceptionInterface
     */
    public function testCreateReservationFailsWithInsufficientStock(): void
    {
        // 1. Setup: Create a product with a low stock level.
        $product = $this->createProduct('Limited Product', 'SKU002', '50.00');
        $warehouse = $this->createWarehouse('Secondary Warehouse');
        $this->setStock($product, $warehouse, 3); // Only 3 items in stock.

        $initialStock = $this->getStockAmount($product, $warehouse);

        // 2. Action: Attempt to reserve more items than are available.
        static::createClient()->request('POST', '/api/product_reservations', [
            'json' => [
                'items' => [
                    [
                        'product' => '/api/products/' . $product->getId(),
                        'amount' => 10, // Requesting 10
                    ],
                ],
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
                'Accept' => 'application/ld+json',
            ]
        ]);

        // 3. Assertions: Verify the correct error response.
        $this->assertResponseStatusCodeSame(422, 'Should return Unprocessable Entity for business logic failure.');
        $this->assertJsonContains([
            '@type' => 'ConstraintViolation',
            // This assumes your application logic adds a violation with this specific message.
            'description' => 'items[0].amount: Insufficient stock or invalid quantity specified (10 / available: 3).' ,
        ]);
        
        // Verify that the stock level remains unchanged after the failed attempt.
        $finalStock = $this->getStockAmount($product, $warehouse);
        $this->assertEquals($initialStock, $finalStock, "Stock should not change on a failed reservation.");
    }

    /**
     * Test case to ensure a reservation fails if a non-existent product IRI is provided.
     *
     * @throws TransportExceptionInterface
     */
    public function testCreateReservationFailsWithInvalidProduct(): void
    {
        // 1. Action: Attempt to create a reservation with a product IRI that doesn't exist.
        static::createClient()->request('POST', '/api/product_reservations', [
            'json' => [
                'items' => [
                    [
                        'product' => '/api/products/99999', // This ID should not exist.
                        'amount' => 1,
                    ],
                ],
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
                'Accept' => 'application/ld+json',
            ]
        ]);

        // 2. Assertions: Verify the API returns a 400 Bad Request.
        $this->assertResponseStatusCodeSame(400, 'Should return Bad Request for an invalid IRI.');
        $this->assertJsonContains([
            '@type' => 'Error',
            'description' => 'Item not found for "/api/products/99999".', // API Platform's default message for invalid IRIs.
        ]);
    }
    
    /**
     * Test case for canceling a product reservation, which should return the stock.
     *
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testCancelProductReservation(): void
    {
        // 1. Setup: Create a product and a successful reservation.
        $product = $this->createProduct('Cancellable Product', 'SKU003', '25.00');
        $warehouse = $this->createWarehouse('Main Warehouse');
        $this->deleteAllReservations();
        $this->setStock($product, $warehouse, 15);

        $initialStock = $this->getStockAmount($product, $warehouse);

        $response = static::createClient()->request('POST', '/api/product_reservations', [
            'json' => ['items' => [['product' => '/api/products/' . $product->getId(), 'amount' => 10]]],
            'headers' => ['Content-Type' => 'application/ld+json']
        ]);
        
        $reservationId = $response->toArray()['id'];
        $stockAfterReservation = $this->getStockAmount($product, $warehouse);
        $this->assertEquals($initialStock - 10, $stockAfterReservation, "Stock should be reduced after initial reservation.");

        // 2. Action: Send a PATCH request to change the status to "canceled".
        static::createClient()->request('PATCH', '/api/product_reservations/' . $reservationId, [
            'json' => [
                'status' => 'canceled',
            ],
            'headers' => [
                // Use the merge-patch+json content type for PATCH requests.
                'Content-Type' => 'application/merge-patch+json',
                'Accept' => 'application/ld+json',
            ],
        ]);

        // 3. Assertions: Verify the status update and stock restoration.
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['status' => 'canceled']);

        // Verify that the stock was returned.
        $finalStock = $this->getStockAmount($product, $warehouse);
        $this->assertEquals($initialStock, $finalStock, "Stock should be restored to its initial amount after cancellation.");
    }
    
    /**
     * Test case for committing a product reservation. Stock should remain deducted.
     *
     * @throws TransportExceptionInterface
     */
    public function testCommitProductReservation(): void
    {
        // 1. Setup: Create a product and a successful reservation.
        $product = $this->createProduct('Committable Product', 'SKU004', '300.00');
        $warehouse = $this->createWarehouse('Main Warehouse');
        $this->setStock($product, $warehouse, 50);

        $initialStock = $this->getStockAmount($product, $warehouse);

        $response = static::createClient()->request('POST', '/api/product_reservations', [
            'json' => ['items' => [['product' => '/api/products/' . $product->getId(), 'amount' => 20]]],
            'headers' => ['Content-Type' => 'application/ld+json']
        ]);
        
        $reservationId = $response->toArray()['id'];
        $stockAfterReservation = $this->getStockAmount($product, $warehouse);
        $this->assertEquals($initialStock - 20, $stockAfterReservation, "Stock should be reduced after initial reservation.");

        // 2. Action: Send a PATCH request to change the status to "committed".
        static::createClient()->request('PATCH', '/api/product_reservations/' . $reservationId, [
            'json' => [
                'status' => 'committed',
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
                'Accept' => 'application/ld+json',
            ],
        ]);

        // 3. Assertions: Verify the status update and that stock is NOT returned.
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['status' => 'committed']);

        // Verify that the stock remains deducted.
        $finalStock = $this->getStockAmount($product, $warehouse);
        $this->assertEquals($stockAfterReservation, $finalStock, "Stock should remain deducted after committing the reservation.");
    }

    // --- Helper Methods to create entities for tests ---

    /**
     * Helper to create and persist a Category entity.
     */
    private function createCategory(string $name): Category
    {
        $category = new Category();
        $category->setName($name);
        $this->entityManager->persist($category);
        $this->entityManager->flush();
        return $category;
    }

    /**
     * Helper to create and persist a Product entity.
     */
    private function createProduct(string $name, string $sku, string $price): Product
    {
        $category = $this->createCategory('Default Category');
        $product = new Product();
        $product->setName($name);
        $product->setSku($sku);
        $product->setPrice($price);
        $product->setCategory($category);
        $product->setDescription('A test product description.');
        $this->entityManager->persist($product);
        $this->entityManager->flush();
        return $product;
    }

    /**
     * Helper to create and persist a Warehouse entity.
     */
    private function createWarehouse(string $name): Warehouse
    {
        $warehouse = new Warehouse();
        $warehouse->setName($name);
        $warehouse->setDescription('A test warehouse.');
        $this->entityManager->persist($warehouse);
        $this->entityManager->flush();
        return $warehouse;
    }


    /**
     * Helper to create and persist a Warehouse entity.
     */
    private function deleteAllReservations()
    {
        $reservations = $this->entityManager->getRepository(ProductReservation::class)->findAll();
        foreach($reservations as $r){
            $this->entityManager->remove($r);
        }
        $this->entityManager->flush();
    }

    /**
     * Helper to set the stock for a given product in a warehouse.
     * This assumes a 'Stock' entity that links Product, Warehouse, and amount.
     */
    private function setStock(Product $product, Warehouse $warehouse, int $amount): void
    {
        $stock = $this->entityManager->getRepository(StockAvailability::class)->findOneBy([
            'product' => $product,
            'warehouse' => $warehouse,
        ]);

        if (!$stock) {
            $stock = new StockAvailability();
            $stock->setProduct($product);
            $stock->setWarehouse($warehouse);
        }
        
        $stock->setAmount($amount);
        $this->entityManager->persist($stock);
        $this->entityManager->flush();
    }
    
    /**
     * Helper to retrieve the current stock amount for a product in a warehouse.
     */
    private function getStockAmount(Product $product, Warehouse $warehouse): int
    {
        
        $stock = $this->entityManager->getRepository(StockAvailability::class)->findOneBy([
            'product' => $product,
            'warehouse' => $warehouse,
        ]);
        $reservations = $this->entityManager->getRepository(ProductReservation::class)->findAll();

        $amount = $stock->getAmount();
        foreach ($reservations as $r){
            foreach ($r->getItems() as $item){
                $amount -= $item->getAmount();
            }
        }

        return $amount;
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        // Close the entity manager to free up resources.
        if ($this->entityManager !== null) {
            $this->entityManager->close();
            $this->entityManager = null;
        }
    }
}
