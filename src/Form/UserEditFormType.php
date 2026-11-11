<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserEditFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', TextType::class, [
                'label' => 'Full Name',
                'required' => false,
            ])
            ->add('username', TextType::class, [
                'label' => 'Username',
                'constraints' => [
                    new NotBlank(message: 'Please enter a username.'),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'constraints' => [
                    new NotBlank(message: 'Please enter an email address.'),
                ],
            ])

            ->add('roles', ChoiceType::class, [
                'label' => 'User Role',
                'choices' => [
                    'Admin' => 'ROLE_ADMIN',
                    'Staff' => 'ROLE_STAFF',
                ],
                'choice_label' => function ($choice, $key, $value) {
                    return $key;
                },
                'expanded' => false,
                'multiple' => false,
                'mapped' => false,
                'data' => in_array('ROLE_ADMIN', $builder->getData()->getRoles(), true)
                    ? 'ROLE_ADMIN'
                    : 'ROLE_STAFF',
            ])
            ->add('isActive', ChoiceType::class, [
                'label' => 'Account Status',
                'choices' => [
                    'Active' => true,
                    'Disabled' => false,
                ],
                'expanded' => false,
                'multiple' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
