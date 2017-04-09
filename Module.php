<?php
namespace Timeline;

use Omeka\Module\AbstractModule;

if (!defined('TIMELINE_HELPERS_DIR')) {
    define('TIMELINE_HELPERS_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR
        . 'libraries' . DIRECTORY_SEPARATOR
        . 'Timeline');
}
require_once TIMELINE_HELPERS_DIR . DIRECTORY_SEPARATOR . 'Functions.php';

/**
 * Timeline plugin class
 */
class Module extends AbstractModule
{
    protected $_hooks = [
        'initialize',
        'install',
        'upgrade',
        'uninstall',
        'uninstall_message',
        'config',
        'config_form',
        'define_acl',
        'define_routes',
        'public_head',
        'admin_head',
        'exhibit_builder_page_head',
    ];

    protected $_filters = [
        'admin_navigation_main',
        'public_navigation_main',
        'public_navigation_items',
        'response_contexts',
        'action_contexts',
        'exhibit_layouts',
        'items_browse_params',
    ];

    /**
     * @var array Options and their default values.
     */
    protected $_options = [
        // Can be 'simile' or 'knightlab'.
        'timeline_library' => 'simile',
        // Can be "browse", "main" or empty.
        'timeline_link_to_nav' => 'browse',
        'timeline_link_to_nav_main' => '',
        'timeline_defaults' => [
            // Numbers are the id of elements of a standard install of Omeka.
            'item_title' => 50,
            'item_description' => 41,
            'item_date' => 40,
            'item_date_end' => '',
            'render_year' => 'skip',
            'center_date' => '',
            'viewer' => '{}',
        ],
    ];

    /**
     * Timeline initialize hook
     */
    public function hookInitialize()
    {
        add_translation_source(dirname(__FILE__) . '/languages');
    }

    /**
     * Timeline install hook
     */
    public function hookInstall()
    {
        $sqlTimelineline = "CREATE TABLE IF NOT EXISTS `{$this->_db->prefix}timeline_timelines` (
            `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `title` TINYTEXT COLLATE utf8_unicode_ci NOT NULL,
            `description` TEXT COLLATE utf8_unicode_ci NOT NULL,
            `owner_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
            `public` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
            `featured` TINYINT(1) NOT NULL DEFAULT '0',
            `parameters` TEXT COLLATE utf8_unicode_ci NOT NULL,
            `query` TEXT COLLATE utf8_unicode_ci NOT NULL,
            `added` timestamp NOT NULL default '2000-01-01 00:00:00',
            `modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `public` (`public`),
            KEY `featured` (`featured`),
            KEY `owner_id` (`owner_id`)
        ) ENGINE=innodb DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

        $this->_db->query($sqlTimelineline);

        $this->_options['timeline_defaults'] = json_encode($this->_options['timeline_defaults']);
        $this->_installOptions();
    }

    /**
     * Timeline upgrade hook.
     *
     * Add newer upgrade checks after existing ones.
     */
    public function hookUpgrade($args)
    {
        $oldVersion = $args['old_version'];
        $newVersion = $args['new_version'];
        $db = $this->_db;

        require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'upgrade.php';
    }

    /**
     * Timeline uninstall hook
     */
    public function hookUninstall()
    {
        $sql = "DROP TABLE IF EXISTS
        `{$this->_db->prefix}timeline_timelines`";

        $this->_db->query($sql);

        // Remove old options.
        delete_option('timeline');
        delete_option('timeline_render_year');

        $this->_uninstallOptions();
    }

    /**
     * Display the uninstall message.
     */
    public function hookUninstallMessage()
    {
        $string = __('<strong>Warning</strong>: Uninstalling the Timeline plugin
          will remove all custom Timeline records.');
        echo '<p>' . $string . '</p>';
    }

    /**
     * Timeline define_acl hook
     */
    public function hookDefineAcl($args)
    {
        $acl = $args['acl'];

        $acl->addResource('Timeline_Timelines');

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

    /**
     * Timeline define_routes hook
     */
    public function hookDefineRoutes($args)
    {
        $router = $args['router'];

        $router->addRoute(
            'timelineActionRoute',
            new Zend_Controller_Router_Route(
                'timeline/timelines/:action/:id',
                [
                    'module' => 'timeline',
                    'controller' => 'timelines',
                ],
                ['id' => '\d+']
            )
        );

        $router->addRoute(
            'timelineDefaultRoute',
            new Zend_Controller_Router_Route(
                'timeline/timelines/:action',
                [
                    'module' => 'timeline',
                    'controller' => 'timelines',
                ]
            )
        );

        $router->addRoute(
            'timelineRedirectRoute',
            new Zend_Controller_Router_Route(
                'timeline',
                [
                    'module' => 'timeline',
                    'controller' => 'timelines',
                    'action' => 'browse',
                ]
            )
        );

        $router->addRoute(
            'timelinePaginationRoute',
            new Zend_Controller_Router_Route(
                'timeline/timelines/:page',
                [
                    'module' => 'timeline',
                    'controller' => 'timelines',
                    'action' => 'browse',
                    'page' => '1',
                ],
                ['page' => '\d+']
            )
        );
    }

    /**
     * Shows plugin configuration page.
     */
    public function hookConfigForm($args)
    {
        $defaults = get_option('timeline_defaults');
        $defaults = json_decode($defaults, true);
        $defaults = empty($defaults)
            // Set default parameters.
            ? $this->_options['timeline_defaults']
            // Add possible new default parameters to avoid a notice.
            : array_merge($this->_options['timeline_defaults'], $defaults);

        $view = $args['view'];
        echo $view->partial(
            'plugins/timeline-config-form.php',
            [
                'defaults' => $defaults,
            ]);
    }

    /**
     * Processes the configuration form.
     */
    public function hookConfig($args)
    {
        $post = $args['post'];
        foreach ($this->_options as $optionKey => $optionValue) {
            if (isset($post[$optionKey])) {
                if (is_array($optionValue)) {
                    $post[$optionKey] = json_encode($post[$optionKey]);
                }
                set_option($optionKey, $post[$optionKey]);
            }
        }
    }

    public function hookAdminHead($args)
    {
        $requestParams = Zend_Controller_Front::getInstance()->getRequest()->getParams();
        $module = isset($requestParams['module']) ? $requestParams['module'] : 'default';
        $controller = isset($requestParams['controller']) ? $requestParams['controller'] : 'index';
        $action = isset($requestParams['action']) ? $requestParams['action'] : 'index';
        if ($module != 'timeline' || $controller != 'timelines' || $action != 'show') {
            return;
        }
        $this->_head($args);
    }

    public function hookPublicHead($args)
    {
        $requestParams = Zend_Controller_Front::getInstance()->getRequest()->getParams();
        $module = isset($requestParams['module']) ? $requestParams['module'] : 'default';
        $controller = isset($requestParams['controller']) ? $requestParams['controller'] : 'index';
        $action = isset($requestParams['action']) ? $requestParams['action'] : 'index';
        if ($module != 'timeline' || $controller != 'timelines' || $action != 'show') {
            return;
        }
        $this->_head($args);
    }

    /**
     * Add timeline assets for exhibit pages using the timeline layout.
     */
    public function hookExhibitBuilderPageHead($args)
    {
        if (array_key_exists('timeline', $args['layouts'])) {
            $this->_head($args);
        }
    }

    /**
     * Load all assets.
     *
     * Replace queue_timeline_assets()
     */
    private function _head($args)
    {
        $library = get_option('timeline_library');
        if ($library == 'knightlab') {
            queue_css_url('//cdn.knightlab.com/libs/timeline3/latest/css/timeline.css');
            queue_js_url('//cdn.knightlab.com/libs/timeline3/latest/js/timeline.js');
            return;
        }

        // Default simile library.
        queue_css_file('timeline-timeline');

        queue_js_file('timeline-scripts');

        // Check useInternalJavascripts in config.ini.
        $config = Zend_Registry::get('bootstrap')->getResource('Config');
        $useInternalJs = isset($config->theme->useInternalJavascripts)
            ? (bool) $config->theme->useInternalJavascripts
            : false;
        $useInternalJs = isset($config->theme->useInternalAssets)
            ? (bool) $config->theme->useInternalAssets
            : $useInternalJs;

        if ($useInternalJs) {
            $timelineVariables = 'Timeline_ajax_url="' . src('simile-ajax-api.js', 'javascripts/simile/ajax-api') . '";
                Timeline_urlPrefix="' . dirname(src('timeline-api.js', 'javascripts/simile/timeline-api')) . '/";
                Timeline_parameters="bundle=true";';
            queue_js_string($timelineVariables);
            queue_js_file('timeline-api', 'javascripts/simile/timeline-api');
            queue_js_string('SimileAjax.History.enabled = false; // window.jQuery = SimileAjax.jQuery;');
        } else {
            queue_js_url('//api.simile-widgets.org/timeline/2.3.1/timeline-api.js?bundle=true');
            queue_js_string('SimileAjax.History.enabled = false; window.jQuery = SimileAjax.jQuery;');
        }
    }

    /**
     * Timeline admin_navigation_main filter.
     *
     * Adds a button to the admin's main navigation.
     *
     * @param array $nav
     * @return array
     */
    public function filterAdminNavigationMain($nav)
    {
        $nav[] = [
            'label' => __('Timelines'),
            'uri' => url('timeline'),
            'resource' => 'Timeline_Timelines',
            'privilege' => 'browse',
        ];
        return $nav;
    }

    /**
     * Timeline public_navigation_main filter.
     *
     * Adds a button to the public theme's main navigation.
     *
     * @param array $nav
     * @return array
     */
    public function filterPublicNavigationMain($nav)
    {
        $nav[] = [
            'label' => __('Timelines'),
            'uri' => url('timeline'),
        ];
        return $nav;
    }

    public function filterPublicNavigationItems($navArray)
    {
        $linkToNav = get_option('timeline_link_to_nav');
        switch ($linkToNav) {
            case 'browse':
                $navArray['Browse Timeline'] = [
                    'label' => __('Browse Timelines'),
                    'uri' => url('timeline'),
                ];
                break;
            case 'main':
                $linkToNavMain = get_option('timeline_link_to_nav_main');
                if ($linkToNavMain) {
                    $navArray['Browse Timeline'] = [
                        'label' => __('Browse Timeline'),
                        'uri' => url('timeline/timelines/show/' . $linkToNavMain),
                    ];
                }
                break;
            default:
        }
        return $navArray;
    }

    /**
     * Adds the timeline-json context to response contexts.
     */
    public function filterResponseContexts($contexts)
    {
        $contexts['timeline-json'] = [
            'suffix' => 'timeline-json',
            'headers' => ['Content-Type' => 'text/javascript'],
        ];
        return $contexts;
    }

    /**
     * Adds timeline-json context to the 'items' actions for the
     * Timeline_TimelinesController.
     */
    public function filterActionContexts($contexts, $args)
    {
        if ($args['controller'] instanceof Timeline_TimelinesController) {
            $contexts['items'][''] = 'timeline-json';
        }
        return $contexts;
    }

    /**
     * Register an exhibit layout for displaying a timeline.
     *
     * @param array $layouts Exhibit layout specs.
     * @return array
     */
    public function filterExhibitLayouts($layouts)
    {
        $layouts['timeline'] = [
            'name' => __('Timelines'),
            'description' => __('Embed a Timeline timeline.'),
        ];
        return $layouts;
    }

    /**
     * Filter items browse params.
     *
     * @param array $params
     * @return array
     */
    public function filterItemsBrowseParams($params)
    {
        // Filter the items to return only items that have a non-empty value for
        // the DC:Date or the specified field when using the timeline-json
        // context.
        $context = Zend_Controller_Action_HelperBroker::getStaticHelper('ContextSwitch')->getCurrentContext();
        if ($context != 'timeline-json') {
            return $params;
        }
        // Check if this is a request (don't filter if this a background process).
        $request = Zend_Controller_Front::getInstance()->getRequest();
        if (empty($request)) {
            return $params;
        }
        $id = (integer) $request->getParam('id');
        if (empty($id)) {
            return $params;
        }
        $timeline = $this->_db->getTable('Timeline_Timeline')->find($id);
        if (empty($timeline)) {
            return $params;
        }
        $params['advanced'][] = [
            'joiner' => 'and',
            'element_id' => $timeline->getProperty('item_date'),
            'type' => 'is not empty',
        ];
        return $params;
    }
}
