<?php

namespace App\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('oldPassword',PasswordType::class ,[
                'label' => 'Old password',
                'required' => true,
                'mapped' => false,
                'constraints' => [
                    new NotBlank(null,'Old password should not be blank'),
                ]
            ])
            ->add('password',PasswordType::class,[
                'label' => 'Password',
                'required' => true,
                'mapped' => false,
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
            ->add('savePassword',SubmitType::class,[
                'label' => 'Save password',
            ]);
    }

}