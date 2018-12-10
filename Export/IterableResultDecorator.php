<?php

namespace KRG\CoreBundle\Export;

use Doctrine\ORM\Internal\Hydration\IterableResult;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class IterableResultDecorator extends AbstractIterableResultDecorator
{
    /** @var array */
    protected $fields;

    /** @var PropertyAccessorInterface */
    protected $propertyAccessor;

    /**
     * IterableResultDecorator constructor.
     *
     * @param IterableResult $iterableResult
     * @param array $fields
     */
    public function __construct(IterableResult $iterableResult, array $fields)
    {
        parent::__construct($iterableResult);
        $this->fields = $fields;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    public function buildRow($item)
    {
        $row = [];
        foreach ($this->fields as $field) {
            try {
                $value = $this->propertyAccessor->getValue($item[0], $field['property_path']);
                if (is_object($value)) {
                    if (method_exists($value, 'getName')) {
                        $value = call_user_func([$value, 'getName']);
                    }
                }
                $row[] = (string) $value;
            } catch (UnexpectedTypeException $exception) {
                $row[] = null;
            }
        }

        return $row;
    }
}