<?php declare(strict_types=1);

namespace Timeline\Form;

// TODO Common is not a dependency of the module Timeline.
use Common\Form\Element as CommonElement;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Omeka\Form\Element as OmekaElement;

class TimelineFieldset extends Fieldset
{
    public function init(): void
    {
        // TODO Checkbox normalize date or not.

        $this
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][query]',
                'type' => OmekaElement\Query::class,
                'options' => [
                    'label' => 'Search pool query', // @translate
                    'info' => 'Restrict timeline to a particular subset of resources, for example a site.', // @translate
                    'query_resource_type' => null,
                    'query_partial_excludelist' => [
                        'common/advanced-search/site',
                    ],
                ],
                'attributes' => [
                    'id' => 'timeline-query',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][item_title]',
                'type' => OmekaElement\PropertySelect::class,
                'options' => [
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
                'name' => 'o:block[__blockIndex__][o:data][item_description]',
                'type' => OmekaElement\PropertySelect::class,
                'options' => [
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
                'name' => 'o:block[__blockIndex__][o:data][item_date]',
                'type' => OmekaElement\PropertySelect::class,
                'options' => [
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
                'name' => 'o:block[__blockIndex__][o:data][item_date_end]',
                'type' => OmekaElement\PropertySelect::class,
                'options' => [
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
                'name' => 'o:block[__blockIndex__][o:data][item_metadata]',
                'type' => OmekaElement\PropertySelect::class,
                'options' => [
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
                'name' => 'o:block[__blockIndex__][o:data][group]',
                'type' => OmekaElement\PropertySelect::class,
                'options' => [
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
                'name' => 'o:block[__blockIndex__][o:data][group_default]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Default group', // @translate
                ],
                'attributes' => [
                    'id' => 'timeline-group-default',
                    'required' => false,
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][render_year]',
                // A radio is not possible when there are multiple timeline blocks.
                'type' => Element\Select::class,
                'options' => [
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
                'name' => 'o:block[__blockIndex__][o:data][center_date]',
                'type' => Element\Text::class,
                'options' => [
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
                'name' => 'o:block[__blockIndex__][o:data][eras]',
                'type' => OmekaElement\ArrayTextarea::class,
                'options' => [
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
                'name' => 'o:block[__blockIndex__][o:data][markers]',
                'type' => CommonElement\DataTextarea::class,
                'options' => [
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
                'name' => 'o:block[__blockIndex__][o:data][thumbnail_type]',
                'type' => Element\Select::class,
                'options' => [
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
                'name' => 'o:block[__blockIndex__][o:data][thumbnail_resource]',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Use the specific thumbnail of the resource if any', // @translate
                ],
                'attributes' => [
                    'id' => 'timeline-thumbnail-resource',
                    'required' => false,
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][viewer]',
                'type' => Element\Textarea::class,
                'options' => [
                    'label' => 'Timeline viewer params', // @translate
                    'info' => 'Set the default params of the viewer as json, or let empty for the included default.', // @translate
                    'documentation' => 'https://gitlab.com/daniel-km/omeka-s-module-timeline#parameters-of-the-viewer',
                ],
                'attributes' => [
                    'id' => 'timeline-viewer',
                    'rows' => 5,
                ],
            ]);
    }
}
