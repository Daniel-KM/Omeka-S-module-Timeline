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
     * @param ContainerInterface $services
     * @return Timeline
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $controllerPluginManager = $services->get('ControllerPluginManager');
        return new Timeline(
            $services->get('FormElementManager'),
            $services->get('Config')['timeline']['block_settings']['timeline'],
            $controllerPluginManager->get('api')
        );
    }
}
