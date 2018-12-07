<?php

namespace KRG\CoreBundle\Model;

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

    /** @var array */
    protected static $ID_NODE = [
        'label' => '#Id',
        'name'  => 'id',
        'full_name' => 'id',
        'type' => 'identifier',
        'class' => null,
        'property_path' => 'id',
        'property' => 'id',
        'required' => false
    ];

    /**
     * ImportModel constructor.
     *
     * @param FormFactoryInterface $formFactory
     * @param TranslatorInterface $translator
     */
    public function __construct(FormFactoryInterface $formFactory, TranslatorInterface $translator)
    {
        $this->formFactory = $formFactory;
        $this->translator = $translator;
    }

    public function build(ModelView $view, array $options)
    {
        $form = $this->formFactory->create($options['type'], null, ['csrf_protection' => false]);
        $formView = $form->createView();

        $view->offsetSet('class', $form->getConfig()->getDataClass());
        $view->offsetSet('columns', $this->getColumns($formView));
        $view->offsetSet('nodes', $this->getNodes($formView));
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
    protected function getNode(FormView $view, bool $flatten = false, string $prefixLabel = '')
    {
        if (in_array('hidden', $view->vars['block_prefixes'])) {
            return [];
        }

        $label = $this->getLabel($view);

        if (count($view->children) > 0) {
            $children = [];

            if ($view->vars['compound'] && $view->parent !== null) {
                $prefixLabel = strlen($prefixLabel) > 0 ? sprintf('%s - %s', $prefixLabel, $label) : $label;
            }

            foreach($view->children as $child) {
                $children[$child->vars['name']] = $this->getNode($child, $flatten, $prefixLabel);
            }

            if ($flatten) {
                return call_user_func_array('array_merge', $children);
            }
            return $children;
        }

        $type = 'text';
        $class = null;

        if (in_array('entity', $view->vars['block_prefixes'])) {
            $type = 'entity';
            $class = $view->vars['errors']->getForm()->getConfig()->getOption('class');
        }
        else if (in_array('number', $view->vars['block_prefixes'])) {
            $type = 'number';
        }
        else if (in_array('date', $view->vars['block_prefixes'])) {
            $type = 'date';
        }
        else if (in_array('datetime', $view->vars['block_prefixes'])) {
            $type = 'datetime';
        }
        else if (in_array('choice', $view->vars['block_prefixes'])) {
            $type = 'choice';
        }
        else if (in_array('checkbox', $view->vars['block_prefixes'])) {
            $type = 'checkbox';
        }

        $propertyPath = strstr($view->vars['full_name'], '[');

        $node = [
            'label' => strlen($prefixLabel) > 0 ? sprintf('%s - %s', $prefixLabel, $label) : $label,
            'name' => $view->vars['name'],
            'full_name' => $view->vars['full_name'],
            'property_path' => preg_replace(['/\]\[/', '/[\[\]]/'], ['.', ''], $propertyPath),
            'property' => $propertyPath,
            'type' => $type,
            'class' => $class,
            'required' => $view->vars['required']
        ];

        if ($flatten) {
            return [$node];
        }

        return $node;
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

    public function getColumns(FormView $view)
    {
        return array_merge([self::$ID_NODE], $this->getNode($view, true));
    }

    public function getNodes(FormView $view)
    {
        return array_merge(['id' => self::$ID_NODE], $this->getNode($view));
    }
}