<?php

namespace KRG\CoreBundle\Export;

use Doctrine\ORM\Internal\Hydration\IterableResult;
use Symfony\Component\PropertyAccess\Exception\AccessException;
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

    public function isValid($item)
    {
        return count($item) === 1;
    }

    public function buildRows($item)
    {
        return $this->toArray($item[0]);
    }

    public function toArray($item)
    {
        $row = [];
        foreach ($this->fields as $field) {
            try {
                $value = $this->getValue($item, $field['property_path']);
                if (is_object($value)) {
                    if (method_exists($value, 'getName')) {
                        $value = call_user_func([$value, 'getName']);
                    }
                }
                else if (is_array($value)) {
                    $value = implode(',', $value);
                }
                $row[] = $value;
            } catch (\Exception $exception) {
                $row[] = null;
            }
        }

        return $row;
    }

    public function getValue($item, $propertyPath)
    {
        return $this->propertyAccessor->getValue($item, $propertyPath);
    }
}