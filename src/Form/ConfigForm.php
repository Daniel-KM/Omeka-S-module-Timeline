<?php
namespace Timeline\Form;

use Omeka\Form\Element\PropertySelect;
use Timeline\Mvc\Controller\Plugin\TimelineData;
use Zend\Form\Element\Checkbox;
use Zend\Form\Element\Radio;
use Zend\Form\Element\Text;
use Zend\Form\Fieldset;
use Zend\Form\Form;
use Zend\I18n\Translator\TranslatorAwareInterface;
use Zend\I18n\Translator\TranslatorAwareTrait;

class ConfigForm extends Form implements TranslatorAwareInterface
{
    use TranslatorAwareTrait;

    public function init()
    {
        $this->add([
            'name' => 'timeline_javascript_library',
            'type' => Fieldset::class,
            'options' => [
                'label' => 'Javascript library', // @translate
            ],
        ]);

        $this->add([
            'name' => 'timeline_library',
            'type' => Radio::class,
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
            'name' => 'timeline_internal_assets',
            'type' => Checkbox::class,
            'options' => [
                'label' => 'Use Internal library for Simile', // @translate
                'info' => 'The external Simile api cannot be used on a https site, so check this box if needed.', // @translate
            ],
        ]);

        $this->add([
            'name' => 'timeline_defaults',
            'type' => Fieldset::class,
            'options' => [
                'label' => 'Default parameters', // @translate
                'info' => $this->translate('These parameters are used as default for all timelines.') // @translate
                    . ' ' . $this->translate('They can be overridden in the form of each timeline.'), // @translate
            ],
        ]);
        $argsFieldset = $this->get('timeline_defaults');

        $argsFieldset->add([
            'name' => 'item_title',
            'type' => PropertySelect::class,
            'options' => [
                'label' => 'Item title', // @translate
                'info' => 'The title field to use when displaying an item on a timeline. Default is "dcterms:title".', // @translate
                'empty_option' => 'Select a property...', // @translate
                'term_as_value' => true,
            ],
            'attributes' => [
                'class' => 'chosen-select',
                'required' => true,
            ],
        ]);

        $argsFieldset->add([
            'name' => 'item_description',
            'type' => PropertySelect::class,
            'options' => [
                'label' => 'Item description', // @translate
                'info' => 'The description field to use when displaying an item on a timeline. Default is "dcterms:description".', // @translate
                'empty_option' => 'Select a property...', // @translate
                'term_as_value' => true,
            ],
            'attributes' => [
                'class' => 'chosen-select',
                'required' => true,
            ],
        ]);

        $argsFieldset->add([
            'name' => 'item_date',
            'type' => PropertySelect::class,
            'options' => [
                'label' => 'Item date', // @translate
                'info' => $this->translate('The date field to use to retrieve and display items on a timeline. Default is "dcterms:date".') // @translate
                    . ' ' . $this->translate('Items with empty value for this field will be skipped.'), // @translate
                'empty_option' => 'Select a property...', // @translate
                'term_as_value' => true,
            ],
            'attributes' => [
                'class' => 'chosen-select',
                'required' => true,
            ],
        ]);

        $argsFieldset->add([
            'name' => 'item_date_end',
            'type' => PropertySelect::class,
            'options' => [
                'label' => 'Item end date', // @translate
                'info' => $this->translate('If set, this field will be used to set the end of a period.') // @translate
                    . ' ' . $this->translate('If should be different from the main date.') // @translate
                    . ' ' . $this->translate('In that case, the previous field will be the start date.') // @translate
                    . ' ' . $this->translate('In all cases, it is possible to set a range in one field with a "/", like "1939-09-01/1945-05-08".'), // @translate
                'empty_option' => 'None', // @translate
                'term_as_value' => true,
            ],
            'attributes' => [
                'class' => 'chosen-select',
            ],
        ]);

        $argsFieldset->add([
            'type' => Radio::class,
            'name' => 'render_year',
            'options' => [
                'label' => 'Render year', // @translate
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
            'type' => Text::class,
            'options' => [
                'label' => 'Center date', // @translate
                'info' => $this->translate('Set the default center date for the timeline.') // @translate
                    . ' ' . $this->translate('The format should be "YYYY-MM-DD".') // @translate
                    . ' ' . $this->translate('An empty value means "now", "0000-00-00" the earliest date, and "9999-99-99" the latest date.'), // @translate
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
                'info' => $this->translate('Set the default params of the viewer as json, or let empty for the included default.') // @translate
                    . ' ' . $this->translate('Currently, only "bandInfos" and "centerDate" are managed.'), // @translate
            ],
            'attributes' => [
                'rows' => 15,
            ],
        ]);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'timeline_library',
            'required' => true,
        ]);
        $inputFilter->add([
            'name' => 'timeline_internal_assets',
            'required' => false,
        ]);

        $defaultsFilter = $inputFilter->get('timeline_defaults');
        $defaultsFilter->add([
            'name' => 'item_title',
            'required' => true,
        ]);
        $defaultsFilter->add([
            'name' => 'item_description',
            'required' => true,
        ]);
        $defaultsFilter->add([
            'name' => 'item_date',
            'required' => true,
        ]);
        $defaultsFilter->add([
            'name' => 'item_date_end',
            'required' => false,
        ]);
        $defaultsFilter->add([
            'name' => 'render_year',
            'required' => false,
        ]);
        $defaultsFilter->add([
            'name' => 'center_date',
            'required' => false,
        ]);
        $defaultsFilter->add([
            'name' => 'viewer',
            'required' => false,
        ]);
    }

    protected function translate($args)
    {
        $translator = $this->getTranslator();
        return $translator->translate($args);
    }
}
