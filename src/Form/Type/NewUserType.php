<?php

namespace App\Form\Type;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class NewUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email',EmailType::class ,[
                'label' => 'Email address',
                'required' => true,
                'constraints' => [
                    new NotBlank(null,'Email should not be blank'),
                ]
            ])
            ->add('password',PasswordType::class,[
                'label' => 'Password',
                'required' => true,
                'constraints' => [
                    new NotBlank(null,'Password should not be blank'),
                ]
            ])
            ->add('passwordRepeat',PasswordType::class,[
                'label' => 'Repeat password',
                'required' => true,
                'mapped' => false,
                'constraints' => [
                    new NotBlank(null,'Password should not be blank'),
                ]
            ])
            ->add('save',SubmitType::class,[
                'label' => 'Save user',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}