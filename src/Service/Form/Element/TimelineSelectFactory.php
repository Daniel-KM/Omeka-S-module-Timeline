<?php
namespace Timeline\Service\Form\Element;

use Interop\Container\ContainerInterface;
use Timeline\Form\Element\TimelineSelect;
use Zend\ServiceManager\Factory\FactoryInterface;

class TimelineSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $element = new TimelineSelect;
        $element->setApiManager($services->get('Omeka\ApiManager'));
        return $element;
    }
}
