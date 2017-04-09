<?php
namespace Timeline\Form;

use Zend\Form\Form;
use Timeline\Mvc\Controller\Plugin\TimelineData;

class Config extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'timeline_library',
            'type' => 'Radio',
            'options' => [
                'label' => 'Timeline library', // @translate
                'info' => 'Two libraries are available: the standard open source Simile Timeline, or the online Knightlab Timeline.', // @translate
                'value_options' => [
                    'simile' => 'Simile',
                    'knightlab' => 'Knightlab',
                ],
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'timeline_defaults',
            'type' => 'Fieldset',
            'options' => [
                'label' => 'Default Parameters', // @translate
                'info' => 'These parameters are used as default for all timelines.' // @translate
                    . ' ' . 'They can be overridden in the form of each timeline.', // @translate
            ],
        ]);
        $argsFieldset = $this->get('timeline_defaults');

        $argsFieldset->add([
            'name' => 'item_title',
            'type' => 'Timeline\Form\Element\PropertySelect',
            'options' => [
                'label' => 'Item Title', // @translate
                'info' => 'The title field to use when displaying an item on a timeline. Default is "dcterms:title".', // @translate
                'empty_option' => 'Select a property...', // @translate
            ],
        ]);

        $argsFieldset->add([
            'name' => 'item_description',
            'type' => 'Timeline\Form\Element\PropertySelect',
            'options' => [
                'label' => 'Item Description', // @translate
                'info' => 'The description field to use when displaying an item on a timeline. Default is "dcterms:description".', // @translate
                'empty_option' => 'Select a property...', // @translate
            ],
        ]);

        $argsFieldset->add([
            'name' => 'item_date',
            'type' => 'Timeline\Form\Element\PropertySelect',
            'options' => [
                'label' => 'Item Date', // @translate
                'info' => 'The date field to use to retrieve and display items on a timeline. Default is "dcterms:date".' // @translate
                    . ' ' . 'Items with empty value for this field will be skipped.', // @translate
                'empty_option' => 'Select a property...', // @translate
            ],
        ]);

        $argsFieldset->add([
            'name' => 'item_date_end',
            'type' => 'Timeline\Form\Element\PropertySelect',
            'options' => [
                'label' => 'Item End Date', // @translate
                'info' => 'If set, this field will be used to set the end of a period.' // @translate
                    . ' ' . 'If should be different from the main date.' // @translate
                    . ' ' . 'In that case, the previous field will be the start date.' // @translate
                    . ' ' . 'In all cases, it is possible to set a range in one field with a "/", like "1939-09-01/1945-05-08".', // @translate
                'empty_option' => 'None', // @translate
            ],
        ]);

        $argsFieldset->add([
            'type' => 'Radio',
            'name' => 'render_year',
            'options' => [
                'label' => 'Render Year', // @translate
                'info' => 'When a date is a single year, like "1066", the value should be interpreted to be displayed on the timeline.', // @translate
                'value_options' => [
                    TimelineData::RENDER_YEAR_JANUARY_1 => 'Pick first January', // @translate
                    TimelineData::RENDER_YEAR_JULY_1 => 'Pick first July', // @translate
                    TimelineData::RENDER_YEAR_FULL_YEAR => 'Mark entire year', // @translate
                    TimelineData::RENDER_YEAR_SKIP => 'Skip the record', // @translate
                ],
            ],
        ]);

        $argsFieldset->add([
            'name' => 'center_date',
            'type' => 'Text',
            'options' => [
                'label' => 'Center Date', // @translate
                'info' => 'Set the default center date for the timeline.' // @translate
                    . ' ' . 'The format should be "YYYY-MM-DD".' // @translate
                    . ' ' . 'An empty value means "now", "0000-00-00" the earliest date, and "9999-99-99" the latest date.', // @translate
            ],
            'validators' => [
                ['name' => 'Date'],
            ],
        ]);

        $argsFieldset->add([
            'name' => 'viewer',
            'type' => 'Textarea',
            'options' => [
                'label' => 'Viewer', // @translate
                'info' => 'Set the default params of the viewer as raw json, or let empty for the included default.' // @translate
                    . ' ' . 'Currently, only "bandInfos" and "centerDate" are managed.', // @translate
            ],
            'attributes' => [
                'rows' => 15,
            ],
        ]);
    }

    /**
     * {@inheritDoc}
     *
     * @param bool $onlyBase
     */
    public function populateValues($data, $onlyBase = false)
    {
        if (empty($data['timeline_defaults']['viewer'])) {
            $data['timeline_defaults']['viewer'] = [];
        }
        $data['timeline_defaults']['viewer'] = trim(json_encode(
            $data['timeline_defaults']['viewer'],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        ), '"\'');

        parent::populateValues($data);
    }
}
