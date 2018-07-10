<?php

namespace KRG\CoreBundle\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;
use Doctrine\Common\Annotations\Annotation\Required;

/**
 * @Annotation
 * @Target({"ALL"})
 */
class IsGranted
{
    /**
     * @var array
     * @Required
     */
    public $value;

    public function __construct(array $values)
    {
        $this->value = [];
        $roles = $this->toArray($values['value']);

        foreach ($roles as $role => $action) {
            if (is_numeric($role)){
                $this->value[$action] = "CRUD";
            } else {
                $this->value[$role] = $action;
            }
        }
    }

    private function toArray($value)
    {
        if (!is_array($value)){
            return [$value];
        }
        return $value;
    }
}