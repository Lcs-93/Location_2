<?php

// src/Controller/VehicleController.php

namespace App\Controller;
use App\Repository\VehicleRepository;
use App\Entity\Vehicle;
use App\Form\VehicleType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Reservation;
class VehicleController extends AbstractController
{
    #[Route('/vehicles', name: 'vehicle_index')]
    public function index(Request $request, EntityManagerInterface $em, VehicleRepository $vehicleRepository): Response
    {
        // Récupérer les paramètres de filtre depuis la requête GET
        $brandFilter = $request->query->get('brand');       
        $maxPriceFilter = $request->query->get('maxPrice'); 
        $availFilter = $request->query->get('avail');       
    
        // Construire la requête de base pour récupérer les véhicules
        $qb = $vehicleRepository->createQueryBuilder('v');
    
        // Filtre par marque
        if (!empty($brandFilter)) {
            $qb->andWhere('v.brand LIKE :brand')
               ->setParameter('brand', '%' . $brandFilter . '%');
        }
    
        // Filtre par prix maximum
        if (!empty($maxPriceFilter)) {
            $qb->andWhere('v.dailyPrice <= :price')
               ->setParameter('price', $maxPriceFilter);
        }
    
        // Filtre par disponibilité
        if ($availFilter !== null && $availFilter !== '') {
            $isAvailable = (bool) $availFilter;
            $qb->andWhere('v.available = :avail')
               ->setParameter('avail', $isAvailable);
        }
    
        // Exécuter la requête et obtenir les véhicules
        $vehicles = $qb->getQuery()->getResult();
    
        // Récupérer le nombre total de réservations pour chaque véhicule
        $reservationsCount = $vehicleRepository->getReservationCountForVehicles();
    
        // Convertir en tableau associatif [id_vehicule => nombre_de_reservations]
        $reservationMap = [];
        foreach ($reservationsCount as $data) {
            $reservationMap[$data['id']] = $data['reservationCount'];
        }
    
        return $this->render('vehicle/index.html.twig', [
            'vehicles' => $vehicles,
            'reservationMap' => $reservationMap,
            'brandFilter' => $brandFilter,
            'maxPriceFilter' => $maxPriceFilter,
            'availFilter' => $availFilter,
        ]);
    }

    #[Route('/vehicle/new', name: 'vehicle_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $vehicle = new Vehicle();
        $form = $this->createForm(VehicleType::class, $vehicle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($vehicle->getDailyPrice() < 100 || $vehicle->getDailyPrice() > 500) {
                $this->addFlash('error', 'Le prix doit être entre 100 et 500 €.');
                return $this->render('vehicle/new.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            $em->persist($vehicle);
            $em->flush();

            $this->addFlash('success', 'Véhicule créé avec succès !');
            return $this->redirectToRoute('vehicle_index');
        }

        return $this->render('vehicle/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/vehicle/{id}/edit', name: 'vehicle_edit')]
    public function edit(Vehicle $vehicle, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(VehicleType::class, $vehicle);
        $form->handleRequest($request);

        // Vérifier si une réservation est en cours
        if ($vehicle->isCurrentlyReserved()) {
            $this->addFlash('error', 'Impossible de modifier la disponibilité tant qu\'une réservation est en cours.');
            return $this->redirectToRoute('vehicle_index');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            if ($vehicle->getDailyPrice() < 100 || $vehicle->getDailyPrice() > 500) {
                $this->addFlash('error', 'Le prix doit être entre 100 et 500 €.');
                return $this->render('vehicle/edit.html.twig', [
                    'form' => $form->createView(),
                    'vehicle' => $vehicle,
                ]);
            }

            $em->flush();

            $this->addFlash('success', 'Véhicule modifié avec succès !');
            return $this->redirectToRoute('vehicle_index');
        }

        return $this->render('vehicle/edit.html.twig', [
            'form' => $form->createView(),
            'vehicle' => $vehicle,
        ]);
    }

    #[Route('/vehicle/{id}/delete', name: 'vehicle_delete')]
    public function delete(Vehicle $vehicle, EntityManagerInterface $em): Response
    {
        // Vérifier si le véhicule a des commentaires
        if (!$vehicle->getComments()->isEmpty()) {
            $this->addFlash('error', 'Ce véhicule ne peut pas être supprimé car il possède des commentaires.');
            return $this->redirectToRoute('vehicle_index');
        }
    
        // Vérifier si le véhicule a des réservations en cours ou futures
        $activeReservations = $em->getRepository(Reservation::class)
            ->createQueryBuilder('r')
            ->where('r.vehicle = :vehicle')
            ->andWhere('r.endDate >= :today')
            ->setParameter('vehicle', $vehicle)
            ->setParameter('today', new \DateTime())
            ->getQuery()
            ->getResult();
    
        if (!empty($activeReservations)) {
            $this->addFlash('error', 'Ce véhicule ne peut pas être supprimé car il est actuellement réservé.');
            return $this->redirectToRoute('vehicle_index');
        }
    
        // Suppression autorisée
        $em->remove($vehicle);
        $em->flush();
    
        $this->addFlash('success', 'Véhicule supprimé avec succès.');
        return $this->redirectToRoute('vehicle_index');
    }

    #[Route('/vehicle/{id}', name: 'vehicle_show')]
    public function show(Vehicle $vehicle): Response
    {
        return $this->render('vehicle/show.html.twig', [
            'vehicle' => $vehicle,
        ]);
    }
}
