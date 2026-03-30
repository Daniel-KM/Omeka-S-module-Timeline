<?php declare(strict_types=1);

namespace Timeline\Form;

// TODO Common is not a dependency of the module Timeline.
use Common\Form\Element as CommonElement;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Omeka\Form\Element as OmekaElement;

class SettingsFieldset extends Fieldset
{
    protected $label = 'Timeline (for resource blocks)'; // @translate

    protected $elementGroups = [
        'timeline' => 'Timeline (for resource blocks)', // @translate
    ];

    public function init(): void
    {
        // TODO Checkbox normalize date or not.

        $this
            ->setAttribute('id', 'timeline')
            ->setOption('element_groups', $this->elementGroups)

            ->add([
                'name' => 'timeline_library',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'element_group' => 'timeline',
                    'label' => 'Library', // @translate
                    'value_options' => [
                        'knightlab' => 'Knightlab TimelineJS', // @translate
                        'simile' => 'Simile Timeline', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'timeline-library',
                    'required' => false,
                ],
            ])
            ->add([
                'name' => 'timeline_item_title',
                'type' => CommonElement\OptionalPropertySelect::class,
                'options' => [
                    'element_group' => 'timeline',
                    'label' => 'Item title', // @translate
                    'empty_option' => '',
                    'term_as_value' => true,
                    'prepend_value_options' => [
                        'default' => 'Automatic', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'timeline-item-title',
                    'required' => false,
                    'class' => 'chosen-select',
                    'data-placeholder' => 'Select a property…', // @translate
                ],
            ])
            ->add([
                'name' => 'timeline_item_description',
                'type' => CommonElement\OptionalPropertySelect::class,
                'options' => [
                    'element_group' => 'timeline',
                    'label' => 'Item description', // @translate
                    'empty_option' => '',
                    'term_as_value' => true,
                    'prepend_value_options' => [
                        'default' => 'Automatic', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'timeline-item-description',
                    'required' => false,
                    'class' => 'chosen-select',
                    'data-placeholder' => 'Select a property…', // @translate
                ],
            ])
            ->add([
                'name' => 'timeline_item_date',
                'type' => CommonElement\OptionalPropertySelect::class,
                'options' => [
                    'element_group' => 'timeline',
                    'label' => 'Item date', // @translate
                    'empty_option' => '',
                    'term_as_value' => true,
                ],
                'attributes' => [
                    'id' => 'timeline-item-date',
                    'required' => true,
                    'class' => 'chosen-select',
                    'data-placeholder' => 'Select a property…', // @translate
                ],
            ])
            ->add([
                'name' => 'timeline_item_date_va',
                'type' => CommonElement\OptionalPropertySelect::class,
                'options' => [
                    'element_group' => 'timeline',
                    'label' => 'Item date: value annotation property', // @translate
                    'info' => 'If set, the date will be extracted from the value annotation of the item date property above.', // @translate
                    'empty_option' => '',
                    'term_as_value' => true,
                ],
                'attributes' => [
                    'id' => 'timeline-item-date-va',
                    'required' => false,
                    'class' => 'chosen-select',
                    'data-placeholder' => 'Select a property…', // @translate
                ],
            ])
            ->add([
                'name' => 'timeline_item_date_end',
                'type' => CommonElement\OptionalPropertySelect::class,
                'options' => [
                    'element_group' => 'timeline',
                    'label' => 'Item end date', // @translate
                    'info' => 'If set, the process will use the other date as a start date.', // @translate
                    'empty_option' => '',
                    'term_as_value' => true,
                ],
                'attributes' => [
                    'id' => 'timeline-item-date-end',
                    'class' => 'chosen-select',
                    'data-placeholder' => 'Select a property…', // @translate
                ],
            ])
            ->add([
                'name' => 'timeline_item_date_end_va',
                'type' => CommonElement\OptionalPropertySelect::class,
                'options' => [
                    'element_group' => 'timeline',
                    'label' => 'Item end date: value annotation property', // @translate
                    'info' => 'If set, the end date will be extracted from the value annotation of the item end date property above.', // @translate
                    'empty_option' => '',
                    'term_as_value' => true,
                ],
                'attributes' => [
                    'id' => 'timeline-item-date-end-va',
                    'required' => false,
                    'class' => 'chosen-select',
                    'data-placeholder' => 'Select a property…', // @translate
                ],
            ])
            ->add([
                'name' => 'timeline_item_metadata',
                'type' => CommonElement\OptionalPropertySelect::class,
                'options' => [
                    'element_group' => 'timeline',
                    'label' => 'Metadata to append for custom timeline', // @translate
                    'empty_option' => '',
                    'term_as_value' => true,
                    'prepend_value_options' => [
                        'resource_class' => 'Resource class', // @translate
                        'resource_class_label' => 'Resource class label', // @translate
                        'resource_template_label' => 'Resource template', // @translate
                        'owner_name' => 'Owner', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'timeline-item-metadata',
                    'required' => false,
                    'multiple' => true,
                    'class' => 'chosen-select',
                    'data-placeholder' => 'Select a metadata…', // @translate
                ],
            ])
            ->add([
                'name' => 'timeline_group',
                'type' => CommonElement\OptionalPropertySelect::class,
                'options' => [
                    'element_group' => 'timeline',
                    'label' => 'Metadata to use as group', // @translate
                    'empty_option' => '',
                    'term_as_value' => true,
                    'prepend_value_options' => [
                        'resource_class' => 'Resource class', // @translate
                        'resource_class_label' => 'Resource class label', // @translate
                        'resource_template_label' => 'Resource template', // @translate
                        'owner_name' => 'Owner', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'timeline-group',
                    'required' => false,
                    'multiple' => false,
                    'class' => 'chosen-select',
                    'data-placeholder' => 'Select a metadata…', // @translate
                ],
            ])
            ->add([
                'name' => 'timeline_group_va',
                'type' => CommonElement\OptionalPropertySelect::class,
                'options' => [
                    'element_group' => 'timeline',
                    'label' => 'Group: value annotation property', // @translate
                    'info' => 'If set, the group will be extracted from the value annotation of the group property above.', // @translate
                    'empty_option' => '',
                    'term_as_value' => true,
                ],
                'attributes' => [
                    'id' => 'timeline-group-va',
                    'required' => false,
                    'class' => 'chosen-select',
                    'data-placeholder' => 'Select a property…', // @translate
                ],
            ])
            ->add([
                'name' => 'timeline_group_default',
                'type' => Element\Text::class,
                'options' => [
                    'element_group' => 'timeline',
                    'label' => 'Default group', // @translate
                ],
                'attributes' => [
                    'id' => 'timeline-group-default',
                    'required' => false,
                ],
            ])
            ->add([
                'name' => 'timeline_render_year',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'element_group' => 'timeline',
                    'label' => 'Render year', // @translate
                    'info' => 'When a date is a single year, like "1066", the value should be interpreted to be displayed on the timeline.', // @translate
                    'value_options' => [
                        'january_1' => 'Pick first January', // @translate
                        'july_1' => 'Pick first July', // @translate
                        'full_year' => 'Mark entire year', // @translate
                        'skip' => 'Skip the resource', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'timeline-render-year',
                ],
            ])
            ->add([
                'name' => 'timeline_center_date',
                'type' => Element\Text::class,
                'options' => [
                    'element_group' => 'timeline',
                    'label' => 'Center date', // @translate
                    'info' => 'Set the default center date for the timeline. The format should be "YYYY-MM-DD". An empty value means "now", "0000-00-00" the earliest date, and "9999-99-99" the latest date.', // @translate
                ],
                'validators' => [
                    ['name' => 'Date'],
                ],
                'attributes' => [
                    'id' => 'timeline-center-date',
                ],
            ])
            ->add([
                'name' => 'timeline_eras',
                'type' => OmekaElement\ArrayTextarea::class,
                'options' => [
                    'element_group' => 'timeline',
                    'label' => 'Eras/Periods', // @translate
                    'info' => 'Write one era by line like "Summer 2024 = 2024-06-20/2024-09-21". Year can be set alone. Require Knightlab.', // @ŧranslate
                    'as_key_value' => true,
                ],
                'attributes' => [
                    'id' => 'timeline-eras',
                    'placeholder' => '',
                    'rows' => 5,
                ],
            ])
            ->add([
                'name' => 'timeline_markers',
                'type' => CommonElement\DataTextarea::class,
                'options' => [
                    'element_group' => 'timeline',
                    'label' => 'Markers for well-known or extra events', // @translate
                    'info' => 'Write one markers by line like "Night of the 4th August = 1789-08-04 = Abolition of feudalism in France". Year can be set alone. Require Knightlab.', // @ŧranslate
                    // Important: these options should be set in the block layout too.
                    'data_options' => [
                        'heading' => null,
                        'dates' => null,
                        'body' => null,
                    ],
                ],
                'attributes' => [
                    'id' => 'timeline-markers',
                    'placeholder' => '',
                    'rows' => 5,
                ],
            ])
            ->add([
                'name' => 'timeline_thumbnail_type',
                'type' => CommonElement\OptionalSelect::class,
                'options' => [
                    'element_group' => 'timeline',
                    'label' => 'Thumbnail to use', // @translate
                    'value_options' => [
                        'square' => 'Square', // @translate
                        'medium' => 'Medium', // @translate
                        'large' => 'Large', // @translate
                        'original' => 'Original (not recommended)', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'timeline-thumbnail-type',
                    'required' => true,
                ],
            ])
            ->add([
                'name' => 'timeline_thumbnail_resource',
                'type' => Element\Checkbox::class,
                'options' => [
                    'element_group' => 'timeline',
                    'label' => 'Use the specific thumbnail of the resource if any', // @translate
                ],
                'attributes' => [
                    'id' => 'timeline-thumbnail-resource',
                    'required' => false,
                ],
            ])
            ->add([
                'name' => 'timeline_viewer',
                'type' => Element\Textarea::class,
                'options' => [
                    'element_group' => 'timeline',
                    'label' => 'Timeline viewer params', // @translate
                    'info' => 'Set the default params of the viewer as json, or let empty for the included default.', // @translate
                    'documentation' => 'https://gitlab.com/daniel-km/omeka-s-module-timeline#parameters-of-the-viewer',
                ],
                'attributes' => [
                    'id' => 'timeline-viewer',
                    'rows' => 5,
                ],
            ])
            ->add([
                'name' => 'timeline_link_to_self',
                'type' => Element\Checkbox::class,
                'options' => [
                    'element_group' => 'timeline',
                    'label' => 'Open links in current browse tab', // @translate
                ],
                'attributes' => [
                    'id' => 'timeline-link-to-self',
                ],
            ])
        ;
    }
}
