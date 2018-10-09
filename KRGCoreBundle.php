<?php

namespace KRG\CoreBundle;

use KRG\CoreBundle\DependencyInjection\Compiler\CoreCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class KRGCoreBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new CoreCompilerPass());
    }
}
