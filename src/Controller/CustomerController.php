<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\User;
use App\Form\CustomerType;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/customer')]
#[IsGranted('ROLE_USER')]
final class CustomerController extends AbstractController
{
    // ================= OWNERSHIP CHECK =================

    private function canManageCustomer(Customer $customer): bool
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $user = $this->getUser();

        return $user instanceof User
            && $customer->getCreatedBy() !== null
            && $customer->getCreatedBy()->getId() === $user->getId();
    }

    // ================= CUSTOMER LIST =================

    #[Route(name: 'app_customer_index', methods: ['GET'])]
    public function index(
        CustomerRepository $customerRepository,
        FormFactoryInterface $formFactory
    ): Response {
        $customers = $customerRepository->findAll();

        $addForm = $this->createForm(
            CustomerType::class,
            new Customer(),
            [
                'action' => $this->generateUrl('app_customer_new'),
                'method' => 'POST',
            ]
        );

        $editForms = [];

        foreach ($customers as $existingCustomer) {
            if (!$this->canManageCustomer($existingCustomer)) {
                continue;
            }

            $editForm = $formFactory->createNamed(
                'customer_edit_' . $existingCustomer->getId(),
                CustomerType::class,
                $existingCustomer,
                [
                    'action' => $this->generateUrl(
                        'app_customer_edit',
                        ['id' => $existingCustomer->getId()]
                    ),
                    'method' => 'POST',
                ]
            );

            $editForms[$existingCustomer->getId()] =
                $editForm->createView();
        }

        return $this->render('customer/index.html.twig', [
            'customers' => $customers,
            'form' => $addForm->createView(),
            'editForms' => $editForms,
        ]);
    }

    // ================= ADD CUSTOMER =================

    #[Route('/new', name: 'app_customer_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        CustomerRepository $customerRepository,
        FormFactoryInterface $formFactory
    ): Response {
        $customer = new Customer();

        $form = $this->createForm(
            CustomerType::class,
            $customer,
            [
                'action' => $this->generateUrl('app_customer_new'),
                'method' => 'POST',
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();

            if (!$user instanceof User) {
                throw $this->createAccessDeniedException(
                    'You must be logged in to add customers.'
                );
            }

            // Assign the logged-in user as the creator
            $customer->setCreatedBy($user);

            $entityManager->persist($customer);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Customer added successfully.'
            );

            return $this->redirectToRoute(
                'app_customer_index',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        // Keep validation errors in the Add Customer modal
        $customers = $customerRepository->findAll();

        $editForms = [];

        foreach ($customers as $existingCustomer) {
            if (!$this->canManageCustomer($existingCustomer)) {
                continue;
            }

            $editForm = $formFactory->createNamed(
                'customer_edit_' . $existingCustomer->getId(),
                CustomerType::class,
                $existingCustomer,
                [
                    'action' => $this->generateUrl(
                        'app_customer_edit',
                        ['id' => $existingCustomer->getId()]
                    ),
                    'method' => 'POST',
                ]
            );

            $editForms[$existingCustomer->getId()] =
                $editForm->createView();
        }

        return $this->render('customer/index.html.twig', [
            'customers' => $customers,
            'form' => $form->createView(),
            'editForms' => $editForms,
            'openAddCustomerModal' => $form->isSubmitted(),
        ]);
    }

    // ================= EDIT CUSTOMER =================

    #[Route('/{id}/edit', name: 'app_customer_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Customer $customer,
        EntityManagerInterface $entityManager,
        FormFactoryInterface $formFactory,
        CustomerRepository $customerRepository
    ): Response {
        // Prevent Staff from editing someone else's customer
        if (!$this->canManageCustomer($customer)) {
            throw $this->createAccessDeniedException(
                'You can only edit customers you created.'
            );
        }

        $form = $formFactory->createNamed(
            'customer_edit_' . $customer->getId(),
            CustomerType::class,
            $customer,
            [
                'action' => $this->generateUrl(
                    'app_customer_edit',
                    ['id' => $customer->getId()]
                ),
                'method' => 'POST',
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Customer updated successfully.'
            );

            return $this->redirectToRoute(
                'app_customer_index',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        // Keep validation errors in the Edit Customer modal
        $customers = $customerRepository->findAll();

        $addForm = $this->createForm(
            CustomerType::class,
            new Customer(),
            [
                'action' => $this->generateUrl('app_customer_new'),
                'method' => 'POST',
            ]
        );

        $editForms = [];

        foreach ($customers as $existingCustomer) {
            if (!$this->canManageCustomer($existingCustomer)) {
                continue;
            }

            $id = $existingCustomer->getId();

            if ($id === $customer->getId()) {
                $editForms[$id] = $form->createView();
            } else {
                $otherForm = $formFactory->createNamed(
                    'customer_edit_' . $id,
                    CustomerType::class,
                    $existingCustomer,
                    [
                        'action' => $this->generateUrl(
                            'app_customer_edit',
                            ['id' => $id]
                        ),
                        'method' => 'POST',
                    ]
                );

                $editForms[$id] = $otherForm->createView();
            }
        }

        return $this->render('customer/index.html.twig', [
            'customers' => $customers,
            'form' => $addForm->createView(),
            'editForms' => $editForms,
            'openEditCustomerId' => $customer->getId(),
        ]);
    }

    // ================= DELETE CUSTOMER =================

    #[Route('/{id}', name: 'app_customer_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Customer $customer,
        EntityManagerInterface $entityManager
    ): Response {
        // Prevent Staff from deleting someone else's customer
        if (!$this->canManageCustomer($customer)) {
            throw $this->createAccessDeniedException(
                'You can only delete customers you created.'
            );
        }

        if ($this->isCsrfTokenValid(
            'delete' . $customer->getId(),
            $request->getPayload()->getString('_token')
        )) {
            $entityManager->remove($customer);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Customer deleted successfully.'
            );
        }

        return $this->redirectToRoute(
            'app_customer_index',
            [],
            Response::HTTP_SEE_OTHER
        );
    }
}
