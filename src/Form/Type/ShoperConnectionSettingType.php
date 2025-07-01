<?php

namespace App\Form\Type;

use App\Entity\ShoperConnectionSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ShoperConnectionSettingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('restUser',TextType::class ,[
                'label' => 'Username',
                'required' => true,
                'constraints' => [
                    new NotBlank(null,'Username should not be blank'),
                ]
            ])
            ->add('restPassword',PasswordType::class,[
                'label' => 'Password',
                'required' => true,
                'attr' => [
                    'value' => $options['data'] ? $options['data']->getRestPassword() : null,
                ],
                'always_empty' => false,
                'constraints' => [
                    new NotBlank(null,'Password should not be blank'),
                ]
            ])
            ->add('shopUrl',TextType::class,[
                'label' => 'Shop url',
                'required' => true,
                'constraints' => [
                    new NotBlank(null,'Shop url should not be blank'),
                ]
            ])
            ->add('save',SubmitType::class,[
                'label' => 'Save user',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ShoperConnectionSettings::class,
        ]);
    }

}