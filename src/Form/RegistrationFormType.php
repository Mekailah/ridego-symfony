<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', TextType::class, [
                'label' => 'Full Name',
                'attr' => [
                    'placeholder' => 'Enter full name',
                    'autocomplete' => 'name',
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'Please enter a full name.',
                    ),
                    new Length(
                        min: 2,
                        max: 150,
                        minMessage: 'Full name must be at least {{ limit }} characters.',
                        maxMessage: 'Full name cannot exceed {{ limit }} characters.',
                    ),
                ],
            ])
            ->add('username', null, [
                'label' => 'Username',
                'attr' => [
                    'placeholder' => 'Enter username',
                    'autocomplete' => 'nickname',
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'Please enter a username.',
                    ),
                    new Length(
                        min: 2,
                        max: 100,
                        minMessage: 'Username must be at least {{ limit }} characters.',
                        maxMessage: 'Username cannot exceed {{ limit }} characters.',
                    ),
                ],
            ])
            ->add('email', EmailType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Please enter an email address.',
                    ]),
                    new Assert\Email([
                        'message' => 'Please enter a valid email address.',
                    ]),
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue(
                        message: 'You should agree to our terms.',
                    ),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'Please enter a password',
                    ),
                    new Length(
                        min: 12,
                        minMessage: 'Your password should be at least {{ limit }} characters',
                        max: 4096,
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
