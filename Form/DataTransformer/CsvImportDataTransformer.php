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

    /** @var NormalizerInterface */
    protected $normalizer;

    /** @var array */
    protected $model;

    /** @var array */
    protected $settings;

    /**
     * CsvImportDataTransformer constructor.
     */
    public function __construct(EntityManagerInterface $entityManager, NormalizerInterface $normalizer, array $model, array $settings)
    {
        $this->entityManager = $entityManager;
        $this->normalizer = $normalizer;
        $this->serializer = new Serializer(
            [$this->normalizer, new ObjectNormalizer(), new ArrayDenormalizer()],
            [new CsvEncoder($settings['delimiter'], $settings['enclosure'], $settings['escape_char']), new JsonEncoder()]
        );
        $this->model = $model;
        $this->settings = $settings;
    }

    public function denormalize($data)
    {
        try {
            $entities = $this->serializer->deserialize($data, sprintf('%s[]', $this->model['class']), 'csv', ['nodes' => $this->model['nodes']]);
            $entities = array_filter($entities);
            return $entities;
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

            $fd = fopen('php://memory', 'r+');
            fputcsv($fd, $header, $this->settings['delimiter'], $this->settings['enclosure'], $this->settings['escape_char']);
            rewind($fd);

            $csv = file_get_contents($value['file']->getPathname());
            $csv = preg_replace("/^[\t\ ]*#.*\n/", '', $csv);
            $csv = preg_replace("/\n#.*/", '', $csv);

            $content = sprintf("%s\n%s", stream_get_contents($fd), $csv);

            $content = preg_replace("/(\R){2,}/", "$1", $content);

            file_put_contents($filename, $content);

            $value['entities'] = array_filter($this->denormalize($content));
        }

        return $value;
    }
}