<?php

namespace App\Controller;

use App\Entity\Car;
use App\Entity\Customer;
use App\Entity\Reservation;
use App\Entity\User;
use App\Form\ReservationType;
use App\Repository\CarRepository;
use App\Repository\ReservationRepository;
use App\Service\SentooPaymentService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
        ReservationRepository $reservationRepository,
        CarRepository $carRepository,
        SentooPaymentService $sentooPaymentService
    ): Response
    {
        $reservation = (new Reservation())
            ->setCar($car);

        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reservation = $form->getData();
            $reservation->setCar($car);
            $reservations = $reservationRepository->findAll();
            $customerEmail = $form->get('customerEmail')->getData();

            if (!$this->isCarAvailable($car, $reservations, $reservation)) {
                return $this->render('reservation/new.html.twig', [
                    'car' => $car,
                    'reservationForm' => $form,
                    'availabilityError' => 'This car is already reserved for those dates. Please choose another date or use Smart Match.',
                ]);
            }

            if (!$this->canCustomerRentAgain($reservations, $customerEmail, $reservation)) {
                return $this->render('reservation/new.html.twig', [
                    'car' => $car,
                    'reservationForm' => $form,
                    'availabilityError' => 'You can only rent a car once every 5 days.',
                ]);
            }

            $availableOptions = $this->findAvailableCars($carRepository->findAll(), $reservations, $reservation);
            $totalPrice = $this->findTotalPriceForCar($car, $availableOptions);

            $customer = (new Customer())
                ->setFirstName($form->get('customerFirstName')->getData())
                ->setLastName($form->get('customerLastName')->getData())
                ->setEmail($form->get('customerEmail')->getData())
                ->setPhone($form->get('customerPhone')->getData());

            $reservation->setCustomer($customer);
            $reservation->setTotalPrice(number_format($totalPrice, 2, '.', ''));

            $user = $this->getUser();
            if ($user instanceof User) {
                $reservation->setUser($user);
            }

            $entityManager->persist($customer);
            $entityManager->persist($reservation);
            $entityManager->flush();

            try {
                $returnUrl = $this->getPaymentReturnUrl($reservation, $sentooPaymentService);

                $payment = $sentooPaymentService->createTransaction($reservation, $totalPrice, $returnUrl);

                $reservation
                    ->setStatus(Reservation::STATUS_PENDING)
                    ->setPaymentStatus(Reservation::PAYMENT_ISSUED)
                    ->setSentooTransactionId($payment['transactionId'])
                    ->setSentooPaymentUrl($payment['paymentUrl'])
                    ->setSentooQrCodeUrl($payment['qrCodeUrl'])
                    ->setPaymentMessage(null);

                $entityManager->flush();

                return $this->redirect($payment['paymentUrl']);
            } catch (\Throwable $exception) {
                $reservation
                    ->setStatus(Reservation::STATUS_CANCELLED)
                    ->setPaymentStatus(Reservation::PAYMENT_FAILED)
                    ->setPaymentMessage($exception->getMessage());

                $entityManager->flush();

                $this->addFlash('danger', 'The reservation was saved, but the payment could not be started.');

                return $this->redirectToRoute('app_payment_show', [
                    'id' => $reservation->getId(),
                ]);
            }
        }

        return $this->render('reservation/new.html.twig', [
            'car' => $car,
            'reservationForm' => $form,
            'availabilityError' => null,
        ]);
    }
     //AI helped me with webhooks and payment integration because I dont know alot of payment integrations
    #[Route('/reservations/{id}/payment', name: 'app_payment_show', methods: ['GET'])]
    public function paymentShow(Reservation $reservation): Response
    {
        return $this->render('payment/show.html.twig', [
            'reservation' => $reservation,
            'attempt' => null,
        ]);
    }

    #[Route('/payment/return/{id}', name: 'app_payment_return', methods: ['GET'])]
    public function paymentReturn(
        Reservation $reservation,
        Request $request,
        EntityManagerInterface $entityManager,
        SentooPaymentService $sentooPaymentService,
        LoggerInterface $logger
    ): Response
    {
        if ($reservation->getSentooTransactionId()) {
            try {
                $paymentStatus = $sentooPaymentService->fetchTransactionStatus($reservation->getSentooTransactionId());
                $this->updatePaymentStatus($reservation, $paymentStatus['status'], $paymentStatus['message']);
                $entityManager->flush();
            } catch (\Throwable $exception) {
                $logger->error('Could not check Sentoo payment status after return.', [
                    'reservationId' => $reservation->getId(),
                    'error' => $exception->getMessage(),
                ]);

                $this->addFlash('danger', 'Could not check the payment status right now.');
            }
        }

        return $this->render('payment/show.html.twig', [
            'reservation' => $reservation,
            'attempt' => $request->query->get('attempt'),
        ]);
    }

    #[Route('/sentoo/webhook', name: 'app_sentoo_webhook', methods: ['POST'])]
    public function sentooWebhook(
        Request $request,
        ReservationRepository $reservationRepository,
        EntityManagerInterface $entityManager,
        SentooPaymentService $sentooPaymentService,
        LoggerInterface $logger
    ): Response
    {
        $transactionId = $request->request->get('transaction_id');

        if (!$transactionId) {
            $logger->warning('Sentoo webhook received without transaction_id.');

            return new Response('success');
        }

        $reservation = $reservationRepository->findOneBy([
            'sentooTransactionId' => $transactionId,
        ]);

        if (!$reservation) {
            $logger->warning('Sentoo webhook received for an unknown transaction.', [
                'transactionId' => $transactionId,
            ]);

            return new Response('success');
        }

        try {
            $paymentStatus = $sentooPaymentService->fetchTransactionStatus($transactionId);
            $this->updatePaymentStatus($reservation, $paymentStatus['status'], $paymentStatus['message']);
            $entityManager->flush();

            $logger->info('Sentoo webhook updated a reservation payment status.', [
                'reservationId' => $reservation->getId(),
                'transactionId' => $transactionId,
                'paymentStatus' => $paymentStatus['status'],
            ]);

            return new Response('success');
        } catch (\Throwable $exception) {
            $logger->error('Sentoo webhook could not update payment status.', [
                'reservationId' => $reservation->getId(),
                'transactionId' => $transactionId,
                'error' => $exception->getMessage(),
            ]);

            return new Response('Could not check payment status.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/reservations/{id}/payment/retry', name: 'app_payment_retry', methods: ['POST'])]
    public function paymentRetry(
        Reservation $reservation,
        Request $request,
        EntityManagerInterface $entityManager,
        SentooPaymentService $sentooPaymentService
    ): Response
    {
        if (!$this->isCsrfTokenValid('retry_payment_' . $reservation->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        if (!in_array($reservation->getPaymentStatus(), [
            Reservation::PAYMENT_REJECTED,
            Reservation::PAYMENT_CANCELLED,
            Reservation::PAYMENT_FAILED,
            Reservation::PAYMENT_EXPIRED,
        ], true)) {
            $this->addFlash('warning', 'This payment cannot be restarted.');

            return $this->redirectToRoute('app_payment_show', [
                'id' => $reservation->getId(),
            ]);
        }

        try {
            $returnUrl = $this->getPaymentReturnUrl($reservation, $sentooPaymentService);

            $payment = $sentooPaymentService->createTransaction($reservation, (float) $reservation->getTotalPrice(), $returnUrl);

            $reservation
                ->setStatus(Reservation::STATUS_PENDING)
                ->setPaymentStatus(Reservation::PAYMENT_ISSUED)
                ->setSentooTransactionId($payment['transactionId'])
                ->setSentooPaymentUrl($payment['paymentUrl'])
                ->setSentooQrCodeUrl($payment['qrCodeUrl'])
                ->setPaymentMessage(null);

            $entityManager->flush();

            return $this->redirect($payment['paymentUrl']);
        } catch (\Throwable $exception) {
            $reservation
                ->setStatus(Reservation::STATUS_CANCELLED)
                ->setPaymentStatus(Reservation::PAYMENT_FAILED)
                ->setPaymentMessage($exception->getMessage());

            $entityManager->flush();

            $this->addFlash('danger', 'The payment could not be restarted.');

            return $this->redirectToRoute('app_payment_show', [
                'id' => $reservation->getId(),
            ]);
        }
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
    
    private function getPaymentReturnUrl(Reservation $reservation, SentooPaymentService $sentooPaymentService): string
    {
        $defaultReturnUrl = $this->generateUrl('app_payment_return', [
            'id' => $reservation->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL) . '?attempt=';

        return $sentooPaymentService->getReturnUrl($reservation, $defaultReturnUrl);
    }

    private function findTotalPriceForCar(Car $car, array $availableOptions): float
    {
        foreach ($availableOptions as $option) {
            if ($option['car']->getId() === $car->getId()) {
                return $option['totalPrice'];
            }
        }

        return 0;
    }

    private function updatePaymentStatus(Reservation $reservation, string $sentooStatus, ?string $message): void
    {
        $reservation
            ->setPaymentStatus($sentooStatus)
            ->setPaymentMessage($message);

        if ($sentooStatus === Reservation::PAYMENT_SUCCESS) {
            $reservation->setStatus(Reservation::STATUS_CONFIRMED);
        }

        if (in_array($sentooStatus, [
            Reservation::PAYMENT_REJECTED,
            Reservation::PAYMENT_CANCELLED,
            Reservation::PAYMENT_FAILED,
            Reservation::PAYMENT_EXPIRED,
        ], true)) {
            $reservation->setStatus(Reservation::STATUS_CANCELLED);
        }
    }

    private function isCarAvailable(Car $car, array $reservations, Reservation $requestedReservation): bool
    {
        foreach ($reservations as $reservation) {
            if ($reservation->getCar()?->getId() !== $car->getId()) {
                continue;
            }

            if (!$this->doesReservationBlockCar($reservation)) {
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

    private function canCustomerRentAgain(array $reservations, string $email, Reservation $newReservation): bool
    {
        foreach ($reservations as $reservation) {
            if ($reservation->getCustomer()?->getEmail() !== $email) {
                continue;
            }

            if (!$this->doesReservationBlockCar($reservation)) {
                continue;
            }

            $nextAllowedDate = $reservation->getEndDate()->modify('+5 days');

            if ($newReservation->getStartDate() < $nextAllowedDate) {
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
            if (!$this->doesReservationBlockCar($reservation)) {
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

    private function doesReservationBlockCar(Reservation $reservation): bool
    {
        if (in_array($reservation->getStatus(), [Reservation::STATUS_CANCELLED, Reservation::STATUS_COMPLETED], true)) {
            return false;
        }

        if (in_array($reservation->getPaymentStatus(), [
            Reservation::PAYMENT_NOT_STARTED,
            Reservation::PAYMENT_FAILED,
            Reservation::PAYMENT_REJECTED,
            Reservation::PAYMENT_CANCELLED,
            Reservation::PAYMENT_EXPIRED,
        ], true)) {
            return false;
        }

        return true;
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
