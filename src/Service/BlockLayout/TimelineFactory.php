<?php
namespace Timeline\Service\BlockLayout;

use Interop\Container\ContainerInterface;
use Timeline\Site\BlockLayout\Timeline;
use Zend\ServiceManager\Factory\FactoryInterface;

class TimelineFactory implements FactoryInterface
{
    /**
     * Create the Timeline block layout service.
     *
     * @param ContainerInterface $serviceLocator
     * @return Timeline
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $apiManager = $serviceLocator->get('Omeka\ApiManager');
        return new Timeline($apiManager);
    }
}
