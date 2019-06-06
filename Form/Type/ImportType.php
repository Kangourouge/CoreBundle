<?php

namespace KRG\CoreBundle\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use EMC\FileinputBundle\Form\Type\FileinputType;
use KRG\CoreBundle\Form\DataTransformer\CsvImportDataTransformer;
use KRG\CoreBundle\Model\ImportModel;
use KRG\CoreBundle\Model\ModelFactory;
use KRG\CoreBundle\Serializer\ImportNormalizer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ImportType extends AbstractType
{
    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var ModelFactory */
    protected $modelFactory;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var array */
    protected $exportSettings;

    /**
     * ImportType constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param ModelFactory $modelFactory
     * @param TranslatorInterface $translator
     * @param array $exportSettings
     */
    public function __construct(EntityManagerInterface $entityManager, ModelFactory $modelFactory, TranslatorInterface $translator, array $exportSettings)
    {
        $this->entityManager = $entityManager;
        $this->modelFactory = $modelFactory;
        $this->translator = $translator;
        $this->exportSettings = $exportSettings;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $dataTransformer = new CsvImportDataTransformer($this->entityManager, $options['normalizer'], $options['model'], $this->exportSettings['csv']);
        $builder->addModelTransformer($dataTransformer);

        $builder
            ->add('entities', CollectionType::class, [
                'entry_type' => $options['entry_type'],
                'entry_options' => ['label' => false, 'label_format' => '%name%'],
                'allow_add' => false,
                'allow_delete' => false,
                'prototype' => false,
                'label' => false
            ])
            ->add('file', FileType::class, [
                'attr' => ['accept' => 'text/csv'],
                'required' => false
            ])
            ->add('confirm', CheckboxType::class, [
                'required' => false
            ]);
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $columns = $options['model']['columns'];

        $this->finishEntitiesView($view, $form, $options, $columns);

        $isEmpty = $view->children['entities']->count() === 0;

        $view->children['submit']->vars['label'] = sprintf('form.import.%s', $isEmpty ? 'import' : 'submit');

        $view->vars['kangourouge'] = array_merge($view->vars['kangourouge'] ?? [], [
            'import_columns'    => $columns,
            'import_messages'   => $this->getMessages($options['normalizer']),
            'import_csv_header'  => $this->getCSVHeader($columns),
            'import_requirements' => $this->exportSettings['csv']
        ]);
    }

    public function finishEntitiesView(FormView $view, FormInterface $form, array $options, array $columns)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $identifierColumns = array_filter($columns, function($column) {
            return $column['identifier'] ?? false;
        });

        foreach($view->children['entities'] as $child) {
            $_identifiers = [];

            foreach ($identifierColumns as $column) {
                $_identifiers[$column['label']] = $propertyAccessor->getValue($child->vars['value'], $column['property_path']);
            }

            $child->vars['kangourouge'] = array_merge($child->vars['kangourouge'] ?? [], [
                'import_extra_fields' => $_identifiers
            ]);
        }
    }

    protected function getCSVHeader(array $columns)
    {
        $labels = array_column($columns, 'label');

        $fd = fopen('php://memory', 'r+');
        fputcsv($fd, $labels, $this->exportSettings['csv']['delimiter'], $this->exportSettings['csv']['enclosure'], $this->exportSettings['csv']['escape_char']);
        rewind($fd);
        $content = stream_get_contents($fd);
        fclose($fd);

        return $content;
    }

    protected function getMessages(ImportNormalizer $normalizer) {

        $messages = [
            'info' => [],
            'warning' => [],
            'danger' => []
        ];

        $uow = $this->entityManager->getUnitOfWork();

        foreach($uow->getScheduledEntityInsertions() as $entity) {
            $messages['info'][] = $this->getMessage($entity);
        }
        foreach($uow->getScheduledEntityUpdates() as $entity) {
            $messages['info'][] = $this->getMessage($entity);
        }
        foreach($uow->getScheduledEntityDeletions() as $entity) {
            $messages['warning'][] = $this->getMessage($entity);
        }

        foreach($normalizer->getExceptions() as $exception) {
            $messages['danger'][] = $exception->getMessage();
        }

        return $messages;
    }

    protected function getMessage($entity)
    {
        $className = substr(get_class($entity), strrpos(get_class($entity), '\\') + 1);
        return sprintf('%s "%s"', $this->translator->trans($className), (string) $entity);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['class', 'entry_type', 'normalizer']);
        $resolver->setDefault('model', null);
        $resolver->setDefault('accept', 'text/csv');
        $resolver->setDefault('error_bubbling', true);
        $resolver->setDefault('label_format', 'form.import.%name%');
        $resolver->setAllowedTypes('class', 'string');
        $resolver->setAllowedTypes('model', ['array', 'null']);
        $resolver->setAllowedTypes('entry_type', [FormInterface::class, 'string']);
        $resolver->setAllowedTypes('normalizer', NormalizerInterface::class);
        $resolver->setNormalizer('model', function(Options $options){
            return $this->modelFactory->create(ImportModel::class, [
                'type' => $options['entry_type']
            ]);
        });
    }

    public function getBlockPrefix()
    {
        return 'import';
    }
}