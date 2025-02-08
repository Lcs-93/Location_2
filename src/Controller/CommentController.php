<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Vehicle;
use App\Form\CommentType;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CommentController extends AbstractController
{
    #[Route('/vehicle/{id}/comments', name: 'vehicle_comments', methods: ['GET'])]
    public function listComments(Vehicle $vehicle, CommentRepository $commentRepo): Response
    {
        // Récupérer les commentaires pour ce véhicule
        $comments = $commentRepo->findBy(['vehicle' => $vehicle]);

        // Calculer la note moyenne
        $averageRating = $commentRepo->getAverageRatingForVehicle($vehicle);

        return $this->render('comment/list.html.twig', [
            'vehicle' => $vehicle,
            'comments' => $comments,
            'averageRating' => $averageRating,
        ]);
    }

    #[Route('/vehicle/{id}/comment/new', name: 'vehicle_comment_new', methods: ['GET','POST'])]
    public function newComment(Vehicle $vehicle, Request $request, EntityManagerInterface $em): Response
    {
        // Vérifier si l'utilisateur est connecté et a le rôle USER
        if (!$this->getUser() || !in_array('ROLE_USER', $this->getUser()->getRoles())) {
            $this->addFlash('error', 'Vous devez être connecté pour laisser un commentaire.');
            return $this->redirectToRoute('vehicle_index');
        }

        // Créer un nouveau commentaire
        $comment = new Comment();
        $comment->setVehicle($vehicle);
        $comment->setUser($this->getUser());

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Sauvegarde
            $em->persist($comment);
            $em->flush();

            $this->addFlash('success', 'Commentaire ajouté avec succès !');
            // Rediriger vers la page des commentaires
            return $this->redirectToRoute('vehicle_comments', ['id' => $vehicle->getId()]);
        }

        return $this->render('comment/new.html.twig', [
            'form' => $form->createView(),
            'vehicle' => $vehicle,
        ]);
    }
}
