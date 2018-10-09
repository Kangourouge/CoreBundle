<?php

namespace KRG\CoreBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateTimeTypeExtension extends AbstractTypeExtension
{
    /** @var string */
    private $locale;

    /**
     * DateTypeExtension constructor.
     *
     * @param string $locale
     */
    public function __construct(string $locale)
    {
        $this->locale = $locale;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $formatter = new \IntlDateFormatter(
            $this->locale,
            \IntlDateFormatter::LONG,
            \IntlDateFormatter::SHORT
        );

        $resolver->setDefault('widget', 'single_text');
        $resolver->setDefault('html5', false);
        $resolver->setDefault('attr', ['class' => 'datepicker', 'data-format' => 'lll']);
        $resolver->setDefault('format', $formatter->getPattern());
    }

    public function getExtendedType()
    {
        return DateTimeType::class;
    }
}
