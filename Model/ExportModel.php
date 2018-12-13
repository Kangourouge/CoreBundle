<?php

namespace KRG\CoreBundle\Model;

use Doctrine\ORM\Query;
use KRG\CoreBundle\Export\IterableResultDecorator;
use KRG\CoreBundle\Export\IterableResultDecoratorInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExportModel implements ModelInterface
{
    /** @var ModelFactory */
    protected $modelFactory;

    /**
     * ExportModel constructor.
     *
     * @param ModelFactory $modelFactory
     */
    public function __construct(ModelFactory $modelFactory)
    {
        $this->modelFactory = $modelFactory;
    }

    public function build(ModelView $view, array $options)
    {
        $data = [
            'sheets' => [],
            'settings' => $options['settings']
        ];

        /** @var Query $query */
        $query = $options['query'];

        $sheets = $options['sheets'];
        foreach ($sheets as &$sheet) {
            $model = $sheet['model'] ?? ExportSheetModel::class;

            $modelOptions = $sheet['options'];
            $modelOptions['iterator'] = $query->iterate();

            $sheet['tables'] = $this->modelFactory->create($model, $modelOptions);
        }
        unset($sheet);

        $data['sheets'] = $sheets;

        $view->setData($data);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['query', 'sheets', 'settings']);
        $resolver->setDefault('settings', []);
        $resolver->setAllowedTypes('query', Query::class);
        $resolver->setAllowedTypes('sheets', 'array');
        $resolver->setAllowedTypes('settings', 'array');
    }
}