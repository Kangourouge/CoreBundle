<?php

namespace KRG\CoreBundle\Model;

use Traversable;

class ModelView implements \ArrayAccess, \IteratorAggregate, \Countable
{
    /** @var array */
    protected $data;

    /**
     * ModelView constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function count()
    {
        return count($this->data);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->data);
    }

    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
        return $this;
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }
}