<?php declare(strict_types=1);

namespace Timeline\Site\ResourcePageBlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Site\ResourcePageBlockLayout\ResourcePageBlockLayoutInterface;

/**
 * Display the timeline for all items of an item set.
 */
class TimelineKnightlab implements ResourcePageBlockLayoutInterface
{
    public function getLabel() : string
    {
        return 'Timeline (Knightlab)'; // @translate
    }

    public function getCompatibleResourceNames() : array
    {
        return [
            'item_sets',
        ];
    }

    public function render(PhpRenderer $view, AbstractResourceEntityRepresentation $resource) : string
    {
        $config = $resource->getServiceLocator()->get('Config');
        $data = $config['timeline']['block_settings']['timeline'];
        return $view->partial('common/resource-page-block-layout/timeline-knightlab', [
            'resource' => $resource,
            'data' => $data,
        ]);
    }
}
