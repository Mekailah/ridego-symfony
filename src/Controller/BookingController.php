<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Customer;
use App\Entity\Motorcycle;
use App\Entity\Payment;
use App\Form\BookingType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BookingController extends AbstractController
{
    #[Route('/admin/bookings', name: 'admin_bookings', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $bookings = $entityManager
            ->getRepository(Booking::class)
            ->findBy([], ['id' => 'DESC']);

        $booking = new Booking();

        $form = $this->createForm(BookingType::class, $booking, [
            'action' => $this->generateUrl('admin_booking_new'),
            'method' => 'POST',
            'motorcycle_choices' => $this->getMotorcycleChoices($entityManager),
        ]);

        return $this->render('booking/index.html.twig', [
            'bookings' => $bookings,
            'form' => $form->createView(),
            'customerDetails' => $this->getCustomerDetails($entityManager),
        ]);
    }

    #[Route('/admin/bookings/{id}/confirm', name: 'admin_booking_confirm', methods: ['POST'])]
    public function confirm(
        Booking $booking,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid(
            'confirm' . $booking->getId(),
            $request->request->get('_token')
        )) {
            return $this->bookingError('Invalid security token.');
        }

        if ($booking->getStatus() !== 'Pending') {
            return $this->bookingError(
                'Only pending bookings can be confirmed.'
            );
        }

        if ($booking->getTotalAmount() === null) {
            return $this->bookingError(
                'This booking has no rental total.'
            );
        }

        if (!$this->isFullyPaid($booking)) {
            return $this->bookingError(
                'Full payment is required before confirming this booking.'
            );
        }

        $motorcycle = $entityManager
            ->getRepository(Motorcycle::class)
            ->findOneBy([
                'name' => $booking->getMotorcycleName(),
            ]);

        if (!$motorcycle || $motorcycle->getAvailableUnits() <= 0) {
            return $this->bookingError(
                'No available motorcycle unit found.'
            );
        }

        $motorcycle->setAvailableUnits(
            $motorcycle->getAvailableUnits() - 1
        );

        $motorcycle->setStatus(
            $motorcycle->getAvailableUnits() > 0
                ? 'Available'
                : 'Unavailable'
        );

        $booking->setStatus('Confirmed');

        $entityManager->flush();

        return $this->redirectToRoute('admin_bookings');
    }

    #[Route('/admin/bookings/{id}/return', name: 'admin_booking_return', methods: ['POST'])]
    public function markReturned(
        Booking $booking,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid(
            'return' . $booking->getId(),
            $request->request->get('_token')
        )) {
            return $this->bookingError('Invalid security token.');
        }

        if ($booking->getStatus() !== 'Confirmed' || $booking->isReturned()) {
            return $this->bookingError(
                'Only confirmed bookings can be marked as returned.'
            );
        }

        $motorcycle = $entityManager
            ->getRepository(Motorcycle::class)
            ->findOneBy([
                'name' => $booking->getMotorcycleName(),
            ]);

        if (!$motorcycle) {
            return $this->bookingError(
                'Motorcycle record not found.'
            );
        }

        if ($motorcycle->getAvailableUnits() < $motorcycle->getTotalUnits()) {
            $motorcycle->setAvailableUnits(
                $motorcycle->getAvailableUnits() + 1
            );
        }

        $motorcycle->setStatus(
            $motorcycle->getAvailableUnits() > 0
                ? 'Available'
                : 'Unavailable'
        );

        $booking->setReturned(true);
        $booking->setStatus('Returned');
        $booking->setReturnedAt(new \DateTimeImmutable());

        $entityManager->flush();

        return $this->redirectToRoute('admin_bookings');
    }

    #[Route('/admin/bookings/{id}/delete', name: 'admin_booking_delete', methods: ['POST'])]
    public function delete(
        Booking $booking,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid(
            'delete' . $booking->getId(),
            $request->request->get('_token')
        )) {
            return $this->bookingError('Invalid security token.');
        }

        if ($booking->getStatus() !== 'Pending') {
            return $this->bookingError(
                'Only pending bookings can be deleted.'
            );
        }

        // Remove associated payments before deleting the booking.
        foreach ($booking->getPayments() as $payment) {
            $entityManager->remove($payment);
        }

        $entityManager->remove($booking);
        $entityManager->flush();

        return $this->redirectToRoute('admin_bookings');
    }

    #[Route('/admin/bookings/new', name: 'admin_booking_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $booking = new Booking();

        $form = $this->createForm(BookingType::class, $booking, [
            'motorcycle_choices' => $this->getMotorcycleChoices($entityManager),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $customer = $booking->getCustomer();

            if (!$customer) {
                return $this->bookingError(
                    'Please select a registered customer.'
                );
            }

            $booking->setCustomerName($customer->getName());

            $motorcycle = $entityManager
                ->getRepository(Motorcycle::class)
                ->findOneBy([
                    'name' => $booking->getMotorcycleName(),
                ]);

            if (!$motorcycle || $motorcycle->getAvailableUnits() <= 0) {
                return $this->bookingError(
                    'Selected motorcycle is no longer available.'
                );
            }

            $pickupDate = $booking->getPickupDate();
            $returnDate = $booking->getReturnDate();

            if ($returnDate < $pickupDate) {
                return $this->bookingError(
                    'Return date cannot be earlier than pick-up date.'
                );
            }

            $rentalDays = max(
                1,
                (int) $pickupDate->diff($returnDate)->days
            );

            $totalAmount = $motorcycle->getPricePerDay() * $rentalDays;

            $booking->setTotalAmount((string) $totalAmount);
            $booking->setStatus('Pending');
            $booking->setReturned(false);
            $booking->setReturnedAt(null);

            $entityManager->persist($booking);

            // Automatically create a pending payment.
            $payment = new Payment();
            $payment->setBooking($booking);
            $payment->setAmount((string) $totalAmount);
            $payment->setPaymentStatus('Pending');
            $payment->setPaymentMethod(null);
            $payment->setPaymentDate(null);

            $entityManager->persist($payment);
            $entityManager->flush();

            return $this->redirectToRoute('admin_bookings');
        }

        return $this->render('booking/new.html.twig', [
            'form' => $form->createView(),
            'customerDetails' => $this->getCustomerDetails($entityManager),
        ]);
    }

    private function getMotorcycleChoices(
        EntityManagerInterface $entityManager
    ): array {
        $motorcycles = $entityManager
            ->getRepository(Motorcycle::class)
            ->findBy([], ['name' => 'ASC']);

        $choices = [];

        foreach ($motorcycles as $motorcycle) {
            if ($motorcycle->getAvailableUnits() > 0) {
                $label = $motorcycle->getName()
                    . ' (' . $motorcycle->getAvailableUnits() . ' available)';

                $choices[$label] = $motorcycle->getName();
            }
        }

        return $choices;
    }

    private function getCustomerDetails(
        EntityManagerInterface $entityManager
    ): array {
        $customers = $entityManager
            ->getRepository(Customer::class)
            ->findAll();

        $details = [];

        foreach ($customers as $customer) {
            $details[$customer->getId()] = [
                'email' => $customer->getEmail(),
                'phone' => $customer->getPhoneNumber(),
            ];
        }

        return $details;
    }

    private function isFullyPaid(Booking $booking): bool
    {
        $bookingTotal = $booking->getTotalAmount();

        if ($bookingTotal === null) {
            return false;
        }

        foreach ($booking->getPayments() as $payment) {
            if (
                $payment->getPaymentStatus() === 'Paid'
                && $payment->getAmount() !== null
                && (int) round((float) $payment->getAmount() * 100)
                    === (int) round((float) $bookingTotal * 100)
            ) {
                return true;
            }
        }

        return false;
    }

    private function bookingError(string $message): Response
    {
        $this->addFlash('error', $message);

        return $this->redirectToRoute('admin_bookings');
    }
}
