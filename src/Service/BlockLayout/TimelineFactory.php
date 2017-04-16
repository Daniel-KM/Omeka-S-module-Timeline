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
        $apiManager = $services->get('Omeka\ApiManager');
        $config = $services->get('Config');
        $useExternal = $config['assets']['use_externals'];

        return new Timeline($apiManager, $useExternal);
    }
}
