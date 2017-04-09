<?php
namespace Timeline\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class TimelineRepresentation extends AbstractEntityRepresentation
{
    public function getControllerName()
    {
        return 'timeline';
    }

    public function getJsonLdType()
    {
        return 'o-module-timeline:Timeline';
    }

    public function getJsonLd()
    {
        $owner = null;
        if ($this->owner()) {
            $owner = $this->owner()->getReference();
        }

        $created = [
            '@value' => $this->getDateTime($this->created()),
            '@type' => 'http://www.w3.org/2001/XMLSchema#dateTime',
        ];
        $modified = null;
        if ($this->modified()) {
            $modified = [
                '@value' => $this->getDateTime($this->modified()),
                '@type' => 'http://www.w3.org/2001/XMLSchema#dateTime',
            ];
        }

        return [
            'o-module-timeline:slug' => $this->slug(),
            'o-module-timeline:title' => $this->title(),
            'o-module-timeline:description' => $this->description(),
            'o-module-timeline:is_public' => $this->isPublic(),
            'o-module-timeline:parameters' => $this->parameters(),
            'o-module-timeline:item_pool' => $this->itemPool(),
            'o-module-timeline:owner' => $owner,
            'o-module-timeline:created' => $created,
            'o-module-timeline:modified' => $modified,
        ];
    }

    public function slug()
    {
        return $this->resource->getSlug();
    }

    public function title()
    {
        return $this->resource->getTitle();
    }

    public function description()
    {
        return $this->resource->getDescription();
    }

    public function isPublic()
    {
        return $this->resource->isPublic();
    }

    public function parameters()
    {
        return $this->resource->getParameters();
    }

    public function itemPool()
    {
        return $this->resource->getItemPool();
    }

    /**
     * Get the owner representation of this resource.
     *
     * @return UserRepresentation
     */
    public function owner()
    {
        return $this->getAdapter('users')
            ->getRepresentation($this->resource->getOwner());
    }

    public function created()
    {
        return $this->resource->getCreated();
    }

    public function modified()
    {
        return $this->resource->getModified();
    }

    /**
     * Get this timeline's item count.
     *
     * @return int
     */
    public function itemCount()
    {
        $response = $this->getServiceLocator()->get('Omeka\ApiManager')
            ->search('items', [
                'timeline_slug' => $this->slug(),
                'limit' => 0,
            ]);
        return $response->getTotalResults();
    }

    /**
     * Return the first media of the first item.
     *
     * {@inheritDoc}
     */
    public function primaryMedia()
    {
        $response = $this->getServiceLocator()->get('Omeka\ApiManager')
            ->search('items', [
                'timeline_slug' => $this->slug(),
                'limit' => 1,
            ]);
        $items = $response->getContent();
        if (empty($items)) {
            return null;
        }
        return $items[0]->primaryMedia();
    }

    public function siteUrl($siteSlug = null, $canonical = false)
    {
        if (!$siteSlug) {
            $siteSlug = $this->getServiceLocator()->get('Application')
                ->getMvcEvent()->getRouteMatch()->getParam('site-slug');
        }
        $url = $this->getViewHelper('Url');
        return $url(
            'site/timeline/slug',
            [
                'site-slug' => $siteSlug,
                'timeline-slug' => $this->slug(),
            ],
            ['force_canonical' => $canonical]
        );
    }

    public function adminUrl($action = null, $canonical = false)
    {
        $url = $this->getViewHelper('Url');
        return $url(
            'admin/timeline/slug',
            [
                'timeline-slug' => $this->slug(),
                'action' => $action,
            ],
            ['force_canonical' => $canonical]
        );
    }
}
