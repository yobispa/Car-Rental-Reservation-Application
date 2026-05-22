<?php

namespace App\Tests\Controller;

use App\Controller\ReservationController;
use App\Entity\Car;
use App\Entity\Reservation;
use PHPUnit\Framework\TestCase;

class ReservationPriceTest extends TestCase
{
    public function testItCalculatesTheNormalBasePrice(): void
    {
        $controller = new ReservationController();
        $car = $this->createCar('100.00');
        $reservation = $this->createReservation('2026-05-01', '2026-05-04');

        self::assertSame(300.0, $controller->calculateBasePrice($car, $reservation));
        self::assertSame(300.0, $controller->calculatePrice($car, $reservation, 0, 5));
        self::assertSame('No price increase', $controller->getPriceIncreaseText($reservation, 0, 5));
    }

    public function testItAddsSeasonDemandAndAvailabilityIncreases(): void
    {
        $controller = new ReservationController();
        $car = $this->createCar('100.00');
        $reservation = $this->createReservation('2026-06-01', '2026-06-04');

        self::assertSame(435.6, $controller->calculatePrice($car, $reservation, 3, 2));
        self::assertSame(
            'Summer +20%, High demand +10%, Low availability +10%',
            $controller->getPriceIncreaseText($reservation, 3, 2)
        );
    }

    public function testItAddsDecemberIncrease(): void
    {
        $controller = new ReservationController();
        $car = $this->createCar('80.00');
        $reservation = $this->createReservation('2026-12-10', '2026-12-12');

        self::assertSame(184.0, $controller->calculatePrice($car, $reservation, 0, 5));
        self::assertSame('December +15%', $controller->getPriceIncreaseText($reservation, 0, 5));
    }

    private function createCar(string $dailyPrice): Car
    {
        return (new Car())
            ->setCode('TEST-001')
            ->setCategory('SEDAN')
            ->setMake('Test')
            ->setModel('Car')
            ->setModelYear(2026)
            ->setSeats(5)
            ->setDoors(4)
            ->setTransmission('AUTOMATIC')
            ->setFuelType('GASOLINE')
            ->setLuggageCapacity(3)
            ->setColor('Blue')
            ->setDailyBasePrice($dailyPrice)
            ->setStatus('AVAILABLE')
            ->setImageFilename('sedan_001.png');
    }

    private function createReservation(string $startDate, string $endDate): Reservation
    {
        return (new Reservation())
            ->setNumberOfPersons(5)
            ->setStartDate(new \DateTimeImmutable($startDate))
            ->setEndDate(new \DateTimeImmutable($endDate));
    }
}
