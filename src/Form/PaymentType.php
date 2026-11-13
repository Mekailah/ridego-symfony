<?php

namespace App\Form;

use App\Entity\Booking;
use App\Entity\Payment;
use App\Repository\BookingRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PaymentType extends AbstractType
{
    public function __construct(
        private readonly BookingRepository $bookingRepository,
        private readonly Security $security
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $this->security->getUser();
        $bookings = $this->security->isGranted('ROLE_ADMIN')
            ? $this->bookingRepository->findAll()
            : ($user !== null
                ? $this->bookingRepository->findBy(['createdBy' => $user])
                : []);

        $builder
            ->add('booking', EntityType::class, [
                'class' => Booking::class,
                'choices' => $bookings,
                'disabled' => $options['lock_booking'],
                'choice_label' => function (Booking $booking): string {
                    return 'Booking #' . $booking->getId()
                        . ' — ' . $booking->getCustomerName()
                        . ' — ' . $booking->getMotorcycleName();
                },
                'placeholder' => 'Select a booking',
                'constraints' => [
                    new Assert\NotNull([
                        'message' => 'Please select a booking.',
                    ]),
                ],
            ])
            ->add('amount', MoneyType::class, [
                'currency' => 'PHP',
                'scale' => 2,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Payment amount is required.',
                    ]),
                    new Assert\Positive([
                        'message' => 'Payment amount must be greater than zero.',
                    ]),
                ],
            ])
            ->add('paymentMethod', ChoiceType::class, [
                'placeholder' => 'Select payment method',
                'required' => false,
                'choices' => [
                    'Cash' => 'Cash',
                    'GCash' => 'GCash',
                    'Bank Transfer' => 'Bank Transfer',
                ],
                'constraints' => [
                    new Assert\Callback(
                        function (mixed $value, ExecutionContextInterface $context): void {
                            $payment = $context->getRoot()->getData();
                            if (
                                $payment instanceof Payment
                                && $payment->getPaymentStatus() === 'Paid'
                                && ($value === null || $value === '')
                            ) {
                                $context->buildViolation(
                                    'Please select a payment method before marking this payment as Paid.'
                                )->addViolation();
                            }
                        }
                    ),
                ],
            ])
            ->add('paymentStatus', ChoiceType::class, [
                'placeholder' => 'Select payment status',
                'choices' => [
                    'Pending' => 'Pending',
                    'Paid' => 'Paid',
                    'Failed' => 'Failed',
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Please select a payment status.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Payment::class,
            'lock_booking' => false,
        ]);
        $resolver->setAllowedTypes('lock_booking', 'bool');
    }
}
