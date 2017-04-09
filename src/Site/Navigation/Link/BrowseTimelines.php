<?php
namespace Timeline\Site\Navigation\Link;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Site\Navigation\Link\LinkInterface;
use Omeka\Stdlib\ErrorStore;

class BrowseTimelines implements LinkInterface
{
    public function getName()
    {
        return 'Browse Timelines'; // @translate
    }

    public function getFormTemplate()
    {
        return 'common/navigation-link-form/timeline-browse';
    }

    public function isValid(array $data, ErrorStore $errorStore)
    {
        return true;
    }

    public function getLabel(array $data, SiteRepresentation $site)
    {
        return isset($data['label']) && '' !== trim($data['label'])
            ? $data['label']
            : $this->getName();
    }

    public function toZend(array $data, SiteRepresentation $site)
    {
        return [
            'route' => 'site/timeline',
            'params' => [
                'site-slug' => $site->slug(),
            ],
        ];
    }

    public function toJstree(array $data, SiteRepresentation $site)
    {
        return [
            'label' => $data['label'],
        ];
    }
}
