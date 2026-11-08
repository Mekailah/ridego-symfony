<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Form\PaymentType;
use App\Repository\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/payment')]
final class PaymentController extends AbstractController
{
    // ================= PAYMENT LIST =================

    #[Route(name: 'app_payment_index', methods: ['GET'])]
    public function index(
        PaymentRepository $paymentRepository,
        FormFactoryInterface $formFactory
    ): Response {
        $payments = $paymentRepository->findAll();

        $addForm = $this->createForm(
            PaymentType::class,
            new Payment(),
            [
                'action' => $this->generateUrl('app_payment_new'),
                'method' => 'POST',
            ]
        );

        $editForms = [];

        foreach ($payments as $existingPayment) {
            $editForm = $formFactory->createNamed(
                'payment_edit_' . $existingPayment->getId(),
                PaymentType::class,
                $existingPayment,
                [
                    'action' => $this->generateUrl(
                        'app_payment_edit',
                        ['id' => $existingPayment->getId()]
                    ),
                    'method' => 'POST',
                ]
            );

            $editForms[$existingPayment->getId()] =
                $editForm->createView();
        }

        return $this->render('payment/index.html.twig', [
            'payments' => $payments,
            'form' => $addForm->createView(),
            'editForms' => $editForms,
        ]);
    }

    // ================= ADD PAYMENT =================

    #[Route('/new', name: 'app_payment_new', methods: ['POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        PaymentRepository $paymentRepository,
        FormFactoryInterface $formFactory
    ): Response {
        $payment = new Payment();

        $form = $this->createForm(
            PaymentType::class,
            $payment,
            [
                'action' => $this->generateUrl('app_payment_new'),
                'method' => 'POST',
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($payment);
            $entityManager->flush();

            return $this->redirectToRoute('app_payment_index');
        }

        $payments = $paymentRepository->findAll();
        $editForms = [];

        foreach ($payments as $existingPayment) {
            $editForm = $formFactory->createNamed(
                'payment_edit_' . $existingPayment->getId(),
                PaymentType::class,
                $existingPayment,
                [
                    'action' => $this->generateUrl(
                        'app_payment_edit',
                        ['id' => $existingPayment->getId()]
                    ),
                    'method' => 'POST',
                ]
            );

            $editForms[$existingPayment->getId()] =
                $editForm->createView();
        }

        return $this->render('payment/index.html.twig', [
            'payments' => $payments,
            'form' => $form->createView(),
            'editForms' => $editForms,
            'openAddPaymentModal' => true,
        ]);
    }

    // ================= EDIT PAYMENT =================

    #[Route('/{id}/edit', name: 'app_payment_edit', methods: ['POST'])]
    public function edit(
        Request $request,
        Payment $payment,
        EntityManagerInterface $entityManager,
        PaymentRepository $paymentRepository,
        FormFactoryInterface $formFactory
    ): Response {
        $form = $formFactory->createNamed(
            'payment_edit_' . $payment->getId(),
            PaymentType::class,
            $payment,
            [
                'action' => $this->generateUrl(
                    'app_payment_edit',
                    ['id' => $payment->getId()]
                ),
                'method' => 'POST',
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_payment_index');
        }

        $payments = $paymentRepository->findAll();

        $addForm = $this->createForm(
            PaymentType::class,
            new Payment(),
            [
                'action' => $this->generateUrl('app_payment_new'),
                'method' => 'POST',
            ]
        );

        $editForms = [];

        foreach ($payments as $existingPayment) {
            $id = $existingPayment->getId();

            if ($id === $payment->getId()) {
                $editForms[$id] = $form->createView();
            } else {
                $otherForm = $formFactory->createNamed(
                    'payment_edit_' . $id,
                    PaymentType::class,
                    $existingPayment,
                    [
                        'action' => $this->generateUrl(
                            'app_payment_edit',
                            ['id' => $id]
                        ),
                        'method' => 'POST',
                    ]
                );

                $editForms[$id] = $otherForm->createView();
            }
        }

        return $this->render('payment/index.html.twig', [
            'payments' => $payments,
            'form' => $addForm->createView(),
            'editForms' => $editForms,
            'openEditPaymentId' => $payment->getId(),
        ]);
    }

    // ================= DELETE PAYMENT =================

    #[Route('/{id}', name: 'app_payment_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Payment $payment,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid(
            'delete' . $payment->getId(),
            $request->getPayload()->getString('_token')
        )) {
            $entityManager->remove($payment);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_payment_index');
    }
}
