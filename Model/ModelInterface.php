<?php

namespace KRG\CoreBundle\Model;

use Symfony\Component\OptionsResolver\OptionsResolver;

interface ModelInterface
{
    /**
     * @param array $options
     */
    public function build(ModelView $view, array $options);

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver);
}