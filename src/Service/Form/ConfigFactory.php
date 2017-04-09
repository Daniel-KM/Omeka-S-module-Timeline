<?php
namespace Timeline\Service\Form;

use Timeline\Form\Config as ConfigForm;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ConfigFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $settings = $services->get('Omeka\Settings');
        $api = $services->get('Omeka\ApiManager');

        $form = new ConfigForm;
        $form->setSettings($settings);
        $form->setApi($api);
        return $form;
    }
}
