<?php declare(strict_types=1);

namespace Timeline\Service\Controller;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Timeline\Controller\ApiController;

class ApiControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ApiController(
            $services->get('Omeka\Paginator'),
            $services->get('Omeka\ApiManager'),
            $services->get('Omeka\EntityManager')
        );
    }
}
