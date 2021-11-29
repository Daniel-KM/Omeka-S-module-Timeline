<?php declare(strict_types=1);

namespace Timeline\Form;

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
                'name' => 'o:block[__blockIndex__][o:data][heading]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Block title', // @translate
                ],
                'attributes' => [
                    'id' => 'timeline-heading',
                    'required' => false,
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
                    'label' => 'Viewer', // @translate
                    'info' => 'Set the default params of the viewer as json, or let empty for the included default.', // @translate
                    'documentation' => 'https://gitlab.com/daniel-km/omeka-s-module-timeline#parameters-of-the-viewer',
                ],
                'attributes' => [
                    'id' => 'timeline-viewer',
                    'rows' => 5,
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][query]',
                'type' => OmekaElement\Query::class,
                'options' => [
                    'label' => 'Search pool query', // @translate
                    'info' => 'Restrict timeline to a particular subset of resources, for example a site.', // @translate
                    'query_resource_type' => null,
                    'query_partial_excludelist' => ['common/advanced-search/site'],
                ],
                'attributes' => [
                    'id' => 'timeline-query',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][library]',
                'type' => Element\Select::class,
                'options' => [
                    'label' => 'Timeline library', // @translate
                    'info' => 'Three libraries are available: the standard open source Simile Timeline, or the online Knightlab Timeline.', // @translate
                    'value_options' => [
                        'simile' => 'Simile (use internal assets)', // @translate
                        'simile_online' => 'Simile online (cannot be used on a https site)', // @translate
                        'knightlab' => 'Knightlab', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'timeline-library',
                    'required' => true,
                ],
            ]);
    }
}
