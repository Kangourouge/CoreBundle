<?php

namespace KRG\CoreBundle\Export;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

interface ExportInterface
{
    /**
     * @param array $sheets
     *
     * @return BinaryFileResponse
     */
    public function render($filename, array $data, array $options = []);
}