<?php

// src/Controller/VehicleController.php

namespace App\Controller;

use App\Entity\Vehicle;
use App\Form\VehicleType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class VehicleController extends AbstractController
{
    #[Route('/vehicles', name: 'vehicle_index')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        // Récupérer les paramètres de filtre depuis la requête GET
        $brandFilter = $request->query->get('brand');       // ex: ?brand=Toyota
        $maxPriceFilter = $request->query->get('maxPrice'); // ex: ?maxPrice=50
        $availFilter = $request->query->get('avail');       // ex: ?avail=1 ou ?avail=0
    
        // Construire la requête de base pour récupérer les véhicules
        $qb = $em->getRepository(Vehicle::class)->createQueryBuilder('v');
    
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
        // On vérifie si 'avail' est bien défini dans l'URL (peut être '0', '1', ou vide)
        if ($availFilter !== null && $availFilter !== '') {
            $isAvailable = (bool) $availFilter; // '1' => true, '0' => false
            // Attention à la propriété dans l'entité : 'available' ou 'isAvailable'
            $qb->andWhere('v.available = :avail')
               ->setParameter('avail', $isAvailable);
        }
    
        // Exécuter la requête et obtenir le résultat
        $vehicles = $qb->getQuery()->getResult();
    
        // Renvoyer la vue avec les filtres pour les réafficher dans le formulaire
        return $this->render('vehicle/index.html.twig', [
            'vehicles' => $vehicles,
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
            $em->persist($vehicle);
            $em->flush();

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

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

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
        $em->remove($vehicle);
        $em->flush();

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

