<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CSVUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('file', FileType::class, [
                'label' => 'Файл для обработки',
                'row_attr' => [
                    'class' => 'input-group'
                ]
            ])
            ->add('endings', TextType::class, [
                'label' => 'Окончания для доменов',
                'row_attr' => [
                    'class' => 'input-group'
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Загрузить',
                'row_attr' => [
                    'class' => 'input-group'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}