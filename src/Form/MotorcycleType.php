<?php

namespace App\Form;

use App\Entity\Motorcycle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

class MotorcycleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('pricePerDay', IntegerType::class)
            ->add('totalUnits', IntegerType::class)
            ->add('availableUnits', IntegerType::class)
            ->add('image', FileType::class, [
                'label' => 'Motorcycle Image',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'accept' => 'image/jpeg,image/png,image/webp',
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Please upload a JPG, PNG, or WebP image.',
                        'maxSizeMessage' => 'The image must not exceed 5 MB.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Motorcycle::class,
        ]);
    }
}
