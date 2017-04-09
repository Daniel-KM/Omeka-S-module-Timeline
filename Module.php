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

        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        $acl->allow(
            null,
            ['Timeline\Api\Adapter\TimelineAdapter'],
            ['search', 'read']
        );

        // All everyone access to browse, show, and items.
        $acl->allow(null, 'Timelines',
            ['show', 'browse', 'items']);
        $acl->allow('researcher', 'Timelines',
            'showNotPublic');
        $acl->allow('contributor', 'Timelines',
            ['add', 'editSelf', 'querySelf', 'itemsSelf', 'deleteSelf', 'showNotPublic']);
        $acl->allow(['super', 'admin', 'contributor', 'researcher'], 'Timelines',
            ['edit', 'query', 'items', 'delete'], new Omeka_Acl_Assert_Ownership);

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
}
