<?php

namespace KRG\CoreBundle\Model;

use Symfony\Component\DependencyInjection\ContainerInterface;

class ModelRegistry
{
    /** @var ContainerInterface */
    private $container;

    /** @var array */
    private $models;

    /** @var array */
    private $services;

    /**
     * ModelRegistry constructor.
     *
     * @param ContainerInterface $container
     * @param array $models
     */
    public function __construct(ContainerInterface $container, array $models)
    {
        $this->container = $container;
        $this->models = $models;
        $this->services = [];
    }

    /**
     * @param $name
     * @return mixed
     * @throws \Exception
     */
    public function get($name)
    {
        if (!in_array($name, $this->models)) {
            throw new \InvalidArgumentException(sprintf('The model "%s" is not registered with the service container.', $name));
        }

        if (!isset($this->services[$name])) {
            $this->services[$name] = $this->container->get($name);
        }

        return $this->services[$name];
    }
}
