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
            ->add('file', FileType::class, [
                'attr' => ['accept' => 'text/csv'],
                'required' => false
            ])
            ->add('entities', CollectionType::class, [
                'entry_type' => $options['entry_type'],
                'entry_options' => ['label' => false, 'attr' => ['class' => 'form-collection-inline']],
                'allow_add' => false,
                'allow_delete' => false,
                'prototype' => false,
                'label' => false,
                'attr' => ['class' => 'form-collection-import'],
            ])
            ->add('confirm', CheckboxType::class, [
                'required' => false
            ]);
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $columns = $options['model']['columns'];
        $view->vars['columns'] = $columns;
        $view->vars['column_labels'] = array_column($columns, 'label');

        $fd = fopen('php://memory', 'r+');
        fputcsv($fd, $view->vars['column_labels'], $this->exportSettings['csv']['delimiter'], $this->exportSettings['csv']['enclosure'], $this->exportSettings['csv']['escape_char']);
        rewind($fd);
        $view->vars['csv_prototype'] = stream_get_contents($fd);

        $content = null;

        $view->vars['attr']['class'] = 'form-import';
        if (count($view->children['entities']->vars['value']) > 0) {
            $view->vars['attr']['class'] .= ' form-import-confirm';
        }

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

        /** @var ImportNormalizer $normalizer */
        $normalizer = $options['normalizer'];
        foreach($normalizer->getExceptions() as $exception) {
            $messages['danger'][] = $exception->getMessage();
        }

        $view->vars['messages'] = $messages;
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