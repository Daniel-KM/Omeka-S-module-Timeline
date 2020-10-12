<?php declare(strict_types=1);
namespace Timeline\Service\Controller;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Timeline\Controller\TimelineController;

class TimelineControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedNamed, array $options = null)
    {
        return new TimelineController(
            $services->get('Omeka\EntityManager')
        );
    }
}
