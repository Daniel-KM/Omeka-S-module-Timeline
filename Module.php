<?php
namespace Timeline;

use Omeka\Api\Exception;
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
            // 'render_year' => \Timeline\Mvc\Controller\Plugin\TimelineData::RENDER_YEAR_DEFAULT,
            'render_year' => 'january_1',
            'center_date' => '9999-99-99',
            'viewer' => '{}',
            // The id of dcterms:date in the standard install of Omeka S.
            'item_date_id' => '7',
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
        $sql = <<<'SQL'
CREATE TABLE `timeline` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) DEFAULT NULL,
  `slug` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `is_public` tinyint(1) NOT NULL,
  `args` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(DC2Type:json_array)',
  `item_pool` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(DC2Type:json_array)',
  `created` datetime NOT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_46FEC666989D9B62` (`slug`),
  KEY `IDX_46FEC6667E3C61F9` (`owner_id`),
  CONSTRAINT `FK_46FEC6667E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $conn = $serviceLocator->get('Omeka\Connection');
        $conn->exec($sql);

        $settings = $serviceLocator->get('Omeka\Settings');
        foreach ($this->settings as $name => $value) {
            $settings->set($name, $value);
        }
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $sql = <<<'SQL'
DROP TABLE IF EXISTS timeline;
SQL;
        $conn = $serviceLocator->get('Omeka\Connection');
        $conn->exec($sql);

        $settings = $serviceLocator->get('Omeka\Settings');
        foreach ($this->settings as $name => $value) {
            $settings->delete($name);
        }
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.search.query',
            [$this, 'filterItems']
        );
        // Add the Timeline term definition.
        $sharedEventManager->attach(
            '*',
            'api.context',
            function (Event $event) {
                $context = $event->getParam('context');
                $context['o-module-timeline'] = 'https://omeka.org/s/vocabs/module/timeline#';
                $event->setParam('context', $context);
            }
        );
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

        $vocabulary = strtok($post['timeline_defaults']['item_date'], ':');
        $name = strtok(':');
        $property = $this->getServiceLocator()->get('Omeka\ApiManager')
            ->search('properties', ['vocabulary_prefix' => $vocabulary, 'local_name' => $name])
            ->getContent();
        $property = reset($property);
        $post['timeline_defaults']['item_date_id'] = (string) $property->id();

        foreach ($this->settings as $settingKey => $settingValue) {
            if (isset($post[$settingKey])) {
                $settings->set($settingKey, $post[$settingKey]);
            }
        }
    }

    public function filterItems(Event $event)
    {
        $query = $event->getParam('request')->getContent();
        if (isset($query['timeline_slug'])) {
            // See Omeka\Api\Adapter\ItemAdapter for "site_id".
            $qb = $event->getParam('queryBuilder');
            $timelineAdapter = $this->getServiceLocator()
                ->get('Omeka\ApiAdapterManager')
                ->get('timelines');
            try {
                $timeline = $timelineAdapter->findEntity(['slug' => $query['timeline_slug']]);
                $params = $timeline->getItemPool();
                if (is_array($params)) {
                    // Avoid potential infinite recursion.
                    unset($params['timeline_slug']);
                } else {
                    $params = [];
                }

                // Add the param for the date: return only if not empty.
                $itemDateId = $timeline->getArgs()['item_date_id'];
                $params['has_property'][$itemDateId] = 1;

                $itemAdapter = $event->getTarget();
                $itemAdapter->buildQuery($qb, $params);
            } catch (Exception\NotFoundException $e) {
                $timeline = null;
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
