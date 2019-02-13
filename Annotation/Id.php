<?php

namespace KRG\CoreBundle\Annotation;
use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class Id
{
    /**
     * @var array
     * @Required
     */
    public $fields;

    public function __construct(array $values)
    {
        $this->fields = $values['value'] ?? null;
    }
}