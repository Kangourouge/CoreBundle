<?php

namespace KRG\CoreBundle\Model;

use Symfony\Component\OptionsResolver\OptionsResolver;

class ModelFactory
{
    /**
     * @var ModelRegistry
     */
    private $modelRegistry;

    /**
     * ModelFactory constructor.
     *
     * @param ModelRegistry $modelRegistry
     */
    public function __construct(ModelRegistry $modelRegistry)
    {
        $this->modelRegistry = $modelRegistry;
    }

    /**
     * @param       $name
     * @param array $options
     * @return $this
     * @throws \Exception
     */
    public function create($name, array $options = [])
    {
        /** @var ModelInterface $model */
        $model = $this->modelRegistry->get($name);

        $resolver = new OptionsResolver();
        $model->configureOptions($resolver);
        $options = $resolver->resolve($options);

        $view = new ModelView();

        $model->build($view, $options);

        return $view->getData();
    }
}
