<?php

namespace KRG\CoreBundle\Model;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Router;
use Symfony\Component\Templating\EngineInterface;

class ModelFactory
{
    /** @var ModelRegistry */
    private $modelRegistry;

    /** @var EngineInterface */
    private $templating;

    /** @var Router */
    private $router;

    /**
     * ModelFactory constructor.
     *
     * @param ModelRegistry $modelRegistry
     * @param EngineInterface $templating
     * @param Router $router
     */
    public function __construct(ModelRegistry $modelRegistry, EngineInterface $templating, Router $router)
    {
        $this->modelRegistry = $modelRegistry;
        $this->templating = $templating;
        $this->router = $router;
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

        $result = $model->build($view, $options);

        return $result ?: $view->getData();
    }

    public function render($view, $name, array $options = []) {
        $model = $this->create($name, $options);

        if ($model instanceof Response) {
            return $model;
        }

        if ($model instanceof ModelPath) {
            return new RedirectResponse($this->router->generate($model->offsetGet('route'), $model->offsetGet('parameters')));
        }

        return $this->templating->renderResponse($view, $model);
    }
}
