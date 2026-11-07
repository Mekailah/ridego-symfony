<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Form\CustomerType;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/customer')]
final class CustomerController extends AbstractController
{
    // ================= CUSTOMER LIST =================

    #[Route(name: 'app_customer_index', methods: ['GET'])]
    public function index(
        CustomerRepository $customerRepository,
        FormFactoryInterface $formFactory
    ): Response {
        $customers = $customerRepository->findAll();

        // Add Customer form
        $addForm = $this->createForm(
            CustomerType::class,
            new Customer(),
            [
                'action' => $this->generateUrl('app_customer_new'),
                'method' => 'POST',
            ]
        );

        // Unique Edit Customer forms
        $editForms = [];

        foreach ($customers as $existingCustomer) {
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
        EntityManagerInterface $entityManager
    ): Response {
        $customer = new Customer();

        $form = $this->createForm(
            CustomerType::class,
            $customer
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($customer);
            $entityManager->flush();

            return $this->redirectToRoute(
                'app_customer_index',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->render('customer/new.html.twig', [
            'customer' => $customer,
            'form' => $form->createView(),
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

            return $this->redirectToRoute(
                'app_customer_index',
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        // Return to Customer Management if editing fails
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
            $id = $existingCustomer->getId();

            if ($id === $customer->getId()) {
                // Keep the submitted form and its validation errors
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
        if ($this->isCsrfTokenValid(
            'delete' . $customer->getId(),
            $request->getPayload()->getString('_token')
        )) {
            $entityManager->remove($customer);
            $entityManager->flush();
        }

        return $this->redirectToRoute(
            'app_customer_index',
            [],
            Response::HTTP_SEE_OTHER
        );
    }
}
