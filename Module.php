<?php
namespace Timeline;

use Omeka\Module\AbstractModule;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceLocatorInterface;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);

        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        $acl->allow(null, [\Timeline\Controller\TimelineController::class]);
    }

    public function upgrade(
        $oldVersion,
        $newVersion,
        ServiceLocatorInterface $serviceLocator
    ) {
        require_once 'data/scripts/upgrade.php';
    }
}
