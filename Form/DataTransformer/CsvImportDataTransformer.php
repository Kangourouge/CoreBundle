<?php

namespace KRG\CoreBundle\Form\DataTransformer;

use Doctrine\ORM\EntityManagerInterface;
use EMC\FileinputBundle\Entity\File;
use EMC\FileinputBundle\Entity\FileInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
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
    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var Serializer */
    protected $serialize;

    /** @var array */
    protected $model;

    /**
     * CsvImportDataTransformer constructor.
     */
    public function __construct(EntityManagerInterface $entityManager, NormalizerInterface $normalizer, array $model)
    {
        $this->entityManager = $entityManager;
        $this->serializer = new Serializer(
            [$normalizer, new ObjectNormalizer(), new ArrayDenormalizer()],
            [new CsvEncoder(), new JsonEncoder()]
        );
        $this->model = $model;
    }

    public function denormalize($data)
    {
        try {
            return $this->serializer->deserialize($data, sprintf('%s[]', $this->model['class']), 'csv', ['nodes' => $this->model['nodes']]);
        } catch (\Exception $exception) {
            throw new TransformationFailedException($exception->getMessage());
        }
    }

    public function transform($value)
    {
        if (!is_string($value) || strlen($value) === 0) {
            return null;
        }

        $filename = sys_get_temp_dir() . '/' . $value;

        if (!file_exists($filename)) {
            return [
                'file' => null,
                'name' => $value,
                'confirm' => false,
                'entities' => [],
            ];
        }

        $content = file_get_contents($filename);

        $value = [
            'file' => null,
            'name' => $value,
            'confirm' => false,
            'entities' => $this->denormalize($content),
        ];

        return $value;
    }

    public function reverseTransform($value)
    {
        $filename = sys_get_temp_dir() . '/' . $value['name'];

        if ($value['file'] instanceof UploadedFile) {
            $columns = $this->model['columns'];

            $header = array_column($columns, 'property_path');

            $csv = file_get_contents($value['file']->getPathname());
            $csv = preg_replace("/^\ *#.*\n/", '', $csv);
            $content = sprintf("%s\n%s", implode(',', $header), $csv);

            file_put_contents($filename, $content);

            $value['entities'] = $this->denormalize($content);
        }

        return $value;
    }
}