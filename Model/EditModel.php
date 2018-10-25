<?php

namespace KRG\CoreBundle\Model;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditModel implements ModelInterface
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

        /** @var FormInterface $filterForm */
        $form = $this->formFactory
                            ->create($options['form_type'], $options['data'])
                            ->add('submit', SubmitType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            call_user_func($options['on_submit'], $data);

            $parameters = $options['redirect_parameters'];
            if (is_callable($parameters)) {
                $parameters = call_user_func($parameters, $data);
            }
            return new ModelPath($options['redirect_route'], $parameters);
        }

        $view->offsetSet('form', $form->createView());
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data', null);
        $resolver->setDefault( 'redirect_parameters', []);

        $resolver->setDefault('on_submit', function($data) {
            $this->entityManager->persist($data);
            $this->entityManager->flush();
        });

        $resolver->setRequired(['request', 'form_type', 'data', 'redirect_route']);
        $resolver->setAllowedTypes('request', Request::class);
        $resolver->setAllowedTypes('redirect_parameters', ['array', 'callable', \Closure::class]);
        $resolver->setAllowedTypes('form_type', ['null', 'string', FormInterface::class]);
        $resolver->setAllowedTypes('on_submit', ['callable', \Closure::class]);
    }
}