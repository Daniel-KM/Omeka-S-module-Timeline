<?php
namespace Timeline\Form;

use Omeka\Api\Manager as ApiManager;
use Omeka\Settings\Settings;
use Zend\Form\Form;
use Timeline\Mvc\Controller\Plugin\TimelineData;

class Config extends Form
{
    protected $api;
    protected $settings;

    public function init()
    {
        $settings = $this->getSettings();
        $properties = $this->listProperties();
        $timelines = $this->listTimelines();
        $timelineDefaults = $settings->get('timeline_defaults');

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
                'value' => $settings->get('timeline_library'),
            ],
        ]);

        $this->add([
            'name' => 'timeline_link_to_nav',
            'type' => 'Select',
            'options' => [
                'label' => 'Add secondary link', // @translate
                'info' => 'The secondary link is displayed in the menu used in items/browse.' // @translate
                    . ' ' . 'The option "Main" allows to display a main timeline.', // @translate
                'empty_option' => 'None', // @translate
                'value_options' => [
                    'browse' => 'Browse timelines', // @translate
                    'main' => 'Display main timeline', // @translate
                ],
            ],
            'attributes' => [
                'value' => $settings->get('timeline_link_to_nav'),
            ],
        ]);

        $this->add([
            'name' => 'timeline_link_to_nav_main',
            'type' => 'Select',
            'options' => [
                'label' => 'Main timeline', // @translate
                'info' => 'This parameter is used only when the previous one is "Display main timeline".', // @translate
                'empty_option' => 'None', // @translate
                'value_options' => $timelines,
            ],
            'attributes' => [
                'value' => $settings->get('timeline_link_to_nav_main'),
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
            'type' => 'Select',
            'options' => [
                'label' => 'Item Title', // @translate
                'info' => 'The title field to use when displaying an item on a timeline. Default is "dcterms:title".', // @translate
                'empty_option' => 'Select a property...', // @translate
                'value_options' => $properties,
            ],
            'attributes' => [
                'value' => $timelineDefaults['item_title'],
            ],
        ]);

        $argsFieldset->add([
            'name' => 'item_description',
            'type' => 'Select',
            'options' => [
                'label' => 'Item Description', // @translate
                'info' => 'The description field to use when displaying an item on a timeline. Default is "dcterms:description".', // @translate
                'empty_option' => 'Select a property...', // @translate
                'value_options' => $properties,
            ],
            'attributes' => [
                'value' => $timelineDefaults['item_description'],
            ],
        ]);

        $argsFieldset->add([
            'name' => 'item_date',
            'type' => 'Select',
            'options' => [
                'label' => 'Item Date', // @translate
                'info' => 'The date field to use to retrieve and display items on a timeline. Default is "dcterms:date".' // @translate
                    . ' ' . 'Items with empty value for this field will be skipped.', // @translate
                'empty_option' => 'Select a property...', // @translate
                'value_options' => $properties,
            ],
            'attributes' => [
                'value' => $timelineDefaults['item_date'],
            ],
        ]);

        $argsFieldset->add([
            'name' => 'item_date_end',
            'type' => 'Select',
            'options' => [
                'label' => 'Item End Date', // @translate
                'info' => 'If set, this field will be used to set the end of a period.' // @translate
                    . ' ' . 'If should be different from the main date.' // @translate
                    . ' ' . 'In that case, the previous field will be the start date.' // @translate
                    . ' ' . 'In all cases, it is possible to set a range in one field with a "/", like "1939-09-01/1945-05-08".', // @translate
                'empty_option' => 'None', // @translate
                'value_options' => $properties,
            ],
            'attributes' => [
                'value' => $timelineDefaults['item_date_end'],
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
            'attributes' => [
                'value' => $timelineDefaults['render_year'],
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
            'attributes' => [
                'value' => $timelineDefaults['center_date'],
            ],
        ]);

        $argsFieldset->add([
            'name' => 'viewer',
            'type' => 'Textarea',
            'options' => [
                'label' => 'Viewer', // @translate
                'info' => 'Set the default params of the viewer as json, or let empty for the included default.' // @translate
                    . ' ' . 'Currently, only "bandInfos" and "centerDate" are managed.', // @translate
            ],
            'attributes' => [
                'value' => trim(json_encode(
                    $timelineDefaults['viewer'],
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
                ), '"\''),
                'rows' => 15,
            ],
        ]);
    }

    /**
     * @param ApiManager $api
     */
    public function setApi(ApiManager $api)
    {
        $this->api = $api;
    }

    /**
     * @return ApiManager
     */
    protected function getApi()
    {
        return $this->api;
    }

    /**
     * @param Settings $settings
     */
    public function setSettings(Settings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @return Settings
     */
    protected function getSettings()
    {
        return $this->settings;
    }

    /**
     * Helper to prepare the true list of properties (not the internal ids).
     *
     * @return array
     */
    protected function listProperties()
    {
        $properties = [];
        $response = $this->getApi()->search('vocabularies');
        foreach ($response->getContent() as $vocabulary) {
            $options = [];
            foreach ($vocabulary->properties() as $property) {
                $options[] = [
                    'label' => $property->label(),
                    'value' => $property->term(),
                ];
            }
            if (!$options) {
                continue;
            }
            $properties[] = [
                'label' => $vocabulary->label(),
                'options' => $options,
            ];
        }
        return $properties;
    }

    protected function listTimelines()
    {
        $result = [];
        $response = $this->getApi()->search('timelines');
        foreach ($response->getContent() as $timeline) {
            $result[$timeline->slug()] = $timeline->title();
        }
        return $result;
    }
}
