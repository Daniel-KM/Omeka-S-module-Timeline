<?php
namespace Timeline\Form;

use Omeka\Form\Element\PropertySelect;
use Timeline\Mvc\Controller\Plugin\TimelineData;
use Zend\Form\Element;
use Zend\Form\Fieldset;
use Zend\Form\Form;
use Zend\I18n\Translator\TranslatorAwareInterface;
use Zend\I18n\Translator\TranslatorAwareTrait;

class TimelineBlockForm extends Form implements TranslatorAwareInterface
{
    use TranslatorAwareTrait;

    public function init()
    {
        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][item_title]',
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

        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][item_description]',
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

        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][item_date]',
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

        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][item_date_end]',
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

        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][render_year]',
            // A radio is not possible when there are multiple timeline blocks.
            'type' => Element\Select::class,
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

        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][center_date]',
            'type' => Element\Text::class,
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

        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][viewer]',
            'type' => Element\Textarea::class,
            'options' => [
                'label' => 'Viewer', // @translate
                'info' => $this->translate('Set the default params of the viewer as json, or let empty for the included default.') // @translate
                    . ' ' . $this->translate('Currently, only "bandInfos" and "centerDate" are managed.'), // @translate
            ],
            'attributes' => [
                'rows' => 15,
            ],
        ]);

        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][query]',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Query to limit resources', // @translate
                'info' => 'Limit the timeline to a particular subset of resources, for example a site, via an advanced search query.', // @translate
            ],
        ]);

        $this->add([
            'name' => 'o:block[__blockIndex__][o:data][library]',
            'type' => Element\Select::class,
            'options' => [
                'label' => 'Timeline library', // @translate
                'info' => 'Three libraries are available: the standard open source Simile Timeline, or the online Knightlab Timeline.', // @translate
                'value_options' => [
                    'simile' => 'Simile (use internal assets)',
                    'simile_online' => 'Simile online (cannot be used on a https site)',
                    'knightlab' => 'Knightlab (require an account)',
                ],
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);
    }

    protected function translate($args)
    {
        $translator = $this->getTranslator();
        return $translator->translate($args);
    }
}
