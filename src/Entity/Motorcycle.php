<?php

namespace App\Entity;

use App\Repository\MotorcycleRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MotorcycleRepository::class)]
class Motorcycle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?int $pricePerDay = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column]
    #[Assert\GreaterThanOrEqual(
        value: 0,
        message: 'Total units cannot be negative.'
    )]
    private ?int $totalUnits = null;

    #[ORM\Column]
    #[Assert\GreaterThanOrEqual(
        value: 0,
        message: 'Available units cannot be negative.'
    )]
    #[Assert\LessThanOrEqual(
        propertyPath: 'totalUnits',
        message: 'Available units cannot be greater than total units.'
    )]
    private ?int $availableUnits = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPricePerDay(): ?int
    {
        return $this->pricePerDay;
    }

    public function setPricePerDay(int $pricePerDay): static
    {
        $this->pricePerDay = $pricePerDay;

        return $this;
    }

    public function getStatus(): ?string
{
    return $this->status;
}

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getTotalUnits(): ?int
    {
        return $this->totalUnits;
    }

    public function setTotalUnits(int $totalUnits): static
    {
        $this->totalUnits = $totalUnits;

        return $this;
    }

    public function getAvailableUnits(): ?int
    {
        return $this->availableUnits;
    }

    public function setAvailableUnits(int $availableUnits): static
    {
        $this->availableUnits = $availableUnits;

        return $this;
    }
}