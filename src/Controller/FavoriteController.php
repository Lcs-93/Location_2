<?php

namespace App\Controller;

use App\Entity\Favorite;
use App\Entity\Vehicle;
use App\Repository\FavoriteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class FavoriteController extends AbstractController
{
    #[Route('/favorite/add/{id}', name: 'add_favorite')]
    #[IsGranted('ROLE_USER')]
    public function addFavorite(Vehicle $vehicle, FavoriteRepository $favoriteRepo, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        // Vérifier si le véhicule est déjà en favori
        $existingFavorite = $favoriteRepo->findOneBy(['user' => $user, 'vehicle' => $vehicle]);

        if ($existingFavorite) {
            $this->addFlash('info', 'Ce véhicule est déjà dans vos favoris.');
        } else {
            $favorite = new Favorite();
            $favorite->setUser($user);
            $favorite->setVehicle($vehicle);

            $em->persist($favorite);
            $em->flush();

            $this->addFlash('success', 'Véhicule ajouté à vos favoris.');
        }

        return $this->redirectToRoute('vehicle_index');
    }

    #[Route('/favorite/remove/{id}', name: 'remove_favorite')]
    #[IsGranted('ROLE_USER')]
    public function removeFavorite(Vehicle $vehicle, FavoriteRepository $favoriteRepo, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $favorite = $favoriteRepo->findOneBy(['user' => $user, 'vehicle' => $vehicle]);

        if ($favorite) {
            $em->remove($favorite);
            $em->flush();

            $this->addFlash('success', 'Véhicule retiré de vos favoris.');
        } else {
            $this->addFlash('error', 'Ce véhicule n\'est pas dans vos favoris.');
        }

        return $this->redirectToRoute('vehicle_index');
    }

    #[Route('/favorites', name: 'user_favorites')]
    #[IsGranted('ROLE_USER')]
    public function listFavorites(FavoriteRepository $favoriteRepo): Response
    {
        $favorites = $favoriteRepo->findBy(['user' => $this->getUser()]);

        return $this->render('favorite/index.html.twig', [
            'favorites' => $favorites,
        ]);
    }
}
