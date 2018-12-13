<?php

namespace KRG\CoreBundle\Export;

interface IterableResultDecoratorInterface
{
    /**
     * @param $item
     *
     * @return array
     */
    public function buildRows($item);

    /**
     * @param $item
     *
     * @return bool
     */
    public function isValid($item);
}