<?php
namespace Timeline;

return [
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
    'block_layouts' => [
        'factories' => [
            'timeline' => Service\BlockLayout\TimelineFactory::class,
        ],
    ],
    'form_elements' => [
        'invokables' => [
            'Timeline\Form\Config' => Form\Config::class,
            'Timeline\Form\TimelineBlock' => Form\TimelineBlock::class,
        ],
        'factories' => [
            'Timeline\Form\Element\PropertySelect' => Service\Form\Element\PropertySelectFactory::class,
        ],
    ],
    'controllers' => [
        'factories' => [
            'Timeline\Controller\Timeline' => Service\Controller\TimelineControllerFactory::class,
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
                        'controller' => 'Timeline',
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
];
