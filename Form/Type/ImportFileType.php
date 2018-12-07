<?php

namespace KRG\CoreBundle\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use EMC\FileinputBundle\Form\Type\FileinputType;
use KRG\CoreBundle\Form\DataTransformer\CsvImportDataTransformer;
use KRG\CoreBundle\Model\ImportModel;
use KRG\CoreBundle\Model\ModelFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ImportFileType extends AbstractType
{
    /** @var SessionInterface */
    protected $session;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var ModelFactory */
    protected $modelFactory;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * ImportFileType constructor.
     *
     * @param SessionInterface $session
     * @param EntityManagerInterface $entityManager
     * @param ModelFactory $modelFactory
     * @param TranslatorInterface $translator
     */
    public function __construct(SessionInterface $session, EntityManagerInterface $entityManager, ModelFactory $modelFactory, TranslatorInterface $translator)
    {
        $this->session = $session;
        $this->entityManager = $entityManager;
        $this->modelFactory = $modelFactory;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $dataTransformer = new CsvImportDataTransformer($this->session, $options['normalizer'], $options['model']);

        $builder->add('entities', CollectionType::class, [
            'entry_type' => $options['entry_type'],
            'entry_options' => ['label' => false, 'attr' => ['class' => 'form-collection-inline']],
            'allow_add' => false,
            'allow_delete' => false,
            'label' => false,
            'attr' => ['class' => 'form-collection-import']
        ]);

        $builder->addModelTransformer($dataTransformer);
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $columns = $options['model']['columns'];
        $view->vars['columns'] = $columns;
        $view->vars['column_labels'] = array_column($columns, 'label');

        $messages = [];

        $uow = $this->entityManager->getUnitOfWork();

        foreach($uow->getScheduledEntityInsertions() as $entity) {
            $messages[] = $this->getMessage($entity, 'CREATE');
        }
        foreach($uow->getScheduledEntityUpdates() as $entity) {
            $messages[] = $this->getMessage($entity, 'UPDATE');
        }
        foreach($uow->getScheduledEntityDeletions() as $entity) {
            $messages[] = $this->getMessage($entity, 'REMOVE');
        }

        $view->vars['messages'] = $messages;
    }

    protected function getMessage($entity, $tag)
    {
        $className = substr(get_class($entity), strrpos(get_class($entity), '\\') + 1);
        return sprintf('[%s] %s "%s"', $tag, $this->translator->trans($className), (string) $entity);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['class', 'entry_type', 'normalizer']);
        $resolver->setDefault('model', null);
        $resolver->setDefault('accept', 'text/csv');
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

    public function getParent()
    {
        return FileinputType::class;
    }
}