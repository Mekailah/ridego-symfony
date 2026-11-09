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
        $payments = $paymentRepository->findBy(
            [],
            ['id' => 'DESC']
        );

        $addForm = $this->createForm(
            PaymentType::class,
            new Payment(),
            [
                'action' => $this->generateUrl('app_payment_new'),
                'method' => 'POST',
            ]
        );

        $editForms = $this->buildEditForms(
            $payments,
            $formFactory
        );

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

            $booking = $payment->getBooking();

            // Prevent duplicate payment records.
            if (
                $booking !== null &&
                $paymentRepository->findOneBy([
                    'booking' => $booking,
                ]) !== null
            ) {
                $this->addFlash(
                    'error',
                    'This booking already has a payment record. Please edit the existing payment instead.'
                );

                return $this->redirectToRoute(
                    'app_payment_index'
                );
            }

            // Automatically record the actual payment date.
            $this->updatePaymentDate($payment);

            $entityManager->persist($payment);
            $entityManager->flush();

            $this->addFlash(
                    'success',
                    'Payment added successfully.'
                );

                return $this->redirectToRoute(
                    'app_payment_index'
                );
            }

            // Reopen Add Payment modal when validation fails.
            $payments = $paymentRepository->findBy(
                [],
                ['id' => 'DESC']
            );

            $editForms = $this->buildEditForms(
                $payments,
                $formFactory
            );

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
        // Remember the previous payment status before editing.
        $previousStatus = $payment->getPaymentStatus();
        $previousDate = $payment->getPaymentDate();

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

            // Set the date only when payment becomes Paid.
            if (
                $payment->getPaymentStatus() === 'Paid' &&
                $previousStatus !== 'Paid'
            ) {
                $payment->setPaymentDate(
                    new \DateTimeImmutable()
                );
            }

            // Keep the original date when editing an already Paid payment.
            elseif (
                $payment->getPaymentStatus() === 'Paid' &&
                $previousStatus === 'Paid'
            ) {
                $payment->setPaymentDate(
                    $previousDate ?? new \DateTimeImmutable()
                );
            }

            // Clear the date if payment is no longer Paid.
            else {
                $payment->setPaymentDate(null);
            }

            $entityManager->flush();

            $this->addFlash(
                'success',
                'Payment updated successfully.'
            );

            return $this->redirectToRoute(
                'app_payment_index'
            );
        }

        // Reopen the correct Edit modal on validation errors.
        $payments = $paymentRepository->findBy(
            [],
            ['id' => 'DESC']
        );

        $addForm = $this->createForm(
            PaymentType::class,
            new Payment(),
            [
                'action' => $this->generateUrl('app_payment_new'),
                'method' => 'POST',
            ]
        );

        $editForms = $this->buildEditForms(
            $payments,
            $formFactory,
            $payment->getId(),
            $form
        );

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

            $this->addFlash(
                'success',
                'Payment deleted successfully.'
            );
        }

        return $this->redirectToRoute(
            'app_payment_index'
        );
    }

    // ================= AUTOMATIC PAYMENT DATE =================

    private function updatePaymentDate(Payment $payment): void
    {
        if ($payment->getPaymentStatus() === 'Paid') {
            $payment->setPaymentDate(
                new \DateTimeImmutable()
            );
        } else {
            $payment->setPaymentDate(null);
        }
    }

    // ================= BUILD EDIT FORMS =================

    private function buildEditForms(
        array $payments,
        FormFactoryInterface $formFactory,
        ?int $submittedPaymentId = null,
        $submittedForm = null
    ): array {
        $editForms = [];

        foreach ($payments as $existingPayment) {
            $id = $existingPayment->getId();

            if (
                $submittedPaymentId !== null &&
                $id === $submittedPaymentId &&
                $submittedForm !== null
            ) {
                $editForms[$id] = $submittedForm->createView();
                continue;
            }

            $editForm = $formFactory->createNamed(
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

            $editForms[$id] = $editForm->createView();
        }

        return $editForms;
    }
}
