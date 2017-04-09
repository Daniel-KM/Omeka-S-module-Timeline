<?php
namespace Timeline;

use Omeka\Module\AbstractModule;
use Timeline\Form\Config as ConfigForm;
use Zend\EventManager\Event;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\Mvc\Controller\AbstractController;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Renderer\PhpRenderer;
use Omeka\Permissions\Assertion\OwnsEntityAssertion;

class Module extends AbstractModule
{
    /**
     * Settings and their default values.
     *
     * @var array
     */
    protected $settings = [
        // Can be 'simile' or 'knightlab'.
        'timeline_library' => 'simile',
        // Can be "browse", "main" or empty.
        'timeline_link_to_nav' => 'browse',
        'timeline_link_to_nav_main' => '',
        'timeline_defaults' => [
            'item_title' => 'dcterms:title',
            'item_description' => 'dcterms:description',
            'item_date' => 'dcterms:date',
            'item_date_end' => '',
            'render_year' => 'skip',
            'center_date' => '',
            'viewer' => '{}',
        ],
    ];

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);
        $this->addAclRules();
    }

    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $conn = $serviceLocator->get('Omeka\Connection');
        $conn->exec('
CREATE TABLE IF NOT EXISTS `timeline` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` TINYTEXT COLLATE utf8_unicode_ci NOT NULL,
    `description` TEXT COLLATE utf8_unicode_ci NOT NULL,
    `owner_id` INT(10) UNSIGNED NOT NULL DEFAULT 0,
    `public` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `featured` TINYINT(1) NOT NULL DEFAULT 0,
    `parameters` TEXT COLLATE utf8_unicode_ci NOT NULL,
    `query` TEXT COLLATE utf8_unicode_ci NOT NULL,
    `added` timestamp NOT NULL default "2000-01-01 00:00:00",
    `modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `public` (`public`),
    KEY `featured` (`featured`),
    KEY `owner_id` (`owner_id`)
) ENGINE=innodb DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ');

        $settings = $serviceLocator->get('Omeka\Settings');
        foreach ($this->settings as $name => $value) {
            $settings->set($name, $value);
        }
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $conn = $serviceLocator->get('Omeka\Connection');
        $conn->exec('
DROP TABLE IF EXISTS timeline;
        ');

        $settings = $serviceLocator->get('Omeka\Settings');
        foreach ($this->settings as $name => $value) {
            $settings->delete($name);
        }
    }

    public function getConfigForm(PhpRenderer $renderer)
    {
        $services = $this->getServiceLocator();
        $formElementManager = $services->get('FormElementManager');
        $form = $formElementManager->get(ConfigForm::class);

        // Currently, Omeka S doesn't allow to display fieldsets in config form.
        $vars = [];
        $vars['form'] = $form;
        return $renderer->render('timeline/module/config.phtml', $vars);
    }

    public function handleConfigForm(AbstractController $controller)
    {
        $serviceLocator = $this->getServiceLocator();
        $settings = $serviceLocator->get('Omeka\Settings');

        $post = $controller->getRequest()->getPost()->toArray();

        foreach ($this->settings as $settingKey => $settingValue) {
            if (isset($post[$settingKey])) {
                $settings->set($settingKey, $post[$settingKey]);
            }
        }
    }

    /**
     * Add ACL rules for this module.
     */
    protected function addAclRules()
    {
        $acl = $this->getServiceLocator()->get('Omeka\Acl');

        // Similar than items or item sets from Omeka\Service\AclFactory.
        $acl->allow(
            null,
            [
                'Timeline\Controller\Admin\Timeline',
                'Timeline\Controller\Site\Timeline',
            ]
        );
        $acl->allow(
            null,
            'Timeline\Api\Adapter\TimelineAdapter',
            [
                'search',
                'read',
            ]
        );
        $acl->allow(
            null,
            'Timeline\Entity\Timeline',
            'read'
        );

        $acl->allow(
            'researcher',
            'Timeline\Controller\Admin\Timeline',
            [
                'index',
                'search',
                'browse',
                'show',
                'show-details',
                'sidebar-select',
            ]
        );

        $acl->allow(
            'author',
            'Timeline\Controller\Admin\Timeline',
            [
                'add',
                'edit',
                'delete',
                'index',
                'search',
                'browse',
                'show',
                'show-details',
                'sidebar-select',
            ]
        );
        $acl->allow(
            'author',
            'Timeline\Api\Adapter\TimelineAdapter',
            [
                'create',
                'update',
                'delete',
            ]
        );
        $acl->allow(
            'author',
            'Timeline\Entity\Timeline',
            [
                'create',
            ]
        );
        $acl->allow(
            'author',
            'Timeline\Entity\Timeline',
            [
                'update',
                'delete',
            ],
            new OwnsEntityAssertion
        );

        $acl->allow(
            'reviewer',
            'Timeline\Controller\Admin\Timeline',
            [
                'add',
                'edit',
                'delete',
                'index',
                'search',
                'browse',
                'show',
                'show-details',
                'sidebar-select',
            ]
        );
        $acl->allow(
            'reviewer',
            'Timeline\Api\Adapter\TimelineAdapter',
            [
                'create',
                'update',
                'delete',
            ]
        );
        $acl->allow(
            'reviewer',
            'Timeline\Entity\Timeline',
            [
                'create',
                'update',
            ]
        );
        $acl->allow(
            'reviewer',
            'Timeline\Entity\Timeline',
            [
                'delete',
            ],
            new OwnsEntityAssertion
        );

        $acl->allow(
            'editor',
            'Timeline\Controller\Admin\Timeline',
            [
                'add',
                'edit',
                'delete',
                'index',
                'search',
                'browse',
                'show',
                'show-details',
                'sidebar-select',
            ]
        );
        $acl->allow(
            'editor',
            'Timeline\Api\Adapter\TimelineAdapter',
            [
                'create',
                'update',
                'delete',
            ]
        );
        $acl->allow(
            'editor',
            'Timeline\Entity\Timeline',
            [
                'create',
                'update',
                'delete',
            ]
        );
    }
}
