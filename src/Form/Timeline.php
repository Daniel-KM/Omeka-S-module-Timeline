<?php
namespace Timeline\Form;

use Omeka\Form\Element\Ckeditor;
use Omeka\Settings\Settings;
use Zend\Form\Form;
use Timeline\Mvc\Controller\Plugin\TimelineData;

class Timeline extends Form
{
    public function init()
    {
        $settings = $this->getSettings();

        $this->setAttribute('id', 'timeline-form');

        // $this->add([
        //     'name' => 'timeline_info',
        //     'type' => 'Fieldset',
        //     'options' => [
        //         'label' => 'About the timeline', // @translate
        //         'info' => 'Set the main metadata of the timeline.', // @translate
        //     ],
        // ]);
        // $infoFieldset = $this->get('timeline_info');

        $this->add([
            'name' => 'o:title',
            'type' => 'Text',
            'options' => [
                'label' => 'Title', // @translate
            ],
            'attributes' => [
                'required' => true,
                'placeholder' => 'Title of this timeline', // @translate
            ],
        ]);

        $this->add([
            'name' => 'o:slug',
            'type' => 'Text',
            'options' => [
                'label' => 'Slug', // @translate
            ],
            'attributes' => [
                'placeholder' => 'slug-of-this-timeline', // @translate
            ],
        ]);

        $this->add([
            'name' => 'o:description',
            'type' => Ckeditor::class,
            'options' => [
                'label' => 'Description', // @translate
            ],
            'attributes' => [
                'rows' => 15,
                'id' => 'id',
                'class' => 'media-html',
            ],
        ]);

        $this->add([
            'name' => 'o:args',
            'type' => 'Fieldset',
            'options' => [
                'label' => 'Specific Parameters', // @translate
                'info' => 'Set the specific parameters of the timeline.' // @translate
                    . ' ' . 'If not set, the defaults set in the config page will apply.', // @translate
            ],
        ]);
        $argsFieldset = $this->get('o:args');

        $argsFieldset->add([
            'name' => 'item_title',
            'type' => 'Timeline\Form\Element\PropertySelect',
            'options' => [
                'label' => 'Item Title', // @translate
                'empty_option' => 'Select a property...', // @translate
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $argsFieldset->add([
            'name' => 'item_description',
            'type' => 'Timeline\Form\Element\PropertySelect',
            'options' => [
                'label' => 'Item Description', // @translate
                'empty_option' => 'Select a property...', // @translate
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $argsFieldset->add([
            'name' => 'item_date',
            'type' => 'Timeline\Form\Element\PropertySelect',
            'options' => [
                'label' => 'Item Date', // @translate
                'empty_option' => 'Select a property...', // @translate
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $argsFieldset->add([
            'name' => 'item_date_end',
            'type' => 'Timeline\Form\Element\PropertySelect',
            'options' => [
                'label' => 'Item End Date', // @translate
                'info' => 'If set, the process will use the other date as a start date.', // @translate
                'empty_option' => 'None', // @translate
            ],
            'attributes' => [
                'required' => false,
            ],
        ]);

        $argsFieldset->add([
            'name' => 'render_year',
            'type' => 'Radio',
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
                'info' => 'Set the default params of the viewer as json, or let empty for the included default.' // @translate
                    . ' ' . 'Currently, only "bandInfos" and "centerDate" are managed.', // @translate
            ],
            'attributes' => [
                'rows' => 15,
            ],
        ]);

        // FIXME Parameters are not validated inside a fieldset, but they should.
        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'o:args',
            'required' => false,
        ]);
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
}
