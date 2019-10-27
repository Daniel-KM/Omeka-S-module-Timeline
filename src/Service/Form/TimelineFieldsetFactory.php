<?php
namespace Timeline\Service\Form;

use Interop\Container\ContainerInterface;
use Timeline\Form\TimelineFieldset;
use Zend\ServiceManager\Factory\FactoryInterface;

class TimelineFieldsetFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $translator = $services->get('MvcTranslator');
        $form = new TimelineFieldset(null, $options);
        $form->setTranslator($translator);
        return $form;
    }
}
