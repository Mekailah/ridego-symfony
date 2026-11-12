<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/profile/change-password', name: 'app_change_password_page', methods: ['GET'])]
    public function changePasswordPage(): Response
    {
        return $this->render('profile/change_password.html.twig');
    }
    #[Route('/profile/change-password', name: 'app_change_password', methods: ['POST'])]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        // Verify CSRF token.
        if (!$this->isCsrfTokenValid(
            'change_password',
            $request->request->getString('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Invalid CSRF token.'
            );
        }

        $currentPassword = $request->request->getString('current_password');
        $newPassword = $request->request->getString('new_password');
        $confirmPassword = $request->request->getString('confirm_password');

        // Check current password.
        if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
            $this->addFlash('error', 'Current password is incorrect.');

            return $this->redirectToRoute('app_change_password_page');
        }

        // Check password length.
        if (mb_strlen($newPassword) < 12) {
            $this->addFlash(
                'error',
                'New password must contain at least 12 characters.'
            );

            return $this->redirectToRoute('app_change_password_page');
        }

        // Check password confirmation.
        if ($newPassword !== $confirmPassword) {
            $this->addFlash('error', 'New passwords do not match.');

            return $this->redirectToRoute('app_change_password_page');
        }

        // Hash and save new password.
        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $newPassword
        );

        $user->setPassword($hashedPassword);
        $entityManager->flush();

        $this->addFlash('success', 'Password changed successfully.');

        return $this->redirectToRoute('app_change_password_page');
        }
}
