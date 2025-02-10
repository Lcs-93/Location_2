<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Vehicle;
use App\Form\CommentType;
use App\Repository\CommentRepository;
use App\Entity\Reservation; // Ne pas oublier d'importer l'entité si tu ne l'as pas déjà
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CommentController extends AbstractController
{
    #[Route('/vehicle/{id}/comments', name: 'vehicle_comments', methods: ['GET'])]
    public function listComments(Vehicle $vehicle, CommentRepository $commentRepo, EntityManagerInterface $em): Response
    {
        $comments = $commentRepo->findBy(['vehicle' => $vehicle]);
        $averageRating = $commentRepo->getAverageRatingForVehicle($vehicle);
    
        // Vérifier si l’utilisateur connecté a loué ce véhicule
        $userHasRented = 0;
        if ($this->getUser()) {
            $userHasRented = $em->getRepository(Reservation::class)
                ->createQueryBuilder('r')
                ->select('COUNT(r.id)')
                ->where('r.user = :user')
                ->andWhere('r.vehicle = :vehicle')
                ->setParameter('user', $this->getUser())
                ->setParameter('vehicle', $vehicle)
                ->getQuery()
                ->getSingleScalarResult();
        }
    
        return $this->render('comment/list.html.twig', [
            'vehicle' => $vehicle,
            'comments' => $comments,
            'averageRating' => $averageRating,
            'userHasRented' => $userHasRented, // On passe le nombre de réservations
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

        // Vérifier que l'utilisateur a déjà loué ce véhicule au moins une fois
        $user = $this->getUser();
        $hasRented = $em->getRepository(Reservation::class)
            ->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.user = :user')
            ->andWhere('r.vehicle = :vehicle')
            ->setParameter('user', $user)
            ->setParameter('vehicle', $vehicle)
            ->getQuery()
            ->getSingleScalarResult();

        if ($hasRented < 1) {
            $this->addFlash('error', 'Vous ne pouvez pas commenter ce véhicule car vous ne l’avez jamais loué.');
            return $this->redirectToRoute('vehicle_comments', ['id' => $vehicle->getId()]);
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

    #[Route('/comment/{id}/delete', name: 'comment_delete', methods: ['POST'])]
public function deleteComment(Comment $comment, EntityManagerInterface $em): Response
{
    // Vérifier si l'utilisateur est administrateur
    if (!$this->isGranted('ROLE_ADMIN')) {
        $this->addFlash('error', 'Vous n\'avez pas les droits pour supprimer ce commentaire.');
        return $this->redirectToRoute('vehicle_comments', ['id' => $comment->getVehicle()->getId()]);
    }

    // Suppression du commentaire
    $em->remove($comment);
    $em->flush();

    $this->addFlash('success', 'Le commentaire a été supprimé avec succès.');
    return $this->redirectToRoute('vehicle_comments', ['id' => $comment->getVehicle()->getId()]);
}
}
