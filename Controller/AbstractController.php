<?php

namespace KRG\CoreBundle\Controller;

use KRG\CoreBundle\Model\ModelFactory;

class AbstractController extends \Symfony\Bundle\FrameworkBundle\Controller\AbstractController
{
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                ModelFactory::class
            ]
        );
    }
}