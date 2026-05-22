<?php

namespace App\Tests\Controller;

use App\Entity\Reservation;
use App\Repository\CarRepository;
use App\Repository\ReservationRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ReservationPagesTest extends WebTestCase
{
    public function testLandingPageLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Find the right car for your trip');
    }

    public function testSmartMatchShowsTheMostAffordableCar(): void
    {
        $client = static::createClient();
        $this->clearReservations();

        $client->request('GET', '/reserve?numberOfPersons=5&startDate=2026-05-01&endDate=2026-05-04');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h2', 'Available cars');
        self::assertStringContainsString('Hyundai Sonata', $client->getResponse()->getContent());
        self::assertStringContainsString('$165.00', $client->getResponse()->getContent());
        self::assertStringContainsString('No price increase', $client->getResponse()->getContent());
    }

    public function testSmartMatchShowsSummerPriceIncrease(): void
    {
        $client = static::createClient();
        $this->clearReservations();

        $client->request('GET', '/reserve?numberOfPersons=5&startDate=2026-06-01&endDate=2026-06-04');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Hyundai Sonata', $client->getResponse()->getContent());
        self::assertStringContainsString('$198.00', $client->getResponse()->getContent());
        self::assertStringContainsString('Summer +20%', $client->getResponse()->getContent());
    }

    public function testReservationFormSavesReservationAndStartsPayment(): void
    {
        $client = static::createClient();
        $this->clearReservations();

        $car = static::getContainer()->get(CarRepository::class)->findOneBy([
            'code' => 'CON-003',
        ]);

        self::assertNotNull($car);

        $client->request('GET', '/cars/' . $car->getId() . '/reserve');

        self::assertResponseIsSuccessful();

        $client->submitForm('Save reservation', [
            'reservation[customerFirstName]' => 'Test',
            'reservation[customerLastName]' => 'Customer',
            'reservation[customerEmail]' => 'payment-flow@example.com',
            'reservation[customerPhone]' => '5551234',
            'reservation[numberOfPersons]' => '2',
            'reservation[startDate]' => '2031-01-10',
            'reservation[endDate]' => '2031-01-12',
        ]);

        self::assertResponseRedirects('https://pay.test/checkout');

        $reservations = static::getContainer()->get(ReservationRepository::class)->findAll();
        $reservation = $reservations[0] ?? null;

        self::assertNotNull($reservation);
        self::assertStringStartsWith('test-transaction-', (string) $reservation->getSentooTransactionId());
        self::assertSame(Reservation::STATUS_PENDING, $reservation->getStatus());
        self::assertSame(Reservation::PAYMENT_ISSUED, $reservation->getPaymentStatus());
        self::assertSame('payment-flow@example.com', $reservation->getCustomer()?->getEmail());
    }

    private function clearReservations(): void
    {
        $connection = static::getContainer()->get(Connection::class);
        $connection->executeStatement('DELETE FROM reservations');
        $connection->executeStatement('DELETE FROM customers');
    }
}
