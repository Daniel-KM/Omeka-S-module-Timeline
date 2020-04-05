<?php
namespace Timeline;

return [
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
    'block_layouts' => [
        'factories' => [
            'timeline' => Service\BlockLayout\TimelineFactory::class,
            'timelineExhibit' => Service\BlockLayout\TimelineExhibitFactory::class,
        ],
    ],
    'form_elements' => [
        'invokables' => [
            Form\TimelineFieldset::class => Form\TimelineFieldset::class,
        ],
        'factories' => [
            Form\TimelineExhibitFieldset::class => Service\Form\TimelineExhibitFieldsetFactory::class,
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
            'timelineExhibitData' => Mvc\Controller\Plugin\TimelineExhibitData::class,
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
                'base_dir' => dirname(__DIR__) . '/language',
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
    'timeline' => [
        'block_settings' => [
            'timeline' => [
                'heading' => '',
                'item_title' => 'dcterms:title',
                'item_description' => 'dcterms:description',
                'item_date' => 'dcterms:date',
                'item_date_end' => '',
                // 'render_year' => \Timeline\Mvc\Controller\Plugin\TimelineData::RENDER_YEAR_DEFAULT,
                'render_year' => 'january_1',
                'center_date' => '9999-99-99',
                'viewer' => '{}',
                'query' => [],
                'library' => 'simile',
                // The id of dcterms:date in the standard install of Omeka S.
                'item_date_id' => '7',
            ],
            'timelineExhibit' => [
                'heading' => '',
                'start_date_property' => 'dcterms:date',
                'end_date_property' => '',
                'credit_property' => 'dcterms:creator',
                'scale' => 'human',
                'options' => '{}',
                'slides' => [
                    [
                        'resource' => null,
                        'type' => 'event',
                        'start_date' => '',
                        'start_display_date' => '',
                        'end_date' => '',
                        'end_display_date' => '',
                        'display_date' => '',
                        'headline' => '',
                        'html' => '',
                        'content' => '',
                        'caption' => '',
                        'credit' => '',
                        'background' => null,
                        'background_color' => '',
                        'group' => '',
                    ],
                ],
            ],
        ],
    ],
];
