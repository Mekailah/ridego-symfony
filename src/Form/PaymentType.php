<?php

namespace App\Form;

use App\Entity\Payment;
use App\Entity\Booking;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class PaymentType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add('booking', EntityType::class, [
                'class' => Booking::class,
                'choice_label' => function (Booking $booking) {
                    return 'Booking #' . $booking->getId();
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
                'choices' => [
                    'Cash' => 'Cash',
                    'GCash' => 'GCash',
                    'Bank Transfer' => 'Bank Transfer',
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Please select a payment method.',
                    ]),
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
            ])

            ->add('paymentDate', DateTimeType::class, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'constraints' => [
                    new Assert\NotNull([
                        'message' => 'Payment date is required.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(
        OptionsResolver $resolver
    ): void {
        $resolver->setDefaults([
            'data_class' => Payment::class,
        ]);
    }
}
