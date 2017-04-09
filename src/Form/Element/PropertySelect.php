<?php
namespace Timeline\Form\Element;

use Omeka\Form\Element\PropertySelect as OmekaPropertySelect;

class PropertySelect extends OmekaPropertySelect
{
    public function getValueOptions()
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
}
