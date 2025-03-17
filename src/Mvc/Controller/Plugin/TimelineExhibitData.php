<?php declare(strict_types=1);

namespace Timeline\Mvc\Controller\Plugin;

use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\Uri\Http as HttpUri;
use NumericDataTypes\DataType\Timestamp;
use Omeka\Api\Manager as ApiManager;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Mvc\Controller\Plugin\Translate;
use Omeka\Api\Representation\MediaRepresentation;

/**
 * Create an exhibit for Knightlab timeline.
 *
 * @link https://timeline.knightlab.com
 */
class TimelineExhibitData extends AbstractPlugin
{
    use TraitTimelineData;

    /**
     * Copied:
     * @see \Timeline\Site\BlockLayout\TimelineExhibit
     * @see \Timeline\Mvc\Controller\Plugin\TimelineExhibitData
     *
     * @var array
     */
    protected $slideDefault = [
        // Main resource to display: one of next three media.
        'resource' => null,
        'asset' => null,
        'external' => null,
        // Default type is empty, that means "event".
        'type' => 'event',
        'start_date' => '',
        'start_display_date' => '',
        'end_date' => '',
        'end_display_date' => '',
        'display_date' => '',
        'headline' => '',
        'html' => '',
        'caption' => '',
        'credit' => '',
        // Background resource to display: one of next three media.
        'background_resource' => null,
        'background_asset' => null,
        'background_external' => null,
        'background_color' => '',
        'group' => '',
    ];

    /**
     * @var \Omeka\Api\Manager
     */
    protected $api;

    /**
     * @var \Omeka\Mvc\Controller\Plugin\Translate
     */
    protected $translate;

    /**
     * @var string
     */
    protected $creditProperty = 'dcterms:creator';

    /**
     * @var string
     */
    protected $endDateProperty = null;

    /**
     * @var array
     */
    protected $fieldsItem = [];

    /**
     * @var string
     */
    protected $fieldGroup = null;

    /**
     * @var string
     */
    protected $groupDefault = null;

    /**
     * @var bool
     */
    protected $linkToSelf = false;

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
     * @var string
     */
    protected $siteSlug;

    /**
     * @var string
     */
    protected $startDateProperty = 'dcterms:date';

    public function __construct(ApiManager $api, Translate $translate)
    {
        $this->api = $api;
        $this->translate = $translate;
    }

    /**
     * Extract titles, descriptions and dates from the timeline’s slides.
     */
    public function __invoke(array $args): array
    {
        $this->startDateProperty = $args['start_date_property'];
        $this->endDateProperty = $args['end_date_property'];
        $this->creditProperty = $args['credit_property'];
        $this->isCosmological = (bool) $args['scale'] === 'cosmological';
        $this->fieldsItem = $args['item_metadata'] ?? [];
        $this->fieldGroup = $args['group'] ?? null;
        $this->groupDefault = empty($args['group_default']) ? null : $args['group_default'];
        $this->siteSlug = $args['site_slug'];
        $this->linkToSelf = !empty($args['link_to_self']);

        $eras = empty($args['eras']) ? [] : $this->extractEras($args['eras']);
        $markers = empty($args['markers']) ? [] : $this->extractMarkers($args['markers']);

        $timeline = [
            'scale' => $this->isCosmological ? 'cosmological' : 'human',
            'title' => null,
            'events' => [],
            'eras' => $eras,
        ];

        foreach ($args['slides'] as $key => $slideData) {
            $slideData['position'] = $key + 1;

            // Simplify checks.
            $slideData += $this->slideDefault;

            // Prepare attachments early so they will be available in all cases.
            if ($slideData['resource']) {
                try {
                    $slideData['resource'] = $this->api->read('resources', ['id' => $slideData['resource']])->getContent();
                    $slideData['asset'] = null;
                    $slideData['external'] = null;
                } catch (\Omeka\Api\Exception\NotFoundException $e) {
                    $slideData['resource'] = null;
                }
            }

            if ($slideData['asset']) {
                try {
                    $slideData['asset'] = $this->api->read('assets', ['id' => $slideData['asset']])->getContent();
                    $slideData['external'] = null;
                } catch (\Omeka\Api\Exception\NotFoundException $e) {
                    $slideData['asset'] = null;
                }
            }

            if ($slideData['background_resource']) {
                try {
                    $slideData['background_resource'] = $this->api->read('resources', $slideData['background_resource'])->getContent();
                    $slideData['background_asset'] = null;
                    $slideData['background_external'] = null;
                } catch (\Omeka\Api\Exception\NotFoundException $e) {
                    $slideData['background_rsource'] = null;
                }
            }

            if ($slideData['background_asset']) {
                try {
                    $slideData['background_asset'] = $this->api->read('assets', $slideData['background_asset'])->getContent();
                    $slideData['background_external'] = null;
                } catch (\Omeka\Api\Exception\NotFoundException $e) {
                    $slideData['background_asset'] = null;
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

        // Append markers.
        $groupLabel = $this->translate->__invoke('Events'); // @translate
        foreach ($markers as $markerData) {
            $markerData['group'] = $groupLabel;
            $timeline['events'][] = $markerData;
        }

        return $timeline;
    }

    /**
     * Get the slide from the slide data.
     */
    protected function slide(array $slideData): ?array
    {
        $interval = $this->intervalDate($slideData);

        // Prepare the group when needed.
        $group = $this->groupDefault;
        if (empty($slideData['group'])) {
            if ($this->fieldGroup
                && $slideData['resource']
                && !in_array($slideData['type'], ['title', 'era'])
            ) {
                $group = $this->resourceMetadataSingle($slideData['resource'], $this->fieldGroup)
                    ?: $this->groupDefault;
                $group = $group ? strip_tags($group) : null;
            }
        } else {
            $group = strip_tags($slideData['group']);
        }

        $slide = [
            'start_date' => $interval ? $interval['start_date'] : $this->startDate($slideData),
            'end_date' => $interval ? $interval['end_date'] : $this->endDate($slideData),
            'text' => $this->text($slideData),
            'media' => $this->media($slideData),
            'group' => $group,
            'display_date' => empty($slideData['display_date']) ? null : $slideData['display_date'],
            'background' => $this->background($slideData),
            'autolink' => false,
            'unique_id' => null,
        ];

        // The id is unique, but not fully stable.
        $slide['unique_id'] = 'slide-' . $slideData['position'];
        if ($slide['media'] && $slideData['resource']) {
            $slide['unique_id'] .= '-' . $slideData['resource']->getControllerName() . '-' . $slideData['resource']->id();
        } elseif ($slide['media'] && $slideData['asset']) {
            $slide['unique_id'] .= '-asset-' . $slideData['asset']->id();
        } elseif ($slide['background'] && $slideData['background_resource']) {
            $slide['unique_id'] .= '-b-' . $slideData['background_resource']->getControllerName() . '-' . $slideData['background_resource']->id();
        } elseif ($slide['background'] && $slideData['background_asset']) {
            $slide['unique_id'] .= '-b-asset-' . $slideData['background_asset']->id();
        }

        $slide = array_filter($slide, fn ($v) => $v !== null);

        // "metadata" allows to pass data to customize Knightslab via css/js.
        if ($this->fieldsItem && !empty($slideData['resource'])) {
            $slide['metadata'] = $this->resourceMetadata($slideData['resource'], $this->fieldsItem);
        }

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
     */
    protected function era(array $slideData): ?array
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
     */
    protected function text(array $slideData): ?array
    {
        $text = [
            'headline' => null,
            'text' => null,
        ];

        // When a media is set, the item is used for data, according to most
        // common cases.

        if ($slideData['headline']) {
            $text['headline'] = $slideData['headline'];
        } elseif ($slideData['resource']) {
            $text['headline'] = $slideData['resource'] instanceof MediaRepresentation
                ? (string) $slideData['resource']->displayTitle()
                : (string) $slideData['resource']->displayTitle();
        }

        if ($text['headline'] && $slideData['resource']) {
            $text['headline'] = $slideData['resource'] instanceof MediaRepresentation
                ? $slideData['resource']->item()->link($text['headline'], null, ['target' => '_blank'])
                : $slideData['resource']->link($text['headline'], null, ['target' => '_blank']);
        }

        if ($slideData['html']) {
            $text['text'] = $slideData['html'];
        } elseif ($slideData['resource']) {
            $text['text'] = (string) $slideData['resource']->displayDescription();
        }

        return array_filter(array_map('strval', $text), 'strlen') ?: null;
    }

    /**
     * Get the media from the slide data.
     */
    protected function media(array $slideData): ?array
    {
        if (empty($slideData['resource'])) {
            return $this->mediaFromAssetOrExternal($slideData);
        }

        /** @var \Omeka\Api\Representation\AbstractResourceEntityRepresentation $resource */
        $resource = $slideData['resource'];

        $media = [
            'url' => null,
            'caption' => null,
            'credit' => null,
            'thumbnail' => null,
            'alt' => null,
            'title' => null,
            'link' => null,
            'link_target' => $this->linkToSelf ? null : '_blank',
        ];

        // When a media is set, the item is used for data, according to most
        // common cases.
        $isMedia = $resource instanceof MediaRepresentation;

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
                        $media['alt'] = $primaryMedia->altText();
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
     */
    protected function mediaFromAssetOrExternal(array $slideData): ?array
    {
        $media = [];

        if (!empty($slideData['asset'])) {
            /** @var \Omeka\Api\Representation\AssetRepresentation $asset */
            $asset = $slideData['asset'];
            $media = [
                'url' => $asset->assetUrl(),
                'caption' => $slideData['caption'],
                'credit' => $slideData['credit'],
                'thumbnail' => null,
                'alt' => $asset->altText(),
                'title' => $slideData['headline'],
                // No link.
                'link' => null,
                'link_target' => null,
            ];
        } elseif (!empty($slideData['external'])) {
            $media = [
                'url' => $slideData['external'],
                'caption' => $slideData['caption'],
                'credit' => $slideData['credit'],
                'thumbnail' => null,
                'alt' => null,
                'title' => $slideData['headline'],
                // No link.
                'link' => null,
                'link_target' => null,
            ];
        }

        /* // No link.
        if (filter_var($media['url'], FILTER_VALIDATE_URL)) {
            $media['link'] = $media['url'];
            $media['link_target'] = $this->linkToSelf ? null : '_blank',
        }
        */

        return array_filter($media) ?: null;
    }

    /**
     * Get the background from the slide data.
     */
    protected function background(array $slideData): ?array
    {
        $background = [];

        if (!empty($slideData['background_resource'])) {
            /** @var \Omeka\Api\Representation\AbstractResourceEntityRepresentation $resource */
            $resource = $slideData['background_resource'];
            $background = [
                'url' => $resource->thumbnailDisplayUrl('large'),
            ];
        } elseif (!empty($slideData['background_asset'])) {
            /** @var \Omeka\Api\Representation\AssetRepresentation $asset */
            $asset = $slideData['background_asset'];
            $background = [
                'url' => $asset->assetUrl(),
            ];
        } elseif (!empty($slideData['background_external'])) {
            $background = [
                'url' => $slideData['background_external'],
            ];
        }

        // if (filter_var($background['url'], FILTER_VALIDATE_URL)) {}
        if ($slideData['background_color']) {
            $background['color'] = $slideData['background_color'];
        }

        return array_filter($background) ?: null;
    }

    /**
     * Get the start date from the slide data.
     */
    protected function startDate(array $slideData): ?array
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
     */
    protected function endDate(array $slideData): ?array
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
     */
    protected function intervalDate(array $slideData): ?array
    {
        if (empty($this->startDateProperty) || empty($slideData['resource'])) {
            return null;
        }

        $date = $slideData['resource']->value($this->startDateProperty, ['type' => 'numeric:interval']);
        if (!$date) {
            return null;
        }

        [$start, $end] = explode('/', $date->value());

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
     */
    protected function resourceDate(AbstractResourceEntityRepresentation $resource, $dateProperty, ?string $displayDate = null): ?array
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
}
