<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Vehicle;
use App\Form\ReservationType;
use App\Repository\ReservationRepository;
use App\Repository\VehicleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ReservationController extends AbstractController
{
    private $vehicleRepository;
    private $reservationRepository;
    private $entityManager;

    public function __construct(
        VehicleRepository $vehicleRepository,
        ReservationRepository $reservationRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->vehicleRepository = $vehicleRepository;
        $this->reservationRepository = $reservationRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/reservations', name: 'app_reservation_index')]
    public function index(): Response
    {
        $reservations = $this->reservationRepository->findAll();

        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservations,
        ]);
    }

    #[Route('/reservation/new/{id}', name: 'app_reservation_new')]
    public function new(Request $request, Vehicle $vehicle): Response
    {
        // Vérifier que l'utilisateur est connecté et qu'il est un client
        if (!$this->getUser() || !in_array('ROLE_USER', $this->getUser()->getRoles())) {
            throw new AccessDeniedException("Vous devez être connecté en tant que client pour faire une réservation.");
        }

        // Créer une nouvelle réservation
        $reservation = new Reservation();
        $reservation->setUser($this->getUser());
        $reservation->setVehicle($vehicle);

        // Créer et gérer le formulaire
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Calcul du prix total
            $vehiclePricePerDay = $vehicle->getDailyPrice();
            $startDate = $reservation->getStartDate();
            $endDate = $reservation->getEndDate();

            $days = $endDate->diff($startDate)->days;
            $totalPrice = $vehiclePricePerDay * $days;

            $reservation->setTotalPrice($totalPrice);
            $this->entityManager->persist($reservation);
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre réservation a été créée avec succès.');

            return $this->redirectToRoute('app_reservation_index');
        }

        return $this->render('reservation/new.html.twig', [
            'form' => $form->createView(),
            'vehicle' => $vehicle,
        ]);
    }

    #[Route('/reservation/edit/{id}', name: 'app_reservation_edit')]
    public function edit(Request $request, Reservation $reservation): Response
    {
        // Vérifier que l'utilisateur est connecté et est le propriétaire de la réservation
        if (!$this->getUser() || $reservation->getUser() !== $this->getUser()) {
            throw new AccessDeniedException("Vous ne pouvez pas modifier cette réservation.");
        }

        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre réservation a été modifiée avec succès.');

            return $this->redirectToRoute('app_reservation_index');
        }

        return $this->render('reservation/edit.html.twig', [
            'form' => $form->createView(),
            'reservation' => $reservation,
        ]);
    }

    #[Route('/reservation/delete/{id}', name: 'app_reservation_delete')]
    public function delete(Reservation $reservation): Response
    {
        // Vérifier que l'utilisateur est connecté et est le propriétaire de la réservation
        if (!$this->getUser() || $reservation->getUser() !== $this->getUser()) {
            throw new AccessDeniedException("Vous ne pouvez pas annuler cette réservation.");
        }

        $this->entityManager->remove($reservation);
        $this->entityManager->flush();

        $this->addFlash('success', 'Votre réservation a été annulée.');

        return $this->redirectToRoute('app_reservation_index');
    }

    /**
     * @Route("/reservation/{id}", name="app_reservation_show")
     */
    public function show(Reservation $reservation): Response
    {
        return $this->render('reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }
}
