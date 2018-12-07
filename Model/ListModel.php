<?php

namespace KRG\CoreBundle\Model;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ListModel implements ModelInterface
{
    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var FormFactoryInterface */
    protected $formFactory;

    /**
     * ListModel constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(EntityManagerInterface $entityManager, FormFactoryInterface $formFactory)
    {
        $this->entityManager = $entityManager;
        $this->formFactory = $formFactory;
    }

    public function build(ModelView $view, array $options)
    {
        /** @var Request $request */
        $request = $options['request'];

        $filter = $options['filter_data'];
        $filterForm = null;
        if ($options['filter_form'] !== null) {
            /** @var FormInterface $filterForm */
            $filterForm = $this->formFactory->create($options['filter_form'], $filter, [
                'query_builder' => $options['query_builder'],
                'action' => '?_page=1'
            ]);

            $filter = $filterForm->getData();

            $filterForm->handleRequest($request);
            if ($filterForm->isValid()) {
                $filter = $filterForm->getData();
            }
        }

        /** @var EntityRepository $repository */
        $repository = $this->entityManager->getRepository($options['data_class']);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $options['query_builder'];
        if (is_callable($options['query_builder'])) {
            $queryBuilder = call_user_func_array($options['query_builder'], [$repository, $filter]);
        }

        $page = (int) $request->get('_page', null) ?: $options['page'];

        if ($options['max_per_page'] > 0) {
            $queryBuilder
                ->setMaxResults($options['max_per_page'])
                ->setFirstResult(($page - 1) * $options['max_per_page']);
        }

        $query = $queryBuilder->getQuery();

        $query->setHydrationMode($this->getHydrationMode());

        $paginator = new Paginator($query);
        $paginator->setUseOutputWalkers(false);

        $nbPages = 1;
        if ($options['max_per_page'] > 0) {
            $nbPages = (int) ceil($paginator->count() / $options['max_per_page']);
        }

        $view->offsetSet('page', $page);
        $view->offsetSet('nbPages', $nbPages);
        $view->offsetSet('nbResults', $paginator->count());
        $view->offsetSet('rows', $paginator->getIterator());
        $view->offsetSet('filter', $filterForm ? $filterForm->createView() : null);
    }

    protected function getHydrationMode() {
        return Query::HYDRATE_ARRAY;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['request', 'data_class', 'query_builder']);
        $resolver->setDefaults([
            'page' => 1,
            'max_per_page' => 10,
            'filter_form' => null,
            'filter_data' => []
        ]);

        $resolver->setAllowedTypes('request', Request::class);
        $resolver->setAllowedTypes('data_class', 'string');
        $resolver->setAllowedTypes('query_builder', ['callable', QueryBuilder::class, \Closure::class]);
        $resolver->setAllowedTypes('filter_form', ['null', 'string', FormInterface::class]);
        $resolver->setAllowedTypes('filter_data', 'array');
        $resolver->setAllowedTypes('max_per_page', 'int');
    }
}