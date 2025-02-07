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
        // Récupérer les paramètres de filtre de la requête
        $brandFilter = $request->query->get('brand');
        $priceFilter = $request->query->get('price');
        $availabilityFilter = $request->query->get('availability');

        // Créer la requête de base pour récupérer les véhicules
        $vehiclesQuery = $em->getRepository(Vehicle::class)->createQueryBuilder('v');

        // Appliquer les filtres si définis
        if ($brandFilter) {
            $vehiclesQuery->andWhere('v.brand LIKE :brand')
                ->setParameter('brand', '%' . $brandFilter . '%');
        }
        if ($priceFilter) {
            $vehiclesQuery->andWhere('v.pricePerDay <= :price')
                ->setParameter('price', $priceFilter);
        }
        if ($availabilityFilter) {
            $vehiclesQuery->andWhere('v.isAvailable = :availability')
                ->setParameter('availability', (bool) $availabilityFilter);
        }

        $vehicles = $vehiclesQuery->getQuery()->getResult();

        return $this->render('vehicle/index.html.twig', [
            'vehicles' => $vehicles,
            'brandFilter' => $brandFilter,
            'priceFilter' => $priceFilter,
            'availabilityFilter' => $availabilityFilter
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

