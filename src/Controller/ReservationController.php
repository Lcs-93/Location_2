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

    /**
     * Affiche la liste des réservations
     * - Si ROLE_ADMIN : affiche toutes
     * - Sinon : affiche celles de l'utilisateur
     */
    #[Route('/reservations', name: 'app_reservation_index')]
    public function index(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException("Vous devez être connecté pour voir vos réservations.");
        }

        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            // Admin → voit toutes les réservations
            $reservations = $this->reservationRepository->findAll();
        } else {
            // Utilisateur normal → voit seulement ses propres réservations
            $reservations = $this->reservationRepository->findBy(['user' => $user]);
        }

        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservations,
        ]);
    }

    /**
     * Crée une nouvelle réservation pour un véhicule donné
     */
    #[Route('/reservation/new/{id}', name: 'app_reservation_new', requirements: ['id' => '\d+'])]
    public function new(int $id, Request $request): Response
    {
        $user = $this->getUser();
        // Vérification du rôle USER
        if (!$user || !in_array('ROLE_USER', $user->getRoles())) {
            throw new AccessDeniedException("Vous devez être connecté en tant que client pour faire une réservation.");
        }

        // Récupérer le véhicule
        $vehicle = $this->vehicleRepository->find($id);
        if (!$vehicle) {
            throw $this->createNotFoundException("Véhicule non trouvé.");
        }

        $reservation = new Reservation();
        $reservation->setUser($user);
        $reservation->setVehicle($vehicle);

        // Formulaire
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Calcul du prix total
            $days = $reservation->getEndDate()->diff($reservation->getStartDate())->days;
            $totalPrice = $vehicle->getDailyPrice() * $days;
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

    /**
     * Éditer une réservation
     * - Admin ou propriétaire
     */
    #[Route('/reservation/edit/{id}', name: 'app_reservation_edit')]
    public function edit(Request $request, Reservation $reservation): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedException("Vous devez être connecté.");
        }

        // Condition : admin ou propriétaire
        if ($reservation->getUser() !== $user && !in_array('ROLE_ADMIN', $user->getRoles())) {
            throw new AccessDeniedException("Vous ne pouvez pas modifier cette réservation.");
        }

        if (!in_array('ROLE_ADMIN', $user->getRoles()) && $reservation->getStartDate() <= new \DateTime()) {
            $this->addFlash('error', 'La réservation a déjà commencé, vous ne pouvez plus la modifier.');
            return $this->redirectToRoute('app_reservation_index');
        }

        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Recalcul du prix si nécessaire
            $days = $reservation->getEndDate()->diff($reservation->getStartDate())->days;
            $totalPrice = $reservation->getVehicle()->getDailyPrice() * $days;
            $reservation->setTotalPrice($totalPrice);

            $this->entityManager->flush();

            $this->addFlash('success', 'Votre réservation a été modifiée avec succès.');
            return $this->redirectToRoute('app_reservation_index');
        }

        return $this->render('reservation/edit.html.twig', [
            'form' => $form->createView(),
            'reservation' => $reservation,
        ]);
    }

    /**
     * Supprimer une réservation
     * - Admin ou propriétaire
     */
    #[Route('/reservation/delete/{id}', name: 'app_reservation_delete')]
    public function delete(Reservation $reservation): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedException("Vous devez être connecté.");
        }

        // Condition : admin ou propriétaire
        if ($reservation->getUser() !== $user && !in_array('ROLE_ADMIN', $user->getRoles())) {
            throw new AccessDeniedException("Vous ne pouvez pas annuler cette réservation.");
        }

        if (!in_array('ROLE_ADMIN', $user->getRoles()) && $reservation->getStartDate() <= new \DateTime()) {
            $this->addFlash('error', 'La réservation a déjà commencé, vous ne pouvez plus l\'annuler.');
            return $this->redirectToRoute('app_reservation_index');
        }
        $this->entityManager->remove($reservation);
        $this->entityManager->flush();

        $this->addFlash('success', 'Votre réservation a été annulée.');
        return $this->redirectToRoute('app_reservation_index');
    }

    /**
     * Affiche le détail d'une réservation
     */
    #[Route('/reservation/{id}', name: 'app_reservation_show', requirements: ['id' => '\d+'])]
    public function show(Reservation $reservation): Response
    {
        return $this->render('reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }
}
