<?php

namespace KRG\CoreBundle\Model;

use Doctrine\ORM\Query;
use KRG\CoreBundle\Export\IterableResultDecorator;
use KRG\CoreBundle\Export\IterableResultDecoratorInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExportSheetModel implements ModelInterface
{
    public function build(ModelView $view, array $options)
    {
        $decorator = $options['decorator'];

        $refClass = new \ReflectionClass($options['decorator']);

        $table = [
            'caption' => null,
            'colgroup' => range(1, count($options['fields'])),
            'thead' => [array_column($options['fields'], 'label')],
            'tbody' => $refClass->newInstance($options['iterator'], $options['fields']),
            'tfoot' => [],
            'template' => $options['template'],
        ];

        $view->setData([$table]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['iterator', 'fields']);
        $resolver->setDefault('settings', []);
        $resolver->setDefault('decorator', IterableResultDecorator::class);
        $resolver->setDefault('template', '@KRGCoreBundle/views/export/table.xml.twig');
        $resolver->setAllowedTypes('iterator', \Iterator::class);
        $resolver->setAllowedTypes('fields', 'array');
        $resolver->setAllowedTypes('decorator', 'string');
        $resolver->setAllowedTypes('template', 'string');
        $resolver->setNormalizer('decorator', function(Options $options, $decorator){

            if (!in_array(IterableResultDecoratorInterface::class, class_implements($decorator))) {
                throw new \InvalidArgumentException(sprintf('Decorator must by type of %s', IterableResultDecoratorInterface::class));
            }

            return $decorator;
        });
    }
}