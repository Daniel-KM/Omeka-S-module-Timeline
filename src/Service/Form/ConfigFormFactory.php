<?php
namespace Timeline\Service\Form;

use Timeline\Form\ConfigForm;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class ConfigFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $translator = $services->get('MvcTranslator');
        $form = new ConfigForm(null, $options);
        $form->setTranslator($translator);
        return $form;
    }
}
