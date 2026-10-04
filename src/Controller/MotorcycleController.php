<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MotorcycleController extends AbstractController
{
    #[Route('/admin/motorcycles', name: 'admin_motorcycles')]
    public function index(): Response
    {
        return $this->render('motorcycle/index.html.twig');
    }

    #[Route('/admin/motorcycles/new', name: 'admin_motorcycle_new')]
    public function new(): Response
    {
        return $this->render('motorcycle/new.html.twig');
    }

    #[Route('/admin/motorcycles/{id}/edit', name: 'admin_motorcycle_edit')]
    public function edit(int $id): Response
    {
        return $this->render('motorcycle/edit.html.twig', [
            'id' => $id,
        ]);
    }
}