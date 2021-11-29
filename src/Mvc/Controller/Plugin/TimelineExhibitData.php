<?php declare(strict_types=1);
namespace Timeline\Mvc\Controller\Plugin;

use DateTime;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\Uri\Http as HttpUri;
use NumericDataTypes\DataType\Timestamp;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;

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
     * @var \Laminas\View\Helper\EscapeHtmlAttr
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
     * @var string
     */
    protected $creditProperty = 'dcterms:creator';

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
        $this->endDateProperty = $args['end_date_property'];
        $this->creditProperty = $args['credit_property'];
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

            // Simplify checks.
            $slideData += [
                'type' => '',
                'start_date' => '',
                'end_date' => '',
                'start_display_date' => '',
                'end_display_date' => '',
                'display_date' => '',
                'headline' => '',
                'html' => '',
                'resource' => null,
                'content' => '',
                'caption' => '',
                'credit' => '',
                'background' => null,
                'background_color' => '',
                'group' => '',
            ];

            // Prepare attachments so they will be available in all cases.
            if ($slideData['resource']) {
                try {
                    /* @var \Omeka\Api\Representation\AbstractResourceEntityRepresentation $resource */
                    $slideData['resource'] = $this->api->read('resources', ['id' => $slideData['resource']])->getContent();
                } catch (\Omeka\Api\Exception\NotFoundException $e) {
                    $slideData['resource'] = null;
                }
            }
            if ($slideData['background']) {
                try {
                    /* @var \Omeka\Api\Representation\AssetRepresentation $background */
                    $slideData['background'] = $this->api->read('assets', $slideData['background'])->getContent();
                } catch (\Omeka\Api\Exception\NotFoundException $e) {
                    $slideData['background'] = null;
                }
            }

            switch ($slideData['type']) {
                case 'title':
                    $slide = $this->slide($slideData);
                    if ($slide) {
                        $timeline['title'] = $slide;
                    }
                    break;
                case 'era':
                    $era = $this->era($slideData);
                    if ($era) {
                        $timeline['eras'][] = $era;
                    }
                    break;
                case 'event':
                default:
                    $slide = $this->slide($slideData);
                    if ($slide) {
                        $timeline['events'][] = $slide;
                    }
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
        $interval = $this->intervalDate($slideData);

        $slide = [
            'start_date' => $interval ? $interval['start_date'] : $this->startDate($slideData),
            'end_date' => $interval ? $interval['end_date'] : $this->endDate($slideData),
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
        if ($slide['media'] && $slideData['resource']) {
            $slide['unique_id'] .= '-' . $slideData['resource']->getControllerName() . '-' . $slideData['resource']->id();
        } elseif ($slide['background'] && $slideData['background']) {
            $slide['unique_id'] .= '-asset-' . $slideData['background']->id();
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
        $interval = $this->intervalDate($slideData);
        $era = [
            'start_date' => $interval ? $interval['start_date'] : $this->startDate($slideData),
            'end_date' => $interval ? $interval['end_date'] : $this->endDate($slideData),
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
            'headline' => null,
            'text' => null,
        ];

        if ($slideData['headline']) {
            $text['headline'] = $slideData['headline'];
        } elseif ($slideData['resource']) {
            $text['headline'] = $slideData['resource']->displayTitle();
        }

        if ($text['headline'] && $slideData['resource']) {
            $text['headline'] = $slideData['resource']->link($text['headline'], null, ['target' => '_blank']);
        }

        if ($slideData['html']) {
            $text['text'] = $slideData['html'];
        } elseif ($slideData['resource']) {
            $text['text'] = $slideData['resource']->displayDescription();
        }

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
            return $this->mediaContent($slideData);
        }

        $resource = $slideData['resource'];

        $media = [
            'url' => null,
            'caption' => null,
            'credit' => null,
            'thumbnail' => null,
            'alt' => null,
            'title' => null,
            'link' => null,
            'link_target' => '_blank',
        ];

        // When a media is set, the item is used for data, according to most
        // common cases.
        $isMedia = $resource->resourceName() === 'media';
        $mainResource = $isMedia
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
                $data = $primaryMedia->mediaData();
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
                            $media['url'] = $primaryMedia->source();
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
                        $media['url'] = '<blockquote>' . $primaryMedia->render() . '</blockquote>';
                        break;
                }
            }
        }

        if ($thumbnail) {
            $media['thumbnail'] = $thumbnail->assetUrl();
        } elseif ($primaryMedia && $primaryMedia->hasThumbnails()) {
            $media['thumbnail'] = $primaryMedia->thumbnailUrl('medium');
        }

        // Don't duplicate the title and the caption for item.
        if ($isMedia) {
            $media['title'] = $mainResource->displayTitle('') ?: null;
            $media['caption'] = $slideData['caption'] ?: $mainResource->displayDescription('');
        } elseif ($slideData['caption']) {
            $media['caption'] = $slideData['caption'];
        }

        if ($slideData['credit']) {
            $media['credit'] = $slideData['credit'];
        } elseif ($this->creditProperty) {
            $value = $resource->value($this->creditProperty) ?: $mainResource->value($this->creditProperty);
            if ($value) {
                $media['credit'] = $value->asHtml();
            }
        }
        $media['link'] = $mainResource->siteUrl($this->siteSlug);

        $media = array_filter($media);

        return isset($media['url']) ? $media : null;
    }

    /**
     * Get the media content from the slide data.
     *
     * @param array $slideData
     * @return array|null
     */
    protected function mediaContent(array $slideData)
    {
        if (empty($slideData['content'])) {
            return null;
        }

        $media = [
            'url' => $slideData['content'],
            'caption' => $slideData['caption'],
            'credit' => $slideData['credit'],
            'thumbnail' => null,
            'alt' => null,
            'title' => null,
            'link' => null,
            'link_target' => null,
        ];

        /* // No link.
        if (filter_var($slideData['content'], FILTER_VALIDATE_URL)) {
            $media['link'] = $slideData['content'];
            $media['link_target'] = '_blank';
        }
        */

        return array_filter($media);
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
            $background['url'] = $slideData['background']->assetUrl();
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
     * Get the start and end date from the specified value of a resource.
     *
     * @param array $slideData
     * @return array|null
     */
    protected function intervalDate(array $slideData)
    {
        if (empty($this->startDateProperty) || empty($slideData['resource'])) {
            return null;
        }

        $date = $slideData['resource']->value($this->startDateProperty, ['type' => 'numeric:interval']);
        if (!$date) {
            return null;
        }

        list($start, $end) = explode('/', $date->value());

        $startDate = Timestamp::getDateTimeFromValue($start);
        $interval['start_date'] = [
            'year' => $startDate['year'],
            'month' => $startDate['month'],
            'day' => $startDate['day'],
            'hour' => $startDate['hour'],
            'minute' => $startDate['minute'],
            'second' => $startDate['second'],
        ];
        if ($slideData['start_display_date']) {
            $interval['start_date']['display_date'] = $slideData['start_display_date'];
        }

        $endDate = Timestamp::getDateTimeFromValue($end, false);
        $interval['end_date'] = [
            'year' => $endDate['year'],
            'month' => $endDate['month_normalized'],
            'day' => $endDate['day_normalized'],
            'hour' => $endDate['hour_normalized'],
            'minute' => $endDate['minute_normalized'],
            'second' => $endDate['second_normalized'],
        ];
        if ($slideData['end_display_date']) {
            $interval['end_date']['display_date'] = $slideData['end_display_date'];
        }

        $interval['display_date'] = $slideData['display_date']
            ?: $startDate['date']->format($startDate['format_render']) . ' - ' . $endDate['date']->format($endDate['format_render']);
        return $interval;
    }

    /**
     * Get the date from the specified value of a resource.
     *
     * @param AbstractResourceEntityRepresentation $resource
     * @param string $dateProperty
     * @param string $displayDate
     * @return array|null
     */
    protected function resourceDate(AbstractResourceEntityRepresentation $resource, $dateProperty, $displayDate = null)
    {
        $dates = $resource->value($dateProperty, ['all' => true]);
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
        $parts = [
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

        $dateTime = is_object($date)
            ? $date->value()
            : $date;

        // Set the start and end "date" objects.
        if (is_object($date) && $date->type() === 'numeric:timestamp') {
            $dateTime = Timestamp::getDateTimeFromValue($date->value());
            $parts = array_intersect_key($dateTime, $parts);
            if (!is_null($displayDate)) {
                $parts['displayDate'] = $displayDate;
            }
        }
        // Simple year (not 0).
        elseif (preg_match('~^-?[0-9]+$~', $dateTime)) {
            $parts['year'] = $this->isCosmological ? $date : ((int) $dateTime ?: null);
        }
        // TODO Simplify with one regex to manage partial or full iso dates.
        // Year-month.
        elseif (preg_match('~^(-?[0-9]+)-(1[0-2]|0[1-9])$~', $dateTime, $matches)) {
            $parts['year'] = (int) $matches[1];
            $parts['month'] = (int) $matches[2];
        }
        // Year-month-day.
        elseif (preg_match('~^(-?[0-9]+)-(1[0-2]|0[1-9])-(3[01]|0[1-9]|[12][0-9])$~', $dateTime, $matches)) {
            $parts['year'] = (int) $matches[1];
            $parts['month'] = (int) $matches[2];
            $parts['day'] = (int) $matches[3];
        }
        // Year-month-day hour.
        elseif (preg_match('~^(-?[0-9]+)-(1[0-2]|0[1-9])-(3[01]|0[1-9]|[12][0-9])T(2[0-3]|[01][0-9])$~', $dateTime, $matches)) {
            $parts['year'] = (int) $matches[1];
            $parts['month'] = (int) $matches[2];
            $parts['day'] = (int) $matches[3];
            $parts['hour'] = (int) $matches[4];
        }
        // Year-month-day hour:minute.
        elseif (preg_match('~^(-?[0-9]+)-(1[0-2]|0[1-9])-(3[01]|0[1-9]|[12][0-9])T(2[0-3]|[01][0-9]):([0-5][0-9])$~', $dateTime, $matches)) {
            $parts['year'] = (int) $matches[1];
            $parts['month'] = (int) $matches[2];
            $parts['day'] = (int) $matches[3];
            $parts['hour'] = (int) $matches[4];
            $parts['minute'] = (int) $matches[5];
        }
        // Year-month-day hour:minute:second.
        elseif (preg_match('~^(-?[0-9]+)-(1[0-2]|0[1-9])-(3[01]|0[1-9]|[12][0-9])T(2[0-3]|[01][0-9]):([0-5][0-9]):([0-5][0-9])$~', $dateTime, $matches)) {
            $parts['year'] = (int) $matches[1];
            $parts['month'] = (int) $matches[2];
            $parts['day'] = (int) $matches[3];
            $parts['hour'] = (int) $matches[4];
            $parts['minute'] = (int) $matches[5];
            $parts['second'] = (int) $matches[6];
        }
        // Year-month-day hour:minute:second.millisecond
        elseif (preg_match('~^(-?[0-9]+)-(1[0-2]|0[1-9])-(3[01]|0[1-9]|[12][0-9])T(2[0-3]|[01][0-9]):([0-5][0-9]):([0-5][0-9])(\\.[0-9]+)?~', $dateTime, $matches)) {
            $parts['year'] = (int) $matches[1];
            $parts['month'] = (int) $matches[2];
            $parts['day'] = (int) $matches[3];
            $parts['hour'] = (int) $matches[4];
            $parts['minute'] = (int) $matches[5];
            $parts['second'] = (int) $matches[6];
            $parts['millisecond'] = (int) $matches[7];
        }
        // Else try a string, converted as a timestamp. Only date is returned
        // for simplicity, according to most common use cases.
        elseif (($timestamp = strtotime($dateTime)) !== false) {
            $dateTimeO = new DateTime();
            $dateTime->setTimestamp($timestamp);
            $parts['year'] = (int) $dateTimeO->format('Y');
            $parts['month'] = (int) $dateTimeO->format('m');
            $parts['day'] = (int) $dateTimeO->format('d');
        }

        $parts = array_filter($parts, function ($v) {
            return !is_null($v);
        });

        if (!isset($parts['year'])) {
            return null;
        }

        if ($this->isCosmological) {
            return array_intersect_key($parts, ['year' => null, 'display_date' => null]);
        }

        return $parts;
    }
}
