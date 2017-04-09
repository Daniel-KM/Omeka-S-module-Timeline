<?php
return [
    'api_adapters' => [
        'invokables' => [
            'timelines' => 'Timeline\Api\Adapter\TimelineAdapter',
        ],
    ],
    'entity_manager' => [
        'mapping_classes_paths' => [
            __DIR__ . '/../src/Entity',
        ],
        'proxy_paths' => [
            __DIR__ . '/../data/doctrine-proxies',
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
    'block_layouts' => [
        'invokables' => [
            'timeline' => 'Timeline\Site\BlockLayout\Timeline',
        ],
    ],
    'navigation_links' => [
        'invokables' => [
            'browseTimelines' => 'Timeline\Site\Navigation\Link\BrowseTimelines',
        ],
    ],
    'form_elements' => [
        'invokables' => [
            'Timeline\Form\Config' => 'Timeline\Form\Config',
            'Timeline\Form\Timeline' => 'Timeline\Form\Timeline',
        ],
        'factories' => [
            'Timeline\Form\Element\PropertySelect' => 'Timeline\Service\Form\Element\PropertySelectFactory',
            'Timeline\Form\Element\TimelineSelect' => 'Timeline\Service\Form\Element\TimelineSelectFactory',
        ],
    ],
    'navigation' => [
        'AdminModule' => [
            [
                'label' => 'Timelines', // @translate
                'route' => 'admin/timeline',
                'resource' => 'Timeline\Controller\Admin\Timeline',
                'privilege' => 'browse',
                'pages' => [
                    [
                        'route' => 'admin/timeline',
                        'visible' => false,
                    ],
                    [
                        'route' => 'admin/timeline/slug',
                        'visible' => false,
                    ],
                    [
                        'route' => 'admin/add-timeline',
                        'visible' => false,
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'invokables' => [
            'Timeline\Controller\Admin\Timeline' => 'Timeline\Controller\Admin\TimelineController',
            'Timeline\Controller\Site\Timeline' => 'Timeline\Controller\Site\TimelineController',
        ],
    ],
    'controller_plugins' => [
        'invokables' => [
            'timelineData' => 'Timeline\Mvc\Controller\Plugin\TimelineData',
        ],
    ],
    'router' => [
        'routes' => [
            'admin' => [
                'child_routes' => [
                    'timeline' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/timeline',
                            'defaults' => [
                                '__NAMESPACE__' => 'Timeline\Controller\Admin',
                                'controller' => 'Timeline',
                                'action' => 'browse',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'slug' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route' => '/:timeline-slug[/:action]',
                                    'constraints' => [
                                        'timeline-slug' => '[a-zA-Z0-9_-]+',
                                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                    ],
                                    'defaults' => [
                                        'action' => 'show',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'add-timeline' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/add-timeline',
                            'defaults' => [
                                '__NAMESPACE__' => 'Timeline\Controller\Admin',
                                'controller' => 'Timeline',
                                'action' => 'add',
                            ],
                        ],
                    ],
                ],
            ],
            'site' => [
                'child_routes' => [
                    'timeline' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/timeline',
                            'defaults' => [
                                '__NAMESPACE__' => 'Timeline\Controller\Site',
                                'controller' => 'Timeline',
                                'action' => 'browse',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'slug' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route' => '/:timeline-slug',
                                    'defaults' => [
                                        'action' => 'show',
                                    ],
                                    'constraints' => [
                                        'timeline-slug' => '[a-zA-Z0-9_-]+',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => __DIR__ . '/../language',
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
];
