<?php declare(strict_types=1);

namespace Timeline;

return [
    'service_manager' => [
        'factories' => version_compare(\Omeka\Module::VERSION, '4.2', '<')
            // Override theme factory to inject module pages and block templates.
            // Copied in BlockPlus, Reference, Timeline.
            ? ['Omeka\Site\ThemeManager' => Service\ThemeManagerFactory::class]
            : [],
    ],
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
    'view_helpers' => [
        'factories' => [
            'timeline' => Service\ViewHelper\TimelineFactory::class,
        ],
    ],
    'page_templates' => [
    ],
    'block_templates' => [
        'timeline' => [
            'timeline-simile' => 'Simile (use internal assets)', // @translate
            'timeline-simile-online' => 'Simile online (use online js/css)', // @translate
            'timeline-knightlab' => 'Knightlab', // @translate
        ],
    ],
    'block_layouts' => [
        'factories' => [
            'timeline' => Service\BlockLayout\TimelineFactory::class,
            'timelineExhibit' => Service\BlockLayout\TimelineExhibitFactory::class,
        ],
    ],
    'resource_page_block_layouts' => [
        'invokables' => [
            'timeline' => Site\ResourcePageBlockLayout\Timeline::class,
            'timelineKnightlab' => Site\ResourcePageBlockLayout\TimelineKnightlab::class,
        ],
    ],
    'form_elements' => [
        'invokables' => [
            Form\SettingsFieldset::class => Form\SettingsFieldset::class,
            Form\TimelineFieldset::class => Form\TimelineFieldset::class,
        ],
        'factories' => [
            Form\TimelineExhibitFieldset::class => Service\Form\TimelineExhibitFieldsetFactory::class,
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\ApiController::class => Service\Controller\ApiControllerFactory::class,
        ],
    ],
    'controller_plugins' => [
        'factories' => [
            'timelineExhibitData' => Service\ControllerPlugin\TimelineExhibitDataFactory::class,
            'timelineKnightlabData' => Service\ControllerPlugin\TimelineKnightlabDataFactory::class,
            'timelineSimileData' => Service\ControllerPlugin\TimelineSimileDataFactory::class,
        ],
    ],
    'router' => [
        'routes' => [
            'api' => [
                'child_routes' => [
                    // The deprecated route "timeline-block" (for url "/timeline/:block-id/events.json") was removed in 3.4.22.
                    'timeline' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            // The block id may be an item set id.
                            'route' => '/timeline[/:block-id]',
                            'constraints' => [
                                'block-id' => '(?:b|r)?\d+',
                            ],
                            'defaults' => [
                                'controller' => Controller\ApiController::class,
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
                'type' => \Laminas\I18n\Translator\Loader\Gettext::class,
                'base_dir' => dirname(__DIR__) . '/language',
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
    'timeline' => [
        // Main settings are used for resource page block layout.
        'settings' => [
            'timeline_library' => 'knightlab',
            'timeline_item_title' => 'default',
            'timeline_item_description' => 'default',
            'timeline_item_date' => 'dcterms:date',
            'timeline_item_date_va' => null,
            'timeline_item_date_end' => null,
            'timeline_item_date_end_va' => null,
            'timeline_item_metadata' => [],
            'timeline_group' => null,
            'timeline_group_va' => null,
            'timeline_group_default' => '',
            'timeline_render_year' => 'january_1',
            'timeline_center_date' => '9999-99-99',
            'timeline_eras' => [],
            'timeline_markers' => [],
            'timeline_thumbnail_type' => 'medium',
            'timeline_thumbnail_resource' => true,
            'timeline_viewer' => '{}',
            'timeline_link_to_self' => false,
        ],
        'block_settings' => [
            'timeline' => [
                'query' => [],
                'item_title' => 'default',
                'item_description' => 'default',
                'item_date' => 'dcterms:date',
                'item_date_va' => null,
                'item_date_end' => null,
                'item_date_end_va' => null,
                'item_metadata' => [],
                'group' => null,
                'group_va' => null,
                'group_default' => '',
                'render_year' => 'january_1',
                'center_date' => '9999-99-99',
                'eras' => [],
                'markers' => [],
                'thumbnail_type' => 'medium',
                'thumbnail_resource' => true,
                'viewer' => '{}',
                'link_to_self' => false,
            ],
            'timelineExhibit' => [
                'start_date_property' => 'dcterms:date',
                'end_date_property' => null,
                'credit_property' => 'dcterms:creator',
                'item_metadata' => [],
                'group' => null,
                'group_default' => '',
                'scale' => 'human',
                'eras' => [],
                'markers' => [],
                'options' => '{}',
                'link_to_self' => false,
                // 'spreadsheet' => null,
                'slides' => [
                    [
                        'resource' => null,
                        'asset' => null,
                        'external' => null,
                        'type' => 'event',
                        'start_date' => '',
                        'start_display_date' => '',
                        'end_date' => '',
                        'end_display_date' => '',
                        'display_date' => '',
                        'headline' => '',
                        'html' => '',
                        'caption' => '',
                        'credit' => '',
                        // 'alt_text' => '',
                        'background_resource' => null,
                        'background_asset' => null,
                        'background_external' => null,
                        'background_color' => '',
                        'group' => '',
                    ],
                ],
            ],
        ],
    ],
];
