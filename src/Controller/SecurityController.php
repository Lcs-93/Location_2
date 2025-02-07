<?php

namespace App\Controller;

use App\Form\SecurityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    private $authenticationUtils;

    public function __construct(AuthenticationUtils $authenticationUtils)
    {
        $this->authenticationUtils = $authenticationUtils;
    }

    #[Route('/login', name: 'app_login')]
    public function login(Request $request): Response
    {
        // Récupérer les erreurs de connexion (si elles existent)
        $error = $this->authenticationUtils->getLastAuthenticationError();

        // Récupérer le dernier nom d'utilisateur saisi (si disponible)
        $lastUsername = $this->authenticationUtils->getLastUsername();

        // Créer et gérer le formulaire personnalisé SecurityType
        $form = $this->createForm(SecurityType::class, null, [
            'action' => $this->generateUrl('app_login'),
            'method' => 'POST',
        ]);
        
        $form->handleRequest($request);

        // Vérifier si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Symfony gère la connexion automatiquement via le firewall
        }

        return $this->render('security/login.html.twig', [
            'form' => $form->createView(),
            'error' => $error,
            'last_username' => $lastUsername,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Symfony gère la déconnexion automatiquement
    }
}
