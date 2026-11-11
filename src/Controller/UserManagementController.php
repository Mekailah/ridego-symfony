<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserEditFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class UserManagementController extends AbstractController
{
    #[Route('/admin/users', name: 'admin_users', methods: ['GET'])]
    public function index(
        EntityManagerInterface $entityManager,
        FormFactoryInterface $formFactory
    ): Response {
        $users = $entityManager
            ->getRepository(User::class)
            ->findBy([], ['id' => 'ASC']);

        $editForms = [];

        foreach ($users as $user) {
            $editForms[$user->getId()] = $formFactory->createNamed(
                'user_edit_' . $user->getId(),
                UserEditFormType::class,
                $user,
                [
                    'action' => $this->generateUrl(
                        'admin_users_edit',
                        ['id' => $user->getId()]
                    ),
                    'method' => 'POST',
'csrf_token_id' => 'edit_user_' . $user->getId(),
                ]
            )->createView();
        }

        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
            'editForms' => $editForms,
        ]);
    }

    #[Route('/admin/users/{id}/edit', name: 'admin_users_edit', methods: ['GET', 'POST'])]
    public function edit(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager,
        FormFactoryInterface $formFactory
    ): Response {
        $form = $formFactory->createNamed(
            'user_edit_' . $user->getId(),
            UserEditFormType::class,
            $user,
            [
                'method' => 'POST',
                'csrf_token_id' => 'edit_user_' . $user->getId(),
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentUser = $this->getUser();

            // Get the role selected in the Edit User form.
            $selectedRole = $form->get('roles')->getData();

            $isCurrentlyAdmin = in_array(
                'ROLE_ADMIN',
                $user->getRoles(),
                true
            );

            // Prevent an Admin from removing their own Admin role.
            if (
                $currentUser instanceof User
                && $currentUser->getId() === $user->getId()
                && $isCurrentlyAdmin
                && $selectedRole !== 'ROLE_ADMIN'
            ) {
                $this->addFlash(
                    'error',
                    'You cannot remove your own Admin role.'
                );

                return $this->redirectToRoute('admin_users');
            }

            // Prevent an Admin from disabling their own account.
            if (
                $currentUser instanceof User
                && $currentUser->getId() === $user->getId()
                && !$user->isActive()
            ) {
                $user->setIsActive($isCurrentlyAdmin);

                $this->addFlash(
                    'error',
                    'You cannot disable your own account.'
                );

                return $this->redirectToRoute('admin_users');
            }

            // Prevent removing the last active Admin through
            // a combination of role and account-status changes.
            $isRemainingActiveAdmin =
                $selectedRole === 'ROLE_ADMIN'
                && $user->isActive();

            if (!$isRemainingActiveAdmin && $isCurrentlyAdmin) {
                $admins = $entityManager
                    ->getRepository(User::class)
                    ->findAll();

                $otherActiveAdmins = array_filter(
                    $admins,
                    fn (User $account) =>
                        $account->getId() !== $user->getId()
                        && in_array('ROLE_ADMIN', $account->getRoles(), true)
                        && $account->isActive()
                );

                if (count($otherActiveAdmins) === 0) {
                    $user->setIsActive(true);

                    $this->addFlash(
                        'error',
                        'You cannot remove or disable the last active Admin account.'
                    );

                    return $this->redirectToRoute('admin_users');
                }
            }

            // Save the selected role after the security checks.
            $user->setRoles([$selectedRole]);
            
            $entityManager->flush();

            $this->addFlash('success', 'User updated successfully.');

            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/users/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/admin/users/{id}/delete', name: 'admin_users_delete', methods: ['POST'])]
    public function delete(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid(
            'delete_user_' . $user->getId(),
            $request->request->getString('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Invalid CSRF token.'
            );
        }

        $currentUser = $this->getUser();

        // Prevent deleting your own account.
        if (
            $currentUser instanceof User
            && $currentUser->getId() === $user->getId()
        ) {
            throw $this->createAccessDeniedException(
                'You cannot delete your own account.'
            );
        }

        // Prevent deleting the last active Admin account.
        if (
            in_array('ROLE_ADMIN', $user->getRoles(), true)
            && $user->isActive()
        ) {
            $admins = $entityManager
                ->getRepository(User::class)
                ->findAll();

            $otherActiveAdmins = array_filter(
                $admins,
                fn (User $account) =>
                    $account->getId() !== $user->getId()
                    && in_array('ROLE_ADMIN', $account->getRoles(), true)
                    && $account->isActive()
            );

            if (count($otherActiveAdmins) === 0) {
                $this->addFlash(
                    'error',
                    'You cannot delete the last active Admin account.'
                );

                return $this->redirectToRoute('admin_users');
            }
        }

        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', 'User deleted successfully.');

        return $this->redirectToRoute('admin_users');
    }
}
