<?php

namespace App\Entity;

use App\Repository\PaymentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
class Payment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column(length: 50)]
    private ?string $paymentMethod = null;

    #[ORM\Column(length: 30)]
    private ?string $paymentStatus = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $paymentDate = null;

    #[ORM\ManyToOne(inversedBy: 'payments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Booking $booking = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getPaymentStatus(): ?string
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(string $paymentStatus): static
    {
        $this->paymentStatus = $paymentStatus;

        return $this;
    }

    public function getPaymentDate(): ?\DateTimeImmutable
    {
        return $this->paymentDate;
    }

    public function setPaymentDate(\DateTimeImmutable $paymentDate): static
    {
        $this->paymentDate = $paymentDate;

        return $this;
    }

    public function getBooking(): ?Booking
    {
        return $this->booking;
    }

    public function setBooking(?Booking $booking): static
    {
        $this->booking = $booking;

        return $this;
    }

    #[Assert\Callback]
    public function validateBookingTotal(
        ExecutionContextInterface $context
    ): void {
        $booking = $this->getBooking();

        if (
            $booking === null ||
            $this->getPaymentStatus() !== 'Paid' ||
            $this->getAmount() === null
        ) {
            return;
        }

        $bookingTotal = $booking->getTotalAmount();

        // Older bookings do not have a calculated total.
        if ($bookingTotal === null) {
            $context->buildViolation(
                'This booking has no rental total. Please update the booking first.'
            )
                ->atPath('booking')
                ->addViolation();

            return;
        }

        // Compare monetary values in cents.
        $toCents = static function (string $amount): int {
            $parts = explode('.', $amount, 2);

            $whole = (int) $parts[0];
            $fraction = (int) str_pad(
                substr($parts[1] ?? '', 0, 2),
                2,
                '0'
            );

            return $whole * 100 + $fraction;
        };

        // Prevent more than one Paid payment per booking.
        foreach ($booking->getPayments() as $existingPayment) {
            if (
                $existingPayment === $this ||
                (
                    $this->getId() !== null &&
                    $existingPayment->getId() === $this->getId()
                )
            ) {
                continue;
            }

            if ($existingPayment->getPaymentStatus() === 'Paid') {
                $context->buildViolation(
                    'This booking has already been fully paid.'
                )
                    ->atPath('booking')
                    ->addViolation();

                return;
            }
        }

        // Require the exact booking total.
        if (
            $toCents($this->getAmount()) !==
            $toCents($bookingTotal)
        ) {
            $context->buildViolation(
                'Full payment is required. Amount must be exactly ₱'
                . number_format((float) $bookingTotal, 2)
            )
                ->atPath('amount')
                ->addViolation();
        }
    }
}
