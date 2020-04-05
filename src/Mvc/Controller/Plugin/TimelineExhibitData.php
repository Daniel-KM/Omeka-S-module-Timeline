<?php
namespace Timeline\Mvc\Controller\Plugin;

use DateTime;
use NumericDataTypes\DataType\Timestamp;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\Uri\Http as HttpUri;

/**
 * Create an exhibit for Knightlab timeline.
 *
 * @link https://timeline.knightlab.com
 */
class TimelineExhibitData extends AbstractPlugin
{
    /**
     * @var \Omeka\Mvc\Controller\Plugin\Api
     */
    protected $api;

    /**
     * @var \Zend\View\Helper\EscapeHtmlAttr
     */
    protected $escapeHtmlAttr;

    /**
     * @var string
     */
    protected $startDateProperty = 'dcterms:date';

    /**
     * @var string
     */
    protected $endDateProperty = null;

    /**
     * @var bool
     */
    protected $isCosmological = false;

    /**
     * @var string
     */
    protected $siteSlug;

    /**
     * List of extensions that are managed directly by the viewer.
     *
     * @link https://timeline.knightlab.com/docs/media-types.html
     *
     * @var array
     */
    protected $mediaExtensions = [
        'jpeg',
        'jpg',
        'gif',
        'png',
        'mp4',
        'mp3',
        'm4a',
        'wav',
    ];

    /**
     * Extract titles, descriptions and dates from the timeline’s slides.
     *
     * @param array $args
     * @return array
     */
    public function __invoke(array $args)
    {
        $controller = $this->getController();
        $this->api = $controller->api();
        $this->escapeHtmlAttr = $controller->viewHelpers()->get('escapeHtmlAttr');

        $this->startDateProperty = $args['start_date_property'];
        $this->endDateProperty = empty($args['end_date_property']) ? null : $args['end_date_property'];
        $this->isCosmologicial = (bool) $args['scale'] === 'cosmological';
        $this->siteSlug = $args['site_slug'];

        $timeline = [
            'scale' => $this->isCosmologicial ? 'cosmological' : 'human',
            'title' => null,
            'events' => [],
            'eras' => [],
        ];

        foreach ($args['slides'] as $key => $slideData) {
            $slideData['position'] = $key + 1;
            switch ($slideData['type']) {
                case 'title':
                    $timeline['title'] = $this->slide($slideData);
                    break;
                case 'era':
                    $timeline['eras'][] = $this->era($slideData);
                    break;
                case 'event':
                default:
                    $timeline['events'][] = $this->slide($slideData);
                    break;
            }
        }

        return $timeline;
    }

    /**
     * Get the slide from the slide data.
     *
     * @param array $slideData
     * @return array|null
     */
    protected function slide(array $slideData)
    {
        $slide = [
            'start_date' => $this->startDate($slideData),
            'end_date' => $this->endDate($slideData),
            'text' => $this->text($slideData),
            'media' => $this->media($slideData),
            'group' => empty($slideData['group']) ? null : $slideData['group'],
            'display_date' => empty($slideData['display_date']) ? null : $slideData['display_date'],
            'background' => $this->background($slideData),
            'autolink' => false,
            'unique_id' => null,
        ];

        // The id is unique, but not fully stable.
        $slide['unique_id'] = 'slide-' . $slideData['position'];
        if ($slide['media']) {
            /** @var \Omeka\Api\Representation\AbstractResourceEntityRepresentation $resource */
            $resource = $this->api->read('resources', ['id' => $slideData['resource']])->getContent();
            $slide['unique_id'] .=  '-' . $resource->getControllerName() . '-' . $slideData['resource'];
        } elseif ($slide['background'] && $slideData['background']) {
            $slide['unique_id'] .= '-asset-' . (int) $slideData['background'];
        }

        $slide = array_filter($slide, function ($v) {
            return !is_null($v);
        });

        if ($slideData['type'] === 'title') {
            return array_filter($slide)
                ? $slide
                : null;
        }

        return empty($slide['start_date'])
            ? null
            : $slide;
    }

    /**
     * Get the era from the slide data.
     *
     * @param array $slideData
     * @return array|null
     */
    protected function era(array $slideData)
    {
        $era = [
            'start_date' => $this->startDate($slideData),
            'end_date' => $this->endDate($slideData),
            'text' => $this->text($slideData),
        ];
        $era = array_filter($era);
        return empty($era['start_date']) || empty($era['end_date'])
            ? null
            : $era;
    }

    /**
     * Get the text from the slide data.
     *
     * @param array $slideData
     * @return array|null
     */
    protected function text(array $slideData)
    {
        $text = [
            'headline' => $slideData['headline'],
            'text' => $slideData['html'],
        ];
        return array_filter($text, 'strlen') ?: null;
    }

    /**
     * Get the media from the slide data.
     *
     * @param array $slideData
     * @return array|null
     */
    protected function media(array $slideData)
    {
        if (empty($slideData['resource'])) {
            return null;
        }

        try {
            /** @var \Omeka\Api\Representation\AbstractResourceEntityRepresentation $resource */
            $resource = $this->api->read('resources', ['id' => $slideData['resource']])->getContent();
        } catch (\Omeka\Api\Exception\NotFoundException $e) {
            return null;
        }

        $media = [
            'url' => null,
            'caption' => null,
            'credit' => null,
            'thumbnail' => null,
            'alt' => null,
            'title' => null,
            'link' => null,
            'link_target' => null,
        ];

        // When a media is set, the item is used for data, according to most
        // common cases.
        $mainResource = $resource->resourceName() === 'media'
            ? $resource->item()
            : $resource;

        /** @var \Omeka\Api\Representation\MediaRepresentation $primaryMedia */
        $primaryMedia = $resource->primaryMedia();
        $thumbnail = $resource->thumbnail();

        if ($primaryMedia) {
            // TODO Use the youtube url directly, etc.
            if (substr($primaryMedia->mediaType(), 0, 6) === 'image/') {
                $media['url'] = $primaryMedia->thumbnailUrl('large');
            } elseif (in_array($primaryMedia->extension(), $this->mediaExtensions)) {
                $media['url'] = $primaryMedia->originalUrl();
            } else {
                $data = $media->mediaData();
                switch ($primaryMedia->renderer()) {
                    case 'file':
                        // May use embed.ly for unmanaged formats.
                        $media['url'] = $primaryMedia->originalUrl();
                        break;
                    case 'html':
                        $media['url'] = '<blockquote>' . $data['html'] . '</blockquote>';
                        break;
                    case 'oembed':
                        if ($data['type'] === 'photo') {
                            $media['url'] = $data['url'];
                            $data['alt'] = empty($data['title']) ? null : $data['title'];
                        } elseif (!empty($data['html'])) {
                            $media['url'] = '<blockquote>' . $data['html'] . '</blockquote>';
                        } else {
                            $media['url'] = $media->source();
                        }
                        break;
                    case 'youtube':
                        // @see \Omeka\Media\Renderer\Youtube::render()
                        $url = new HttpUri(sprintf('https://www.youtube.com/embed/%s', $data['id']));
                        $query = [];
                        if (isset($data['start'])) {
                            $query['start'] = $data['start'];
                        }
                        if (isset($data['end'])) {
                            $query['end'] = $data['end'];
                        }
                        $media['url'] = $url->setQuery($query);
                        break;
                    case 'iiif':
                    default:
                        $media['url'] = '<blockquote>' . $media->render() . '</blockquote>';
                        break;
                }
            }
        }

        if ($thumbnail) {
            $media['thumbnail'] = $thumbnail->assetUrl();
        } elseif ($primaryMedia && $primaryMedia->hasThumbnails()) {
            $media['thumbnail'] = $primaryMedia->thumbnailUrl('medium');
        }

        $media['title'] = $mainResource->displayTitle('') ?: null;
        $media['caption'] = $mainResource->displayDescription('') ?: null;
        $value = $resource->value('dcterms:creator') ?: $mainResource->value('dcterms:creator');
        if ($value) {
            $media['credit'] = $value->asHtml();
        }
        $media['link'] = $mainResource->siteUrl($this->siteSlug);

        return array_filter($media, 'strlen') && $media['url'] ? $media : null;
    }

    /**
     * Get the background from the slide data.
     *
     * @param array $slideData
     * @return array|null
     */
    protected function background(array $slideData)
    {
        $background = [];

        if ($slideData['background']) {
            try {
                /** @var \Omeka\Api\Representation\AssetRepresentation $asset */
                $asset = $this->api->read('assets', $slideData['background'])->getContent();
                $background['url'] = $asset->assetUrl();
            } catch (\Omeka\Api\Exception\NotFoundException $e) {
            }
        }

        if ($slideData['background_color']) {
            $background['color'] = $slideData['background_color'];
        }

        return $background ?: null;
    }

    /**
     * Get the start date from the slide data.
     *
     * @param array $slideData
     * @return array|null
     */
    protected function startDate(array $slideData)
    {
        if (empty($slideData['start_date'])) {
            return empty($slideData['resource']) || empty($this->startDateProperty)
                ? null
                : $this->resourceDate($slideData['resource'], $this->startDateProperty, $slideData['start_display_date']);
        }
        return $this->date($slideData['start_date'], $slideData['start_display_date']);
    }

    /**
     * Get the end date from the slide data.
     *
     * @param array $slideData
     * @return array|null
     */
    protected function endDate(array $slideData)
    {
        if (empty($slideData['end_date'])) {
            return empty($slideData['resource']) || empty($this->endDateProperty)
                ? null
                : $this->resourceDate($slideData['resource'], $this->endDateProperty, $slideData['end_display_date']);
        }
        return $this->date($slideData['end_date'], $slideData['end_display_date']);
    }

    /**
     * Get the date from the specified value of a resource.
     *
     * @param int $resourceId
     * @param string $dateProperty
     * @param string $displayDate
     * @return array|null
     */
    protected function resourceDate($resourceId, $dateProperty, $displayDate = null)
    {
        try {
            /** @var \Omeka\Api\Representation\AbstractResourceEntityRepresentation $resource */
            $resource = $this->api->read('resources', ['id' => $resourceId])->getContent();
        } catch (\Omeka\Api\Exception\NotFoundException $e) {
            return null;
        }

        $dates = $resource->value($dateProperty, ['all' => true, 'default' => []]);
        foreach ($dates as $date) {
            $date = $this->date($date, $displayDate);
            if (is_array($date)) {
                return $date;
            }
        }

        return null;
    }

    /**
     * Convert a date from string to array.
     *
     * @param string|\Omeka\Api\Representation\ValueRepresentation $date
     * @param string $displayDate
     * @return array|null
     */
    protected function date($date, $displayDate = null)
    {
        $displayDate = strlen($displayDate) ? $displayDate : null;
        $explodedDate = [
            'year' => null,
            'month' => null,
            'day' => null,
            'hour' => null,
            'minute' => null,
            'second' => null,
            'millisecond' => null,
            'display_date' => $displayDate,
        ];

        $matches = [];

        // Set the start and end "date" objects.
        if (is_object($date) && $date->type() === 'numeric:timestamp') {
            $dateTime = Timestamp::getDateTimeFromValue($date->value());
            $explodedDate = array_intersect_key($dateTime, $explodedDate);
            if (!is_null($displayDate)) {
                $explodedDate['displayDate'] = $displayDate;
            }
        }
        // Simple year (not 0).
        elseif (preg_match('~^-?[0-9]+$~', $date)) {
            $explodedDate['year'] = $this->isCosmological ? $date : ((int) $date ?: null);
        }
        // TODO Simplify with one regex to manage partial or full iso dates.
        // Year-month.
        elseif (preg_match('~^(-?[0-9]+)-(1[0-2]|0[1-9])$~', $date, $matches)) {
            $explodedDate['year'] = (int) $matches[1];
            $explodedDate['month'] = (int) $matches[2];
        }
        // Year-month-day.
        elseif (preg_match('~^(-?[0-9]+)-(1[0-2]|0[1-9])-(3[01]|0[1-9]|[12][0-9])$~', $date, $matches)) {
            $explodedDate['year'] = (int) $matches[1];
            $explodedDate['month'] = (int) $matches[2];
            $explodedDate['day'] = (int) $matches[3];
        }
        // Year-month-day hour.
        elseif (preg_match('~^(-?[0-9]+)-(1[0-2]|0[1-9])-(3[01]|0[1-9]|[12][0-9])T(2[0-3]|[01][0-9])$~', $date, $matches)) {
            $explodedDate['year'] = (int) $matches[1];
            $explodedDate['month'] = (int) $matches[2];
            $explodedDate['day'] = (int) $matches[3];
            $explodedDate['hour'] = (int) $matches[4];
        }
        // Year-month-day hour:minute.
        elseif (preg_match('~^(-?[0-9]+)-(1[0-2]|0[1-9])-(3[01]|0[1-9]|[12][0-9])T(2[0-3]|[01][0-9]):([0-5][0-9])$~', $date, $matches)) {
            $explodedDate['year'] = (int) $matches[1];
            $explodedDate['month'] = (int) $matches[2];
            $explodedDate['day'] = (int) $matches[3];
            $explodedDate['hour'] = (int) $matches[4];
            $explodedDate['minute'] = (int) $matches[5];
        }
        // Year-month-day hour:minute:second.
        elseif (preg_match('~^(-?[0-9]+)-(1[0-2]|0[1-9])-(3[01]|0[1-9]|[12][0-9])T(2[0-3]|[01][0-9]):([0-5][0-9]):([0-5][0-9])$~', $date, $matches)) {
            $explodedDate['year'] = (int) $matches[1];
            $explodedDate['month'] = (int) $matches[2];
            $explodedDate['day'] = (int) $matches[3];
            $explodedDate['hour'] = (int) $matches[4];
            $explodedDate['minute'] = (int) $matches[5];
            $explodedDate['second'] = (int) $matches[6];
        }
        // Year-month-day hour:minute:second.millisecond
        elseif (preg_match('~^(-?[0-9]+)-(1[0-2]|0[1-9])-(3[01]|0[1-9]|[12][0-9])T(2[0-3]|[01][0-9]):([0-5][0-9]):([0-5][0-9])(\\.[0-9]+)?~', $date, $matches)) {
            $explodedDate['year'] = (int) $matches[1];
            $explodedDate['month'] = (int) $matches[2];
            $explodedDate['day'] = (int) $matches[3];
            $explodedDate['hour'] = (int) $matches[4];
            $explodedDate['minute'] = (int) $matches[5];
            $explodedDate['second'] = (int) $matches[6];
            $explodedDate['millisecond'] = (int) $matches[7];
        }
        // Else try a string, converted as a timestamp. Only date is returned
        // for simplicity, according to most common use cases.
        elseif (($timestamp = strtotime($date)) !== false) {
            $dateTime = new DateTime();
            $dateTime->setTimestamp($timestamp);
            $explodedDate['year'] = (int) $dateTime->format('Y');
            $explodedDate['month'] = (int) $dateTime->format('m');
            $explodedDate['day'] = (int) $dateTime->format('d');
        }

        $explodedDate = array_filter($explodedDate, function ($v) {
            return !is_null($v);
        });

        if ($this->isCosmological) {
            return is_null($explodedDate['year'])
                ? null
                : array_intersect_key($explodedDate, ['year' => null, 'display_date' => null]);
        }

        return $explodedDate['year']
            ? $explodedDate
            : null;
    }
}
