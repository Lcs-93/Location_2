<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Vehicle;
use App\Form\CommentType;
use App\Repository\CommentRepository;
use App\Repository\VehicleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface; 
class CommentController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    #[Route('/vehicle/{id}/comments', name: 'vehicle_comments', methods: ['GET'])]
    public function viewComments(int $id, VehicleRepository $vehicleRepository, CommentRepository $commentRepository): Response
    {
        $vehicle = $vehicleRepository->find($id);
        
        if (!$vehicle) {
            throw $this->createNotFoundException('Véhicule non trouvé.');
        }

        // Récupérer tous les commentaires associés au véhicule
        $comments = $commentRepository->findBy(['vehicle' => $vehicle]);

        return $this->render('comment/view_comments.html.twig', [
            'vehicle' => $vehicle,
            'comments' => $comments,
        ]);
    }

    #[Route('/vehicle/{id}/comment', name: 'vehicle_comment', methods: ['GET', 'POST'])]
    public function addComment(int $id, Request $request, VehicleRepository $vehicleRepository): Response
    {
        // Recherche du véhicule
        $vehicle = $vehicleRepository->find($id);

        if (!$vehicle) {
            throw $this->createNotFoundException('Véhicule non trouvé.');
        }

        // Création du nouveau commentaire
        $comment = new Comment();
        $comment->setVehicle($vehicle);
        $comment->setUser($this->getUser()); // Assurer que l'utilisateur est connecté

        // Création et gestion du formulaire
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Sauvegarde du commentaire en base de données
            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            // Message flash de succès et redirection
            $this->addFlash('success', 'Commentaire ajouté avec succès !');
            return $this->redirectToRoute('vehicle_comments', ['id' => $vehicle->getId()]);
        }

        // Rendu de la vue
        return $this->render('comment/add_comment.html.twig', [
            'form' => $form->createView(),
            'vehicle' => $vehicle,
        ]);
    }
}
