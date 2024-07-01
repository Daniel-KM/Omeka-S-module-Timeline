<?php declare(strict_types=1);

namespace Timeline\Service\ControllerPlugin;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Timeline\Mvc\Controller\Plugin\TimelineKnightlabData;

class TimelineKnightlabDataFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $name, array $options = null)
    {
        return new TimelineKnightlabData(
            $services->get('Omeka\ApiManager')
        );
    }
}
