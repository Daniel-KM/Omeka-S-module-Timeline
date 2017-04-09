<?php
namespace Timeline\Form;

use Omeka\Api\Manager as ApiManager;
use Timeline\Mvc\Controller\Plugin\TimelineData;
use Zend\Form\Form;

class TimelineBlock extends Form
{
    /**
     * @var ApiManager
     */
    protected $apiManager;

    public function init()
    {
        $this->setAttribute('id', 'timeline-form');

        $properties = $this->listProperties();

        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][args]',
            'type' => 'Fieldset',
            'options' => [
                'label' => 'Parameters', // @translate
            ],
        ]);
        $argsFieldset = $this->get('o:block[__blockIndex__][o:data][args]');

        $argsFieldset->add([
            'name' => 'item_title',
            // TODO Use the element directly.
            'type' => 'Timeline\Form\Element\PropertySelect',
            'type' => 'Select',
            'options' => [
                'label' => 'Item Title', // @translate
                'empty_option' => 'Select a property...', // @translate
                'value_options' => $properties,
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $argsFieldset->add([
            'name' => 'item_description',
            'type' => 'Timeline\Form\Element\PropertySelect',
            'type' => 'Select',
            'options' => [
                'label' => 'Item Description', // @translate
                'empty_option' => 'Select a property...', // @translate
                'value_options' => $properties,
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $argsFieldset->add([
            'name' => 'item_date',
            'type' => 'Timeline\Form\Element\PropertySelect',
            'type' => 'Select',
            'options' => [
                'label' => 'Item Date', // @translate
                'empty_option' => 'Select a property...', // @translate
                'value_options' => $properties,
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $argsFieldset->add([
            'name' => 'item_date_end',
            'type' => 'Timeline\Form\Element\PropertySelect',
            'type' => 'Select',
            'options' => [
                'label' => 'Item End Date', // @translate
                'info' => 'If set, the process will use the other date as a start date.', // @translate
                'empty_option' => 'None', // @translate
                'value_options' => $properties,
            ],
            'attributes' => [
                'required' => false,
            ],
        ]);

        $argsFieldset->add([
            'name' => 'render_year',
            // A radio is not possible when there are multiple timeline blocks.
            'type' => 'Select',
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

        // FIXME Parameters are not validated inside a fieldset, but they should.
        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'o:block[__blockIndex__][o:data][args]',
            'required' => false,
        ]);
    }

    /**
     * {@inheritDoc}
     *
     * @param bool $onlyBase
     */
    public function populateValues($data, $onlyBase = false)
    {
        if (empty($data['o:block[__blockIndex__][o:data][args]']['viewer'])) {
            $data['o:block[__blockIndex__][o:data][args]']['viewer'] = (object) [];
        }
        if (!is_string($data['o:block[__blockIndex__][o:data][args]']['viewer'])) {
            $data['o:block[__blockIndex__][o:data][args]']['viewer'] = trim(json_encode(
                $data['o:block[__blockIndex__][o:data][args]']['viewer'],
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            ), '"\'');
        }

        parent::populateValues($data);
    }

    /**
     * Helper to get the list of properties by tag name instead of internal ids.
     */
    protected function listProperties()
    {
        $valueOptions = [];
        $response = $this->getApiManager()->search('vocabularies');
        foreach ($response->getContent() as $vocabulary) {
            $options = [];
            foreach ($vocabulary->properties() as $property) {
                $options[] = [
                    'label' => $property->label(),
                    'value' => $property->term(),
                    'attributes' => [
                        'data-term' => $property->term(),
                        'data-id' => $property->id(),
                    ],
                ];
            }
            if (!$options) {
                continue;
            }
            $valueOptions[] = [
                'label' => $vocabulary->label(),
                'options' => $options,
            ];
        }
        return $valueOptions;
    }

    /**
     * @param ApiManager $apiManager
     */
    public function setApiManager(ApiManager $apiManager)
    {
        $this->apiManager = $apiManager;
    }

    /**
     * @return ApiManager
     */
    public function getApiManager()
    {
        return $this->apiManager;
    }
}
