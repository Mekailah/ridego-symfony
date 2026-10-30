<?php

namespace App\Controller;

use App\Entity\Motorcycle;
use App\Form\MotorcycleType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MotorcycleController extends AbstractController
{
    #[Route('/admin/motorcycles', name: 'admin_motorcycles')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $motorcycles = $entityManager
            ->getRepository(Motorcycle::class)
            ->findAll();

        return $this->render('motorcycle/index.html.twig', [
            'motorcycles' => $motorcycles,
        ]);
    }

    #[Route('/admin/motorcycles/new', name: 'admin_motorcycle_new')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $motorcycle = new Motorcycle();

        $form = $this->createForm(MotorcycleType::class, $motorcycle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($motorcycle);
            $entityManager->flush();

            return $this->redirectToRoute('admin_motorcycles');
        }

        return $this->render('motorcycle/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/admin/motorcycles/{id}/edit', name: 'admin_motorcycle_edit')]
    public function edit(
        Motorcycle $motorcycle,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(MotorcycleType::class, $motorcycle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('admin_motorcycles');
        }

        return $this->render('motorcycle/edit.html.twig', [
            'form' => $form,
            'motorcycle' => $motorcycle,
        ]);
    }

    #[Route('/admin/motorcycles/{id}/delete', name: 'admin_motorcycle_delete', methods: ['POST'])]
    public function delete(
        Motorcycle $motorcycle,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid(
            'delete'.$motorcycle->getId(),
            $request->request->get('_token')
        )) {
            $entityManager->remove($motorcycle);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_motorcycles');
    }
    }