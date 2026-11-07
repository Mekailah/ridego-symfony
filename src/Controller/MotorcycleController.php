<?php

namespace App\Controller;

use App\Entity\Motorcycle;
use App\Form\MotorcycleType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class MotorcycleController extends AbstractController
{
    #[Route('/admin/motorcycles', name: 'admin_motorcycles', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $motorcycles = $entityManager
            ->getRepository(Motorcycle::class)
            ->findAll();

            $editForms = [];

            foreach ($motorcycles as $existingMotorcycle) {
                $editForm = $this->createForm(
                    MotorcycleType::class,
                    $existingMotorcycle,
                    [
                        'action' => $this->generateUrl('admin_motorcycle_edit', [
                            'id' => $existingMotorcycle->getId(),
                        ]),
                        'method' => 'POST',
                    ]
                );

                $editForms[$existingMotorcycle->getId()] = $editForm->createView();
            }

        $motorcycle = new Motorcycle();

        $form = $this->createForm(MotorcycleType::class, $motorcycle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->saveMotorcycleImage($form->get('image')->getData(), $motorcycle);

            $entityManager->persist($motorcycle);
            $entityManager->flush();

            $this->addFlash('success', 'Motorcycle added successfully.');

            return $this->redirectToRoute('admin_motorcycles');
        }

        return $this->render('motorcycle/index.html.twig', [
            'motorcycles' => $motorcycles,
            'form' => $form->createView(),
            'editForms' => $editForms,
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
            $this->saveMotorcycleImage($form->get('image')->getData(), $motorcycle);

            $entityManager->persist($motorcycle);
            $entityManager->flush();

            $this->addFlash('success', 'Motorcycle added successfully.');

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
            // Keep the existing image if no new file is selected.
            $this->saveMotorcycleImage($form->get('image')->getData(), $motorcycle);

            $entityManager->flush();

            $this->addFlash('success', 'Motorcycle updated successfully.');

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

    private function saveMotorcycleImage(
        ?UploadedFile $imageFile,
        Motorcycle $motorcycle
    ): void {
        if ($imageFile === null) {
            return;
        }

        $extension = $imageFile->guessExtension() ?: 'bin';
        $filename = bin2hex(random_bytes(16)).'.'.$extension;

        $imageFile->move(
            $this->getParameter('kernel.project_dir').'/public/images/motorcycles',
            $filename
        );

        $motorcycle->setImage($filename);
    }
}
