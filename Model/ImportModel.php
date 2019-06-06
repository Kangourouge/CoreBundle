<?php

namespace KRG\CoreBundle\Model;

use Doctrine\Common\Annotations\Reader;
use KRG\CoreBundle\Annotation\Id;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class ImportModel implements ModelInterface
{
    /** @var FormFactoryInterface */
    protected $formFactory;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var Reader */
    protected $reader;

    /**
     * ImportModel constructor.
     *
     * @param FormFactoryInterface $formFactory
     * @param TranslatorInterface $translator
     * @param Reader $reader
     */
    public function __construct(FormFactoryInterface $formFactory, TranslatorInterface $translator, Reader $reader)
    {
        $this->formFactory = $formFactory;
        $this->translator = $translator;
        $this->reader = $reader;
    }

    public function build(ModelView $view, array $options)
    {
        $form = $this->formFactory->create($options['type'], null, ['csrf_protection' => false]);
        $formView = $form->createView();

        $className = $form->getConfig()->getDataClass();

        $annotation = $this->reader->getClassAnnotation(new \ReflectionClass($className), Id::class);
        $identifiers = $annotation !== null ? $annotation->fields : ['id'];

        $columns = $this->getColumns($formView, $identifiers);
        $nodes = $this->getNodes($formView, $identifiers);

        $view->offsetSet('class', $form->getConfig()->getDataClass());
        $view->offsetSet('columns', $columns);
        $view->offsetSet('nodes', $nodes);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('type');
        $resolver->setAllowedTypes('type', [FormTypeInterface::class, 'string']);
    }

    /**
     * @param FormView $view
     * @param bool $flatten
     *
     * @return array
     */
    protected function getNode(FormView $view, array $identifiers, bool $flatten = false, string $prefixLabel = '')
    {
        if (in_array('hidden', $view->vars['block_prefixes'])) {
            return [];
        }

        $label = $this->getLabel($view);

        if (count($view->children) > 0 && !in_array('choice', $view->vars['block_prefixes'])) {
            $children = [];

            if ($view->vars['compound'] && $view->parent !== null) {
                $prefixLabel = strlen($prefixLabel) > 0 ? sprintf('%s - %s', $prefixLabel, $label) : $label;
            }

            foreach($view->children as $child) {
                $children[$child->vars['name']] = $this->getNode($child, $identifiers, $flatten, $prefixLabel);
            }

            if ($flatten) {
                return call_user_func_array('array_merge', $children);
            }
            return $children;
        }

        $property = strstr($view->vars['full_name'], '[');
        $propertyPath = preg_replace(['/\]\[/', '/[\[\]]/'], ['.', ''], $property);
        if (in_array($propertyPath, $identifiers)) {
            return [];
        }

        $node = [
            'label' => strlen($prefixLabel) > 0 ? sprintf('%s - %s', $prefixLabel, $label) : $label,
            'name' => $view->vars['name'],
            'full_name' => $view->vars['full_name'],
            'property_path' => $propertyPath,
            'property' => $property,
            'type' => $this->getType($view),
            'required' => $view->vars['required']
        ];

        if ($node['type'] === 'entity') {
            $node = array_merge($node, [
                'class' => $view->vars['errors']->getForm()->getConfig()->getOption('class'),
            ]);
        }

        if ($node['type'] === 'choice') {
            $node = array_merge($node, [
                'choices' => array_column($view->vars['choices'], 'data', 'label'),
                'multiple' => $view->vars['multiple']
            ]);
        }

        if ($flatten) {
            return [$node];
        }

        return $node;
    }

    protected function getType(FormView $view)
    {
        $types = ['entity', 'number', 'date', 'datetime', 'choice', 'checkbox'];

        foreach($types as $type) {
            if (in_array($type, $view->vars['block_prefixes'])) {
                return $type;
            }
        }

        return 'text';
    }

    protected function getLabel(FormView $view) {
        $label = $view->vars['label'];
        if ($label === null) {
            $label = $view->vars['name'];
            if ($view->vars['label_format'] !== null) {
                $label = preg_replace('/%name%/', $view->vars['name'], $view->vars['label_format']);
            }
        }

        $transLabel = $this->translator->trans($label, [], $view->vars['translation_domain']);

        if ($transLabel === $label) {
            $transLabel = ucfirst(preg_replace('/(?<=\\w)(?=[A-Z])/'," $1", $label));
        }

        return $transLabel;
    }

    public function getIdentifiers(array $identifiers)
    {
        $nodes = [];

        foreach($identifiers as $identifier) {
            $nodes[] = [
                'label' => '#' . $identifier,
                'name'  => $identifier,
                'full_name' => $identifier,
                'type' => 'identifier',
                'class' => null,
                'property_path' => $identifier,
                'property' => $identifier,
                'required' => false
            ];
        }

        return $nodes;
    }

    public function getColumns(FormView $view, array $identifiers)
    {
        $nodes = $this->getNode($view, $identifiers, true);
        $this->addIdentifiers($nodes, $identifiers);

        return $nodes;
    }

    public function getNodes(FormView $view, array $identifiers)
    {
        $nodes = $this->getNode($view, [], false);

        return $nodes;
    }

    protected function addIdentifiers(array &$nodes, array $identifiers)
    {
        $properties = array_column($identifiers, 'property_path');

        $identifiers = array_reverse($identifiers);

        foreach($identifiers as $identifier) {
            if (!in_array($identifier, $properties)) {
                array_unshift($nodes, [
                    'label' => '#' . $identifier,
                    'name'  => $identifier,
                    'full_name' => $identifier,
                    'type' => 'text',
                    'class' => null,
                    'property_path' => $identifier,
                    'property' => $identifier,
                    'identifier' => true,
                    'required' => false
                ]);
            }
        }

        return $nodes;
    }
}