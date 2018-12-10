<?php

namespace KRG\CoreBundle\Serializer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class ImportNormalizer extends ObjectNormalizer
{
    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var array */
    protected $cache;

    /**
     * ImportNormalizer constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param array $columns
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->cache = [];
    }

    /**
     * @return array
     */
    public function getCache()
    {
        return $this->cache;
    }

    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $nodes = $context['nodes'] ?? [];
        if (is_string($data)) {
            return $this->findOrCreate($class, $data, false);
        }

        if (is_array($data)) {
            if (count($data) === 0) {
                return null;
            }

            $object = null;

            if (isset($data['id']) && is_numeric($data['id'])) {
                $object = $this->entityManager->find($class, $data['id']);
                if ($data === null) {
                    throw new \Exception('Object not found');
                }
            } else {
                $object = $this->entityManager->getClassMetadata($class)->getReflectionClass()->newInstance();
            }

            unset($data['id']);

            $classMetadata = $this->entityManager->getClassMetadata($class);

            foreach ($data as $key => $value) {

                $_nodes = $nodes[$key] ?? [];

                if (is_string($value) && strlen($value) === 0) {
                    $value = null;
                    continue;
                }

                if (is_array($value) && count(array_filter($value)) === 0) {
                    $value = null;
                    continue;
                }

                if ($classMetadata->hasAssociation($key)) {
                    $association = $classMetadata->getAssociationMapping($key);
                    if (is_string($value)) {
                        $value = $this->findOrCreate($association['targetEntity'], $value, in_array('persist', $association['cascade']));
                    } else {
                        if ($association['type'] === ClassMetadataInfo::ONE_TO_MANY || $association['type'] === ClassMetadataInfo::MANY_TO_MANY) {
                            foreach ($value as &$_value) {
                                $_value = $this->denormalize($_value, $association['targetEntity'], $format, ['nodes' => $_nodes]);
                            }
                            unset($_value);
                            $value = array_values($value);
                        } else {
                            $value = $this->denormalize($value, $association['targetEntity'], $format, ['nodes' => $_nodes]);
                        }
                    }
                }
                else if (!$classMetadata->hasField($key)) {
                    if (isset($_nodes['class'])) {
                        $value = $this->denormalize($value, $_nodes['class'], $format, ['nodes' => $_nodes]);
                    }
                }

                $this->propertyAccessor->setValue($object, $key, $value);
            }

            return $object;
        }

        return parent::denormalize($data, $class, $format, $context);
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return $this->entityManager->getMetadataFactory()->hasMetadataFor($type);
    }

    protected function findOrCreate(string $class, string $name, bool $createIfNotExists = true)
    {
        $name = trim($name);

        if (strlen($name) === 0) {
            return null;
        }

        if (isset($this->cache[$class][$name])) {
            return $this->cache[$class][$name];
        }

        $entity = $this->entityManager->getRepository($class)->findOneBy(['name' => $name]);

        if ($entity === null) {
            if (!$createIfNotExists) {
                throw new \Exception(sprintf('Entity %s "%s" not found', $class, $name));
            }
            $entity = $this->entityManager->getClassMetadata($class)->getReflectionClass()->newInstance();
            $entity->setName($name);
            $this->entityManager->persist($entity);
        }

        if (!isset($this->cache[$class])) {
            $this->cache[$class] = [];
        }

        $this->cache[$class][$name] = $entity;

        return $entity;
    }
}