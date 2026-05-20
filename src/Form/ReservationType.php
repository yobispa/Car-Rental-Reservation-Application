<?php

namespace App\Form;

use App\Entity\Reservation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customerFirstName', TextType::class, [
                'mapped' => false,
                'label' => 'First name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Jane',
                ],
                'constraints' => [
                    new NotBlank(message: 'Please enter your first name.'),
                ],
            ])
            ->add('customerLastName', TextType::class, [
                'mapped' => false,
                'label' => 'Last name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Smith',
                ],
                'constraints' => [
                    new NotBlank(message: 'Please enter your last name.'),
                ],
            ])
            ->add('customerEmail', EmailType::class, [
                'mapped' => false,
                'label' => 'Email',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'jane@example.com',
                ],
                'constraints' => [
                    new NotBlank(message: 'Please enter your email address.'),
                    new Email(message: 'Please enter a valid email address.'),
                ],
            ])
            ->add('customerPhone', TelType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Phone',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '(555) 123-4567',
                ],
            ])
            ->add('numberOfPersons', IntegerType::class, [
                'label' => 'Number of persons',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                ],
            ])
            ->add('startDate', DateType::class, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'label' => 'Start date',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('endDate', DateType::class, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'label' => 'End date',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }
}
