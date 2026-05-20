<?php

namespace App\Controller;

use App\Entity\Car;
use App\Entity\Customer;
use App\Entity\Reservation;
use App\Entity\User;
use App\Form\ReservationType;
use App\Repository\CarRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ReservationController extends AbstractController
{
    #[Route('/reserve', name: 'app_reservation_auto', methods: ['GET'])]
    public function smartMatch(
        Request $request,
        CarRepository $carRepository,
        ReservationRepository $reservationRepository
    ): Response {
        $availableOptions = [];
        $alternativeDates = [];
        $search = [
            'numberOfPersons' => $request->query->get('numberOfPersons', ''),
            'startDate' => $request->query->get('startDate', ''),
            'endDate' => $request->query->get('endDate', ''),
        ];

        if ($search['numberOfPersons'] && $search['startDate'] && $search['endDate']) {
            $reservation = (new Reservation())
                ->setNumberOfPersons((int) $search['numberOfPersons'])
                ->setStartDate(new \DateTimeImmutable($search['startDate']))
                ->setEndDate(new \DateTimeImmutable($search['endDate']));

            $cars = $carRepository->findAll();
            $reservations = $reservationRepository->findAll();

            $availableOptions = $this->findAvailableCars($cars, $reservations, $reservation);
            if (empty($availableOptions)) {
                $alternativeDates = $this->findAlternativeDates($cars, $reservations, $reservation);
            }
        }

        return $this->render('reservation/smart_match.html.twig', [
            'availableOptions' => $availableOptions,
            'alternativeDates' => $alternativeDates,
            'search' => $search,
        ]);
    }

    #[Route('/cars/{id}/reserve', name: 'app_reservation_new', methods: ['GET', 'POST'])]
    public function new(
        Car $car,
        Request $request,
        EntityManagerInterface $entityManager,
        ReservationRepository $reservationRepository
    ): Response
    {
        $reservation = (new Reservation())
            ->setCar($car);

        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reservation = $form->getData();
            $reservation->setCar($car);

            if (!$this->isCarAvailable($car, $reservationRepository->findAll(), $reservation)) {
                $this->addFlash('warning', 'This car is already reserved for those dates. Please choose another date or use Smart Match.');

                return $this->render('reservation/new.html.twig', [
                    'car' => $car,
                    'reservationForm' => $form,
                ]);
            }

            $customer = (new Customer())
                ->setFirstName($form->get('customerFirstName')->getData())
                ->setLastName($form->get('customerLastName')->getData())
                ->setEmail($form->get('customerEmail')->getData())
                ->setPhone($form->get('customerPhone')->getData());

            $reservation->setCustomer($customer);

            $user = $this->getUser();
            if ($user instanceof User) {
                $reservation->setUser($user);
            }

            $entityManager->persist($customer);
            $entityManager->persist($reservation);
            $entityManager->flush();

            $this->addFlash('success', 'Your reservation request has been saved.');

            return $this->redirectToRoute('app_quest');
        }

        return $this->render('reservation/new.html.twig', [
            'car' => $car,
            'reservationForm' => $form,
        ]);
    }
    // AI helped with these private methods and algorithms.
    private function findAvailableCars(array $cars, array $reservations, Reservation $reservation): array
    {
        $availableCars = [];
        $availableCarsWithPrices = [];

        foreach ($cars as $car) {
            if ($car->getSeats() < $reservation->getNumberOfPersons()) {
                continue;
            }

            if (!$this->isCarAvailable($car, $reservations, $reservation)) {
                continue;
            }

            $availableCars[] = $car;
        }

        $demandCount = $this->countDemand($reservations, $reservation);
        $availableCarsCount = count($availableCars);

        foreach ($availableCars as $car) {
            $availableCarsWithPrices[] = [
                'car' => $car,
                'basePrice' => $this->calculateBasePrice($car, $reservation),
                'totalPrice' => $this->calculatePrice($car, $reservation, $demandCount, $availableCarsCount),
                'priceIncreaseText' => $this->getPriceIncreaseText($reservation, $demandCount, $availableCarsCount),
            ];
        }

        usort($availableCarsWithPrices, fn (array $first, array $second): int => $first['totalPrice'] <=> $second['totalPrice']);

        return $availableCarsWithPrices;
    }

    private function isCarAvailable(Car $car, array $reservations, Reservation $requestedReservation): bool
    {
        foreach ($reservations as $reservation) {
            if ($reservation->getCar()?->getId() !== $car->getId()) {
                continue;
            }

            if (in_array($reservation->getStatus(), [Reservation::STATUS_CANCELLED, Reservation::STATUS_COMPLETED], true)) {
                continue;
            }

            if (
                $reservation->getStartDate() < $requestedReservation->getEndDate()
                && $reservation->getEndDate() > $requestedReservation->getStartDate()
            ) {
                return false;
            }
        }

        return true;
    }

    private function calculatePrice(Car $car, Reservation $reservation, int $demandCount, int $availableCarsCount): float
    {
        $month = (int) $reservation->getStartDate()->format('n');
        $price = $this->calculateBasePrice($car, $reservation);

        if (in_array($month, [6, 7, 8], true)) {
            $price *= 1.20;
        }

        if ($month === 12) {
            $price *= 1.15;
        }

        if ($demandCount >= 3) {
            $price *= 1.10;
        }

        if ($availableCarsCount <= 2) {
            $price *= 1.10;
        }

        return round($price, 2);
    }

    private function calculateBasePrice(Car $car, Reservation $reservation): float
    {
        $rentalDays = $reservation->getRentalDays() ?? 1;

        return (float) $car->getDailyBasePrice() * $rentalDays;
    }

    private function getPriceIncreaseText(Reservation $reservation, int $demandCount, int $availableCarsCount): string
    {
        $month = (int) $reservation->getStartDate()->format('n');
        $priceIncreases = [];

        if (in_array($month, [6, 7, 8], true)) {
            $priceIncreases[] = 'Summer +20%';
        }

        if ($month === 12) {
            $priceIncreases[] = 'December +15%';
        }

        if ($demandCount >= 3) {
            $priceIncreases[] = 'High demand +10%';
        }

        if ($availableCarsCount <= 2) {
            $priceIncreases[] = 'Low availability +10%';
        }

        if (empty($priceIncreases)) {
            return 'No price increase';
        }

        return implode(', ', $priceIncreases);
    }

    private function countDemand(array $reservations, Reservation $requestedReservation): int
    {
        $demandCount = 0;

        foreach ($reservations as $reservation) {
            if (in_array($reservation->getStatus(), [Reservation::STATUS_CANCELLED, Reservation::STATUS_COMPLETED], true)) {
                continue;
            }

            if (
                $reservation->getStartDate() < $requestedReservation->getEndDate()
                && $reservation->getEndDate() > $requestedReservation->getStartDate()
            ) {
                $demandCount++;
            }
        }

        return $demandCount;
    }

    private function findAlternativeDates(array $cars, array $reservations, Reservation $reservation): array
    {
        $alternatives = [];
        $rentalDays = $reservation->getRentalDays() ?? Reservation::MIN_RENTAL_DAYS;

        foreach ([1, 3, 7] as $daysLater) {
            $alternativeReservation = (new Reservation())
                ->setNumberOfPersons($reservation->getNumberOfPersons())
                ->setStartDate($reservation->getStartDate()->modify(sprintf('+%d days', $daysLater)))
                ->setEndDate($reservation->getStartDate()->modify(sprintf('+%d days', $daysLater + $rentalDays)));

            $availableCars = $this->findAvailableCars($cars, $reservations, $alternativeReservation);

            if (!empty($availableCars)) {
                $alternatives[] = [
                    'startDate' => $alternativeReservation->getStartDate(),
                    'endDate' => $alternativeReservation->getEndDate(),
                    'car' => $availableCars[0]['car'],
                    'totalPrice' => $availableCars[0]['totalPrice'],
                    'priceIncreaseText' => $availableCars[0]['priceIncreaseText'],
                ];
            }
        }

        return $alternatives;
    }
}
