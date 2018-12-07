<?php

namespace KRG\CoreBundle\Export;

interface IterableResultDecoratorInterface
{
    /**
     * @param $item
     *
     * @return array
     */
    public function buildRow($item);
}