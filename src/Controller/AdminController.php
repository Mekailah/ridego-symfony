<?php

namespace App\Controller;

use App\Entity\Motorcycle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $motorcycles = $entityManager
            ->getRepository(Motorcycle::class)
            ->findAll();

        $totalMotorcycles = 0;
        $availableMotorcycles = 0;

        foreach ($motorcycles as $motorcycle) {
            $totalMotorcycles += $motorcycle->getTotalUnits();
            $availableMotorcycles += $motorcycle->getAvailableUnits();
        }

        return $this->render('admin/index.html.twig', [
            'totalMotorcycles' => $totalMotorcycles,
            'availableMotorcycles' => $availableMotorcycles,
        ]);
    }
}