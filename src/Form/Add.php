<?php
/**
 * Form for Timeline records.
 */
class Timeline_Form_TimelineAdd extends Omeka_Form
{
    public function init()
    {
        parent::init();

        $this->setMethod('post');
        $this->setAttrib('id', 'timeline-form');

        // Title
        $this->addElement('text', 'title', [
            'label' => __('Title'),
            'description' => __('A title for this timeline.'),
        ]);

        // Description
        $this->addElement('textarea', 'description', [
            'label' => __('Description'),
            'description' => __('A description for this timeline.'),
            'attribs' => ['class' => 'html-editor', 'rows' => '15'],
        ]);

        // Public/Not Public
        $this->addElement('checkbox', 'public', [
            'label' => __('Status'),
            'description' => __('Whether the timeline is public or not.'),
            'value' => false,
        ]);

        // Featured/Not Featured
        $this->addElement('checkbox', 'featured', [
            'label' => __('Featured'),
            'description' => __('Whether the timeline is featured or not.'),
            'value' => false,
        ]);

        $values = get_table_options('Element', null, [
            'record_types' => ['Item', 'All'],
            'sort' => 'alphaBySet',
        ]);
        unset($values['']);
        foreach ([
                'item_title' => [__('Item Title')],
                'item_description' => [__('Item Description')],
                'item_date' => [__('Item Date')],
            ] as $parameterName => $parameterOptions) {
            $this->addElement('select', $parameterName, [
                'label' => $parameterOptions[0],
                'multiOptions' => $values,
                'value' => false,
            ]);
        }

        $values = ['' => __('None')] + $values;
        $this->addElement('select', 'item_date_end', [
            'label' => __('Item End Date'),
            'description' => __('If set, the process will use the other date as a start date.'),
            'multiOptions' => $values,
            'value' => false,
        ]);

        // Set fhe mode to render a year.
        $values = [
            'skip' => __('Skip the record'),
            'january_1' => __('Pick first January'),
            'july_1' => __('Pick first July'),
            'full_year' => __('Mark entire year'),
        ];
        $this->addElement('radio', 'render_year', [
            'label' => __('Render Year'),
            'description' => __('When a date is a single year, like "1066", the value should be interpreted to be displayed on the timeline.'),
            'multiOptions' => $values,
            'value' => false,
        ]);

        // Set the center date for the timeline.
        $this->addElement('text', 'center_date', [
            'label' => __('Center Date'),
            'description' => __('Set the center date of the timeline.')
                . ' ' . __('The format should be "YYYY-MM-DD".')
                . ' ' . __('An empty value means "now", "0000-00-00" the earliest date, and "9999-99-99" the latest date.'),
            'validator' => ['date'],
        ]);

        // Set the params of the viewer.
        $this->addElement('textarea', 'viewer', [
            'label' => __('Viewer'),
            'description' => __('Set the params of the viewer as json, or let empty for the included default.')
                . ' ' . __('Currently, only "bandInfos" and "centerDate" are managed.'),
            'attribs' => ['rows' => '10'],
        ]);

        // Submit
        $this->addElement('submit', 'submit', [
            'label' => __('Save Timeline'),
        ]);

        // Group the title, description, and public/featured fields.
        $this->addDisplayGroup(
            [
                'title',
                'description',
                'public',
                'featured',
            ],
            'timeline_info',
            [
                'legend' => __('About the timeline'),
                'description' => __('Set the main metadata of the timeline.'),
        ]);
        $this->addDisplayGroup(
            [
                'item_title',
                'item_description',
                'item_date',
                'item_date_end',
                'render_year',
                'center_date',
                'viewer',
            ],
            'timeline_parameters',
            [
                'legend' => __('Specific parameters'),
                'description' => __('Set the specific parameters of the timeline.')
                    . ' ' . __('If not set, the defaults set in the config page will apply.'),
        ]);

        // Add the submit to a separate display group.
        $this->addDisplayGroup(['submit'], 'timeline_submit');

        $this->addElement('sessionCsrfToken', 'csrf_token');
    }
}
