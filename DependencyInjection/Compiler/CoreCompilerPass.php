<?php
namespace KRG\CoreBundle\DependencyInjection\Compiler;

use KRG\CmsBundle\Form\FilterRegistry;
use KRG\CmsBundle\Routing\Generator\UrlGenerator;
use KRG\CmsBundle\Routing\Generator\Dumper\PhpGeneratorDumper;
use KRG\CoreBundle\Model\ModelInterface;
use KRG\CoreBundle\Model\ModelRegistry;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class CoreCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $this->processModelRegistry($container);
    }

    protected function processModelRegistry(ContainerBuilder $container)
    {
        if (false === $container->hasDefinition(ModelRegistry::class)) {
            return;
        }

        $taggedServices = array_keys($container->findTaggedServiceIds('krg.model'));
        $modelClasses = array_filter($taggedServices, function ($className) {
            return in_array(ModelInterface::class, class_implements($className));
        });
        $definition = $container->findDefinition(ModelRegistry::class);
        $definition->setArgument(1, $modelClasses);
    }

}
