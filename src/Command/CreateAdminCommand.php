<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Create a new RideGo administrator account',
)]
class CreateAdminCommand
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $io->title('RideGo - Create Administrator');

        $email = trim($io->ask('Admin email address') ?? '');

        if (
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            strlen($email) > 180
        ) {
            $io->error('Please enter a valid email address.');
            return Command::FAILURE;
        }

        if ($this->userRepository->findOneBy(['email' => $email])) {
            $io->error('An account with this email already exists.');
            return Command::FAILURE;
        }

        $password = $io->askHidden('Admin password');

        if ($password === null || strlen($password) < 12) {
            $io->error('Password must contain at least 12 characters.');
            return Command::FAILURE;
        }

        $confirmPassword = $io->askHidden('Confirm admin password');

        if ($password !== $confirmPassword) {
            $io->error('Passwords do not match.');
            return Command::FAILURE;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $password)
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('RideGo administrator created successfully!');

        return Command::SUCCESS;
    }
}
