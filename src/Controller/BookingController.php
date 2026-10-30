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

        if ($booking->isReturned()) {
            return $this->redirectToRoute('admin_bookings');
        }

        $motorcycle = $entityManager
            ->getRepository(Motorcycle::class)
            ->findOneBy([
                'name' => $booking->getMotorcycleName()
            ]);

        if ($motorcycle) {
            if ($motorcycle->getAvailableUnits() < $motorcycle->getTotalUnits()) {
                $motorcycle->setAvailableUnits(
                    $motorcycle->getAvailableUnits() + 1
                );
            }

            if ($motorcycle->getAvailableUnits() > 0) {
                $motorcycle->setStatus('Available');
            } else {
                $motorcycle->setStatus('Unavailable');
            }
        }

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

        // Prevent deducting inventory more than once
        if ($booking->getStatus() !== 'Pending') {
            return $this->redirectToRoute('admin_bookings');
        }

        $motorcycle = $entityManager
            ->getRepository(Motorcycle::class)
            ->findOneBy([
                'name' => $booking->getMotorcycleName()
            ]);

        if (!$motorcycle) {
            return $this->redirectToRoute('admin_bookings');
        }

        // Make sure a unit is available
        if ($motorcycle->getAvailableUnits() <= 0) {
            return $this->redirectToRoute('admin_bookings');
        }

        $motorcycle->setAvailableUnits(
            $motorcycle->getAvailableUnits() - 1
        );

        if ($motorcycle->getAvailableUnits() > 0) {
            $motorcycle->setStatus('Available');
        } else {
            $motorcycle->setStatus('Unavailable');
        }

        $booking->setStatus('Confirmed');

        $entityManager->flush();

        return $this->redirectToRoute('admin_bookings');
    }

    #[Route('/admin/bookings/new', name: 'admin_booking_new')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $booking = new Booking();

        $motorcycles = $entityManager
            ->getRepository(Motorcycle::class)
            ->findBy(['status' => 'Available'], ['name' => 'ASC']);

        $motorcycleChoices = [];

        foreach ($motorcycles as $motorcycle) {
            $motorcycleChoices[
                $motorcycle->getName() .
                ' (' . $motorcycle->getAvailableUnits() . ' available)'
            ] = $motorcycle->getName();
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