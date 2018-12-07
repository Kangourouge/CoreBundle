<?php

namespace KRG\CoreBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateTypeExtension extends AbstractTypeExtension
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
        $resolver->setDefault('widget', 'single_text');
        $resolver->setDefault('html5', false);
        $resolver->setDefault('attr', ['class' => 'datepicker', 'data-format' => 'll']);
        $resolver->setDefault('format', \IntlDateFormatter::MEDIUM);
    }

    public function getExtendedType()
    {
        return DateType::class;
    }
}
