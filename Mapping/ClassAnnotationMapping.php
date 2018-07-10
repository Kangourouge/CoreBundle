<?php

namespace KRG\CoreBundle\Mapping;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;

class ClassAnnotationMapping
{
    /**
     * @param string $annotationName Label of the annotation
     * @param array $namespaces Array of namespaces to filter on
     * @return array
     */
    static function getAnnotationForNamespace(string $annotationName, array $namespaces)
    {
        if (empty($namespaces)) {
            throw new \Exception('namespaces must contain at least one namespace');
        }

        $classes = get_declared_classes();
        $classes = array_filter($classes, function ($element) use ($namespaces) {
            foreach ($namespaces as $namespace) {
                if (substr($element, 0, strlen($namespace)) === $namespace) {
                    return $element;
                }
            }
        });

        $classesAnnotation = [];
        $annotationReader = new AnnotationReader();
        foreach ($classes as $class) {
            $annotation = $annotationReader->getClassAnnotation(new \ReflectionClass($class), $annotationName);
            if ($annotation) {
                $classesAnnotation[$class] = $annotation;
            }
        }

        return $classesAnnotation;
    }
}