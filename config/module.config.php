<?php
return [
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
    'block_layouts' => [
        'factories' => [
            'timeline' => 'Timeline\Service\BlockLayout\TimelineFactory',
        ],
    ],
    'form_elements' => [
        'invokables' => [
            'Timeline\Form\Config' => 'Timeline\Form\Config',
            'Timeline\Form\TimelineBlock' => 'Timeline\Form\TimelineBlock',
        ],
        'factories' => [
            'Timeline\Form\Element\PropertySelect' => 'Timeline\Service\Form\Element\PropertySelectFactory',
        ],
    ],
    'controllers' => [
        'factories' => [
            'Timeline\Controller\Timeline' => 'Timeline\Service\Controller\TimelineControllerFactory',
        ],
    ],
    'controller_plugins' => [
        'invokables' => [
            'timelineData' => 'Timeline\Mvc\Controller\Plugin\TimelineData',
        ],
    ],
    'router' => [
        'routes' => [
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
