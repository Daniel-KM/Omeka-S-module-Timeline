<?php declare(strict_types=1);
namespace Timeline\Form;

use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Laminas\View\Helper\Url as UrlHelper;
use Omeka\Form\Element\Asset;
use Omeka\Form\Element\PropertySelect;

// use Omeka\Form\Element\ResourceSelect;

class TimelineExhibitFieldset extends Fieldset
{
    /**
     * @var UrlHelper
     */
    protected $urlHelper;

    public function init(): void
    {
        // $urlHelper = $this->getUrlHelper();

        $this
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][heading]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Block title', // @translate
                ],
                'attributes' => [
                    'id' => 'timeline-exhibit-heading',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][start_date_property]',
                'type' => PropertySelect::class,
                'options' => [
                    'label' => 'Start date property', // @translate
                    'info' => 'Date to use from the attachement when no date is set.', // @translate
                    'empty_option' => '',
                    'term_as_value' => true,
                ],
                'attributes' => [
                    'id' => 'timeline-exhibit-start-date-property',
                    'class' => 'chosen-select',
                    'data-placeholder' => 'Select a property…', // @translate
                    'value' => 'dcterms:date',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][end_date_property]',
                'type' => PropertySelect::class,
                'options' => [
                    'label' => 'End date property', // @translate
                    'info' => 'End date to use from the attachement when no end date is set.', // @translate
                    'empty_option' => '',
                    'term_as_value' => true,
                ],
                'attributes' => [
                    'id' => 'timeline-exhibit-end-date-property',
                    'class' => 'chosen-select',
                    'data-placeholder' => 'Select a property…', // @translate
                    'value' => 'dcterms:date',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][credit_property]',
                'type' => PropertySelect::class,
                'options' => [
                    'label' => 'Credit property', // @translate
                    'info' => 'Credit to use from the attachement when no credit is set (generally creator or rights).', // @translate
                    'empty_option' => '',
                    'term_as_value' => true,
                ],
                'attributes' => [
                    'id' => 'timeline-exhibit-credit-property',
                    'class' => 'chosen-select',
                    'data-placeholder' => 'Select a property…', // @translate
                    'value' => 'dcterms:creator',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][scale]',
                'type' => Element\Radio::class,
                'options' => [
                    'label' => 'Scale', // @translate
                    'value_options' => [
                        'human' => 'Human', // @translate
                        'cosmological' => 'Cosmological', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'timeline-exhibit-scale',
                    'value' => 'human',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][options]',
                'type' => Element\Textarea::class,
                'options' => [
                    'label' => 'Options', // @translate
                    'info' => 'Set the default params of the viewer as json, or let empty for the included default.', // @translate
                    'documentation' => 'https://gitlab.com/daniel-km/omeka-s-module-timeline#knightlab-timeline',
                ],
                'attributes' => [
                    'rows' => 5,
                ],
            ])
        ;

        $this
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][slides]',
                'type' => Fieldset::class,
                'options' => [
                    'label' => 'Slides', // @translate
                ],
                'attributes' => [
                    'id' => 'timeline-exhibit-slides',
                    'class' => 'slides-list',
                    'data-next-index' => '0',
                ],
            ])
        ;

        $fieldsetBase = $this->get('o:block[__blockIndex__][o:data][slides]');
        $fieldsetBase
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][slides][__slideIndex__]',
                'type' => Fieldset::class,
                'options' => [
                    'label' => 'Slide', // @translate
                    'use_as_base_fieldset' => true,
                ],
                'attributes' => [
                    'id' => 'timeline-exhibit-slides',
                    'class' => 'slide-data',
                    'data-index' => '__slideIndex__',
                ],
            ]);
        $fieldsetRepeat = $fieldsetBase->get('o:block[__blockIndex__][o:data][slides][__slideIndex__]');
        $fieldsetRepeat
            // TODO Make a quick exhibit with attachments.
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][slides][__slideIndex__][resource]',
                'type' => Element\Number::class,
                'options' => [
                    'label' => 'Resource for content', // @translate
                ],
                'attributes' => [
                    'id' => 'timeline-exhibit-resource',
                    'multiple' => false,
                    'required' => false,
                    'min' => 0,
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][slides][__slideIndex__][type]',
                'type' => Element\Radio::class,
                'options' => [
                    'label' => 'Type', // @translate
                    'value_options' => [
                        'event' => 'Event', // @translate
                        'era' => 'Era', // @translate
                        'title' => 'Title', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'timeline-exhibit-type',
                    'value' => 'event',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][slides][__slideIndex__][start_date]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Start date', // @translate
                ],
                'attributes' => [
                    'id' => 'timeline-exhibit-start-date',
                ],
                'validators' => [
                    ['name' => 'Date'],
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][slides][__slideIndex__][start_display_date]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Display date for start', // @translate
                ],
                'attributes' => [
                    'id' => 'timeline-exhibit-start-display-date',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][slides][__slideIndex__][end_date]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'End date', // @translate
                ],
                'validators' => [
                    ['name' => 'Date'],
                ],
                'attributes' => [
                    'id' => 'timeline-exhibit-end-date',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][slides][__slideIndex__][end_display_date]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Display date for end', // @translate
                ],
                'attributes' => [
                    'id' => 'timeline-exhibit-end-display-date',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][slides][__slideIndex__][display_date]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Display main date', // @translate
                ],
                'attributes' => [
                    'id' => 'timeline-exhibit-display-date',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][slides][__slideIndex__][headline]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Headline', // @translate
                ],
                'attributes' => [
                    'id' => 'timeline-exhibit-headline',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][slides][__slideIndex__][html]',
                'type' => Element\Textarea::class,
                'options' => [
                    'label' => 'Text', // @translate
                ],
                'attributes' => [
                    'id' => 'timeline-exhibit-html',
                    'class' => 'block-html full wysiwyg',
                ],
            ])
            /* // TODO Use attachement or a dynamic resource callback.
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][slides][__slideIndex__][resource]',
                'type' => ResourceSelect::class,
                'options' => [
                    'label' => 'Resource', // @translate
                    'empty_option' => '',
                    'resource_value_options' => [
                        'resource' => 'media',
                        'query' => [],
                        'option_text_callback' => function ($resource) {
                            return $resource->displayTitle();
                        },
                    ],
                ],
                'attributes' => [
                   'id' => 'timeline-exhibit-resource',
                    'class' => 'chosen-select',
                    'multiple' => false,
                    'required' => false,
                    'data-placeholder' => 'Select one resource…', // @translate
                    'data-api-base-url' => $urlHelper('api/default', ['resource' => 'resource']),
                ],
            ])
            */
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][slides][__slideIndex__][content]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'External content', // @translate
                ],
                'attributes' => [
                    'id' => 'timeline-exhibit-content',
                    'required' => false,
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][slides][__slideIndex__][caption]',
                'type' => Element\Textarea::class,
                'options' => [
                    'label' => 'Caption', // @translate
                ],
                'attributes' => [
                    'id' => 'timeline-exhibit-caption',
                    // 'class' => 'block-html full wysiwyg',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][slides][__slideIndex__][credit]',
                'type' => Element\Textarea::class,
                'options' => [
                    'label' => 'Credit', // @translate
                ],
                'attributes' => [
                    'id' => 'timeline-exhibit-credit',
                    // 'class' => 'block-html full wysiwyg',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][slides][__slideIndex__][background]',
                'type' => Asset::class,
                'options' => [
                    'label' => 'Background', // @translate
                ],
                'attributes' => [
                    'id' => 'timeline-exhibit-background',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][slides][__slideIndex__][background_color]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Background color', // @translate
                    'info' => 'A css color as hexadecimal or keyword.', // @translate
                ],
                'attributes' => [
                    'id' => 'timeline-exhibit-background-color',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][slides][__slideIndex__][group]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Group', // @translate
                ],
                'attributes' => [
                    'id' => 'timeline-exhibit-group',
                ],
            ])

            // TODO Move remove / creation of new fieldset to js?
            ->add([
                'name' => 'add_slide',
                'type' => Element\Button::class,
                'options' => [
                    'label' => 'Add another slide', // @translate
                ],
                'attributes' => [
                    'id' => 'timeline-exhibit-another',
                    'class' => 'timeline-slide-add button',
                ],
            ])
            ->add([
                'name' => 'remove_slide',
                'type' => Element\Button::class,
                'options' => [
                    'label' => 'Remove this slide', // @translate
                ],
                'attributes' => [
                    'id' => 'timeline-exhibit-remove',
                    'class' => 'timeline-slide-remove button red',
                ],
            ]);
    }

    public function setUrlHelper(UrlHelper $urlHelper)
    {
        $this->urlHelper = $urlHelper;
        return $this;
    }

    public function getUrlHelper()
    {
        return $this->urlHelper;
    }
}
