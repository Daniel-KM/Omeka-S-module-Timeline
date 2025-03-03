<?php declare(strict_types=1);

namespace Timeline\Service\ControllerPlugin;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Timeline\Mvc\Controller\Plugin\TimelineSimileData;

class TimelineSimileDataFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $name, array $options = null)
    {
        $plugins = $services->get('ControllerPluginManager');
        return new TimelineSimileData(
            $services->get('Omeka\ApiManager'),
            $plugins->get('translate')
        );
    }
}
