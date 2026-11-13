<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Entity\User;
use App\Form\PaymentType;
use App\Repository\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/payment')]
#[IsGranted('ROLE_USER')]
final class PaymentController extends AbstractController
{
    private function canManagePayment(Payment $payment): bool
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $user = $this->getUser();
        $booking = $payment->getBooking();

        return $user instanceof User
            && $booking !== null
            && $booking->getCreatedBy() !== null
            && $booking->getCreatedBy()->getId() === $user->getId();
    }

    #[Route('', name: 'app_payment_index', methods: ['GET'])]
    public function index(
        PaymentRepository $paymentRepository,
        FormFactoryInterface $formFactory
    ): Response {
        $payments = $paymentRepository->findBy([], ['id' => 'DESC']);

        return $this->render('payment/index.html.twig', [
            'payments' => $payments,
            'editForms' => $this->buildEditForms($payments, $formFactory),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_payment_edit', methods: ['POST'])]
    public function edit(
        Request $request,
        Payment $payment,
        EntityManagerInterface $entityManager,
        PaymentRepository $paymentRepository,
        FormFactoryInterface $formFactory
    ): Response {
        if (!$this->canManagePayment($payment)) {
            throw $this->createAccessDeniedException(
                'You can only edit payments for bookings you created.'
            );
        }

        $previousStatus = $payment->getPaymentStatus();
        $previousDate = $payment->getPaymentDate();

        $form = $formFactory->createNamed(
            'payment_edit_' . $payment->getId(),
            PaymentType::class,
            $payment,
            [
                'action' => $this->generateUrl('app_payment_edit', [
                    'id' => $payment->getId(),
                ]),
                'method' => 'POST',
                'lock_booking' => true,
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($payment->getPaymentStatus() === 'Paid' && $previousStatus !== 'Paid') {
                $payment->setPaymentDate(new \DateTimeImmutable());
            } elseif ($payment->getPaymentStatus() === 'Paid') {
                $payment->setPaymentDate($previousDate ?? new \DateTimeImmutable());
            } else {
                $payment->setPaymentDate(null);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Payment updated successfully.');
            return $this->redirectToRoute('app_payment_index');
        }

        $payments = $paymentRepository->findBy([], ['id' => 'DESC']);
    
        return $this->render('payment/index.html.twig', [
            'payments' => $payments,
            'editForms' => $this->buildEditForms(
                $payments,
                $formFactory,
                $payment->getId(),
                $form
            ),
            'openEditPaymentId' => $payment->getId(),
        ]);
    }

    #[Route('/{id}', name: 'app_payment_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Payment $payment,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->canManagePayment($payment)) {
            throw $this->createAccessDeniedException(
                'You can only delete payments for bookings you created.'
            );
        }

        if ($this->isCsrfTokenValid(
            'delete' . $payment->getId(),
            $request->getPayload()->getString('_token')
        )) {
            $entityManager->remove($payment);
            $entityManager->flush();
            $this->addFlash('success', 'Payment deleted successfully.');
        }

        return $this->redirectToRoute('app_payment_index');
    }

    private function buildEditForms(
        array $payments,
        FormFactoryInterface $formFactory,
        ?int $submittedPaymentId = null,
        ?FormInterface $submittedForm = null
    ): array {
        $editForms = [];

        foreach ($payments as $existingPayment) {
            if (!$this->canManagePayment($existingPayment)) {
                continue;
            }

            $id = $existingPayment->getId();
            if ($submittedPaymentId !== null && $id === $submittedPaymentId && $submittedForm !== null) {
                $editForms[$id] = $submittedForm->createView();
                continue;
            }

            $editForm = $formFactory->createNamed(
                'payment_edit_' . $id,
                PaymentType::class,
                $existingPayment,
                [
                    'action' => $this->generateUrl('app_payment_edit', ['id' => $id]),
                    'method' => 'POST',
                    'lock_booking' => true,
                ]
            );
            $editForms[$id] = $editForm->createView();
        }

        return $editForms;
    }
}
