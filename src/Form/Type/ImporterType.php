<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;


class ImporterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $wholesalers = $options['wholesalers'];
        $availabilities = $options['availabilities'];

        foreach ($wholesalers as $wholesaler) {
            $builder
                ->add('wholesaler_file-' . $wholesaler->getId(), FileType::class, [
                    'label' => 'CSV file: ' . $wholesaler->getName(),
                    'required' => false,
                    'constraints' => [
                        new File([
                            'maxSize' => '30M',
                        ])
                    ],
                    'attr' => [
                    ]
                ]);
        }

        $builder->add('exclude_products',TextType::class, [
            'label' => 'Exclude products',
            'required' => false,
        ]);
        $builder->add('exclude_product_status',ChoiceType::class, [
            'label' => 'Exclude product status',
            'choices' => $this->getAvailabilitiesChoices($availabilities),
            'required' => false,
            'multiple' => true,
        ]);

        $builder->add('save', SubmitType::class, [
            'label' => 'import',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'wholesalers' => [],
            'availabilities' => []
        ]);

        $resolver->setRequired(['wholesalers']);
        $resolver->setRequired(['availabilities']);
    }

    private function getAvailabilitiesChoices($availabilities)
    {
        $choices = [];

        foreach ($availabilities as $availability) {
            $choices[$availability['name']] = $availability['id'];
        }

        return $choices;
    }
}