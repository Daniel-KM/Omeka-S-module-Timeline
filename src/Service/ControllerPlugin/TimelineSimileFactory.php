<?php declare(strict_types=1);

namespace Timeline\Service\ControllerPlugin;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Timeline\Mvc\Controller\Plugin\TimelineSimile;

class TimelineSimileFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $name, array $options = null)
    {
        return new TimelineSimile(
            $services->get('Omeka\ApiManager')
        );
    }
}
