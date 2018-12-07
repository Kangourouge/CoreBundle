<?php

namespace KRG\CoreBundle\Export;

use Doctrine\ORM\Internal\Hydration\IterableResult;

abstract class AbstractIterableResultDecorator implements \Iterator, IterableResultDecoratorInterface
{
    /** @var IterableResult */
    protected $iterableResult;

    /**
     * IterableResultDecorator constructor.
     *
     * @param IterableResult $iterableResult
     */
    public function __construct(IterableResult $iterableResult)
    {
        $this->iterableResult = $iterableResult;
    }

    abstract public function buildRow($item);

    public function current()
    {
        $item = $this->iterableResult->current();
        return $this->buildRow($item);
    }

    public function next()
    {
        return $this->iterableResult->next();
    }

    public function key()
    {
        return $this->iterableResult->key();
    }

    public function valid()
    {
        return $this->iterableResult->valid();
    }

    public function rewind()
    {
        return $this->iterableResult->rewind();
    }
}