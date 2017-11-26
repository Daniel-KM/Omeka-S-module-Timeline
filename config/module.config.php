<?php
namespace Timeline;

return [
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
    'block_layouts' => [
        'factories' => [
            'timeline' => Service\BlockLayout\TimelineFactory::class,
        ],
    ],
    'form_elements' => [
        'invokables' => [
            Form\TimelineBlockForm::class => Form\TimelineBlockForm::class,
        ],
        'factories' => [
            Form\ConfigForm::class => Service\Form\ConfigFormFactory::class,
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\TimelineController::class => Service\Controller\TimelineControllerFactory::class,
        ],
    ],
    'controller_plugins' => [
        'invokables' => [
            'timelineData' => Mvc\Controller\Plugin\TimelineData::class,
        ],
    ],
    'router' => [
        'routes' => [
            // TODO Replace the timeline block route by a site and admin child routes?
            'timeline-block' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/timeline/:block-id/events.json',
                    'constraints' => [
                        'block-id' => '\d+',
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'Timeline\Controller',
                        'controller' => 'TimelineController',
                        'action' => 'events',
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
    'timeline' => [
        'settings' => [
            // Can be 'simile' or 'knightlab'.
            'timeline_library' => 'simile',
            'timeline_internal_assets' => false,
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
        ],
    ],
];
