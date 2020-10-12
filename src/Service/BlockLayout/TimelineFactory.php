<?php declare(strict_types=1);
namespace Timeline\Service\BlockLayout;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Timeline\Site\BlockLayout\Timeline;

class TimelineFactory implements FactoryInterface
{
    /**
     * Create the Timeline block layout service.
     *
     * @return Timeline
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Timeline(
            $services->get('ControllerPluginManager')->get('api')
        );
    }
}
