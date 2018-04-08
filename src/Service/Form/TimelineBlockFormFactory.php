<?php
namespace Timeline\Service\Form;

use Interop\Container\ContainerInterface;
use Timeline\Form\TimelineBlockForm;
use Zend\ServiceManager\Factory\FactoryInterface;

class TimelineBlockFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $translator = $services->get('MvcTranslator');
        $form = new TimelineBlockForm(null, $options);
        $form->setTranslator($translator);
        return $form;
    }
}
