<?php
namespace Timeline\Form;

use Omeka\Form\Element\PropertySelect;
use Timeline\Mvc\Controller\Plugin\TimelineData;
use Zend\Form\Element\Select;
use Zend\Form\Element\Text;
use Zend\Form\Fieldset;
use Zend\Form\Form;

class TimelineBlockForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][args]',
            'type' => Fieldset::class,
            'options' => [
                'label' => 'Parameters', // @translate
            ],
        ]);
        $argsFieldset = $this->get('o:block[__blockIndex__][o:data][args]');

        $argsFieldset->add([
            'name' => 'item_title',
            'type' => PropertySelect::class,
            'options' => [
                'label' => 'Item title', // @translate
                'empty_option' => 'Select a property…', // @translate
                'term_as_value' => true,
            ],
            'attributes' => [
                'required' => true,
                'class' => 'chosen-select',
            ],
        ]);

        $argsFieldset->add([
            'name' => 'item_description',
            'type' => PropertySelect::class,
            'options' => [
                'label' => 'Item description', // @translate
                'empty_option' => 'Select a property…', // @translate
                'term_as_value' => true,
            ],
            'attributes' => [
                'required' => true,
                'class' => 'chosen-select',
            ],
        ]);

        $argsFieldset->add([
            'name' => 'item_date',
            'type' => PropertySelect::class,
            'options' => [
                'label' => 'Item date', // @translate
                'empty_option' => 'Select a property…', // @translate
                'term_as_value' => true,
            ],
            'attributes' => [
                'required' => true,
                'class' => 'chosen-select',
            ],
        ]);

        $argsFieldset->add([
            'name' => 'item_date_end',
            'type' => PropertySelect::class,
            'options' => [
                'label' => 'Item end date', // @translate
                'info' => 'If set, the process will use the other date as a start date.', // @translate
                'empty_option' => 'None', // @translate
                'term_as_value' => true,
            ],
            'attributes' => [
                'class' => 'chosen-select',
            ],
        ]);

        $argsFieldset->add([
            'name' => 'render_year',
            // A radio is not possible when there are multiple timeline blocks.
            'type' => Select::class,
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

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'o:block[__blockIndex__][o:data][args]',
            'required' => false,
        ]);
    }
}
