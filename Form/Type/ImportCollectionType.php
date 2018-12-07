<?php

namespace KRG\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImportCollectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('entities', CollectionType::class, [
                'entry_type' => $options['entry_type'],
                'entry_options' => [
                    'label' => false,
                    'attr' => ['class' => 'form-inline-collection']
                ],
                'attr' => ['class' => 'form-collection-import'],
                'allow_add' => false,
                'allow_delete' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['entry_type']);
        $resolver->setAllowedTypes('entry_type', 'string');
    }
}