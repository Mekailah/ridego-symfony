<?php

namespace App\Form;

use App\Entity\Payment;
use App\Entity\Booking;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PaymentType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder

            // ================= BOOKING =================

            ->add('booking', EntityType::class, [
                'class' => Booking::class,

                'choice_label' => function (Booking $booking) {
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

            // ================= AMOUNT =================

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

            // ================= PAYMENT METHOD =================

            ->add('paymentMethod', ChoiceType::class, [
                'placeholder' => 'Select payment method',

                'required' => false,

                'choices' => [
                    'Cash' => 'Cash',
                    'GCash' => 'GCash',
                    'Bank Transfer' => 'Bank Transfer',
                ],

                // Payment method is required only when Paid.
                'constraints' => [
                    new Assert\Callback(
                        function (
                            mixed $value,
                            ExecutionContextInterface $context
                        ): void {
                            $payment = $context->getRoot()->getData();

                            if (
                                $payment instanceof Payment &&
                                $payment->getPaymentStatus() === 'Paid' &&
                                ($value === null || $value === '')
                            ) {
                                $context->buildViolation(
                                    'Please select a payment method before marking this payment as Paid.'
                                )->addViolation();
                            }
                        }
                    ),
                ],
            ])

            // ================= PAYMENT STATUS =================

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

        // Payment Date is intentionally not included.
        // PaymentController will set it automatically.
    }

    public function configureOptions(
        OptionsResolver $resolver
    ): void {
        $resolver->setDefaults([
            'data_class' => Payment::class,
        ]);
    }
}
