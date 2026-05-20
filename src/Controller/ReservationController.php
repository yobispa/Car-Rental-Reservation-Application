<?php

namespace App\Controller;

use App\Entity\Car;
use App\Entity\Customer;
use App\Entity\Reservation;
use App\Entity\User;
use App\Form\ReservationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ReservationController extends AbstractController
{
    #[Route('/cars/{id}/reserve', name: 'app_reservation_new', methods: ['GET', 'POST'])]
    public function new(Car $car, Request $request, EntityManagerInterface $entityManager): Response
    {
        $reservation = (new Reservation())
            ->setCar($car);

        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reservation = $form->getData();

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
}
