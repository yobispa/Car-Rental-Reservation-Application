<?php

namespace App\Tests\Entity;

use App\Entity\Reservation;
use PHPUnit\Framework\TestCase;

class ReservationTest extends TestCase
{
    public function testItCountsRentalDays(): void
    {
        $reservation = (new Reservation())
            ->setStartDate(new \DateTimeImmutable('2026-05-01'))
            ->setEndDate(new \DateTimeImmutable('2026-05-06'));

        self::assertSame(5, $reservation->getRentalDays());
    }

    public function testItReturnsNullWhenTheEndDateIsNotAfterTheStartDate(): void
    {
        $reservation = (new Reservation())
            ->setStartDate(new \DateTimeImmutable('2026-05-06'))
            ->setEndDate(new \DateTimeImmutable('2026-05-01'));

        self::assertNull($reservation->getRentalDays());
    }
}
