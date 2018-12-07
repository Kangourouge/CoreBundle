<?php

namespace KRG\CoreBundle\Form\DataTransformer;

use AppBundle\Entity\File;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class CsvImportDataTransformer implements DataTransformerInterface
{
    /** @var SessionInterface  */
    protected $session;

    /** @var Serializer */
    protected $serialize;

    /** @var array */
    protected $model;

    /**
     * CsvImportDataTransformer constructor.
     */
    public function __construct(SessionInterface $session, NormalizerInterface $normalizer, array $model)
    {
        $this->session = $session;
        $this->serializer = new Serializer(
            [$normalizer, new ObjectNormalizer(), new ArrayDenormalizer()],
            [new CsvEncoder(), new JsonEncoder()]
        );
        $this->model = $model;
    }

    public function transform($value)
    {
        return $this->deserialize();
    }

    public function deserialize() {
        $data = $this->session->get('krg.core.import', null);

        if ($data === null) {
            return null;
        }

        return $this->serializer->deserialize($data, sprintf('%s[]', $this->model['class']), 'csv', ['nodes' => $this->model['nodes']]);
    }

    public function reverseTransform($value)
    {
        if ($value instanceof File && $value->getPath() instanceof UploadedFile) {
            $columns = $this->model['columns'];

            $header = array_column($columns, 'property_path');

            $csv = file_get_contents($value->getPath()->getPathname());
            $csv = preg_replace("/^\ *#.*\n/", '', $csv);
            $data = sprintf("%s\n%s", implode(',', $header), $csv);

            $this->session->set('krg.core.import', $data);
        }

        return $this->deserialize();
    }
}