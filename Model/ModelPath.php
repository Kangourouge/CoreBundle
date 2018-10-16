<?php

namespace KRG\CoreBundle\Model;

class ModelPath extends ModelView
{
    public function __construct($route, array $parameters = [])
    {
        parent::__construct([
            'route' => $route,
            'parameters' => $parameters
        ]);
    }
}