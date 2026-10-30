<?php

namespace App\Controller;

use App\Form\BookingType;
use App\Entity\Booking;
use App\Entity\Motorcycle;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BookingController extends AbstractController
{
    #[Route('/admin/bookings', name: 'admin_bookings')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $bookings = $entityManager
            ->getRepository(Booking::class)
            ->findBy([], ['id' => 'DESC']);

        return $this->render('booking/index.html.twig', [
            'bookings' => $bookings,
        ]);
    }

    #[Route('/admin/bookings/{id}/return', name: 'admin_booking_return', methods: ['POST'])]
    public function markReturned(
        Booking $booking,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid(
            'return'.$booking->getId(),
            $request->request->get('_token')
        )) {
            return $this->redirectToRoute('admin_bookings');
        }

        // Only confirmed bookings can be returned
        if ($booking->getStatus() !== 'Confirmed' || $booking->isReturned()) {
            return $this->redirectToRoute('admin_bookings');
        }

        $motorcycle = $entityManager
            ->getRepository(Motorcycle::class)
            ->findOneBy([
                'name' => $booking->getMotorcycleName()
            ]);

        if (!$motorcycle) {
            $this->addFlash('error', 'Motorcycle record not found.');

            return $this->redirectToRoute('admin_bookings');
        }

        // Restore one available unit
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

    #[Route('/admin/bookings/{id}/confirm', name: 'admin_booking_confirm', methods: ['POST'])]
    public function confirm(
        Booking $booking,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid(
            'confirm'.$booking->getId(),
            $request->request->get('_token')
        )) {
            return $this->redirectToRoute('admin_bookings');
        }

        // Prevent confirming or deducting inventory twice
        if ($booking->getStatus() !== 'Pending') {
            return $this->redirectToRoute('admin_bookings');
        }

        $motorcycle = $entityManager
            ->getRepository(Motorcycle::class)
            ->findOneBy([
                'name' => $booking->getMotorcycleName()
            ]);

        if (!$motorcycle || $motorcycle->getAvailableUnits() <= 0) {
            $this->addFlash('error', 'No available motorcycle unit found.');

            return $this->redirectToRoute('admin_bookings');
        }

        // Deduct one available unit
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
    
    #[Route('/admin/bookings/{id}/delete', name: 'admin_booking_delete', methods: ['POST'])]
    public function delete(
        Booking $booking,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid(
            'delete'.$booking->getId(),
            $request->request->get('_token')
        )) {
            return $this->redirectToRoute('admin_bookings');
        }

        // Only pending bookings can be deleted
        if ($booking->getStatus() !== 'Pending') {
            $this->addFlash('error', 'Only pending bookings can be deleted.');

            return $this->redirectToRoute('admin_bookings');
        }

        $entityManager->remove($booking);
        $entityManager->flush();

        $this->addFlash('success', 'Booking deleted successfully.');

        return $this->redirectToRoute('admin_bookings');
    }
    

    #[Route('/admin/bookings/new', name: 'admin_booking_new')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $booking = new Booking();

        // Only motorcycles with available units can be selected
        $motorcycles = $entityManager
            ->getRepository(Motorcycle::class)
            ->findBy([], ['name' => 'ASC']);

        $motorcycleChoices = [];

        foreach ($motorcycles as $motorcycle) {
            if ($motorcycle->getAvailableUnits() > 0) {
                $motorcycleChoices[
                    $motorcycle->getName() .
                    ' (' . $motorcycle->getAvailableUnits() . ' available)'
                ] = $motorcycle->getName();
            }
        }

        $form = $this->createForm(BookingType::class, $booking, [
            'motorcycle_choices' => $motorcycleChoices,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $booking->setStatus('Pending');
            $booking->setReturned(false);
            $booking->setReturnedAt(null);

            $entityManager->persist($booking);
            $entityManager->flush();

            return $this->redirectToRoute('admin_bookings');
        }

        return $this->render('booking/new.html.twig', [
            'form' => $form,
        ]);
    }
}
