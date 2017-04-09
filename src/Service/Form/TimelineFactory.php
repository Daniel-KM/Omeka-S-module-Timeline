<?php
namespace Timeline\Service\Form;

use Timeline\Form\Timeline;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class TimelineFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $settings = $services->get('Omeka\Settings');

        $form = new Timeline;
        $form->setSettings($settings);
        return $form;
    }
}
