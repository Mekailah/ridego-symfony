<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Customer;
use App\Entity\Motorcycle;
use App\Entity\Payment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        // MOTORCYCLE STATISTICS

        $motorcycles = $entityManager
            ->getRepository(Motorcycle::class)
            ->findAll();

        $totalMotorcycles = 0;
        $availableMotorcycles = 0;

        foreach ($motorcycles as $motorcycle) {
            $totalMotorcycles += $motorcycle->getTotalUnits();
            $availableMotorcycles += $motorcycle->getAvailableUnits();
        }

        // CUSTOMER STATISTICS

        $totalCustomers = $entityManager
            ->getRepository(Customer::class)
            ->count([]);

        // BOOKING STATISTICS

        $bookingRepository = $entityManager
            ->getRepository(Booking::class);

        $totalBookings = $bookingRepository->count([]);

        $pendingBookings = $bookingRepository->count([
            'status' => 'Pending',
        ]);

        $confirmedBookings = $bookingRepository->count([
            'status' => 'Confirmed',
        ]);

        $returnedBookings = $bookingRepository->count([
            'status' => 'Returned',
        ]);

        // REVENUE: COUNT PAID PAYMENTS ONLY

        $totalRevenue = $entityManager
            ->createQueryBuilder()
            ->select('COALESCE(SUM(p.amount), 0)')
            ->from(Payment::class, 'p')
            ->where('p.paymentStatus = :status')
            ->setParameter('status', 'Paid')
            ->getQuery()
            ->getSingleScalarResult();

            // GET 5 MOST RECENT BOOKINGS

            $recentBookings = $entityManager
                ->getRepository(Booking::class)
                ->findBy([], ['id' => 'DESC'], 5);
                
        // SEND STATISTICS TO DASHBOARD

        return $this->render('admin/index.html.twig', [
            'totalMotorcycles' => $totalMotorcycles,
            'availableMotorcycles' => $availableMotorcycles,
            'totalBookings' => $totalBookings,
            'pendingBookings' => $pendingBookings,
            'totalCustomers' => $totalCustomers,
            'confirmedBookings' => $confirmedBookings,
            'returnedBookings' => $returnedBookings,
            'totalRevenue' => $totalRevenue,
            'recentBookings' => $recentBookings,
        ]);
    }
}
