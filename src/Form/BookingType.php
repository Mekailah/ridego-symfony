<?php

namespace App\Form;

use App\Entity\Booking;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BookingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customerName', TextType::class)
            ->add('motorcycleName', ChoiceType::class, [
                'choices' => $options['motorcycle_choices'],
                'placeholder' => 'Select Motorcycle',
            ])
            ->add('pickupDate', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('returnDate', DateType::class, [
                'widget' => 'single_text',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Booking::class,
            'motorcycle_choices' => [],
        ]);

        $resolver->setAllowedTypes('motorcycle_choices', 'array');
    }
}