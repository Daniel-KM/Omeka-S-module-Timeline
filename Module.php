<?php declare(strict_types=1);

namespace Timeline;

use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Omeka\Module\AbstractModule;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(MvcEvent $event): void
    {
        parent::onBootstrap($event);

        $this->getServiceLocator()->get('Omeka\Acl')
            ->allow(
                null,
                [
                    \Timeline\Controller\ApiController::class,
                ]);
    }

    public function upgrade(
        $oldVersion,
        $newVersion,
        ServiceLocatorInterface $services
    ): void {
        $serviceLocator = $services;
        require_once 'data/scripts/upgrade.php';
    }
}
