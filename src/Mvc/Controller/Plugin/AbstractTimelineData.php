<?php declare(strict_types=1);

namespace Timeline\Mvc\Controller\Plugin;

use Common\Stdlib\EasyMeta;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\Mvc\I18n\Translator;
use Omeka\Api\Manager as ApiManager;
use Omeka\Api\Representation\ItemRepresentation;
use Omeka\Settings\Settings;
use Omeka\Settings\SiteSettings;

abstract class AbstractTimelineData extends AbstractPlugin
{
    use TraitTimelineData;

    /**
     * @var \Omeka\Api\Manager
     */
    protected $api;

    /**
     * @var \Common\Stdlib\EasyMeta
     */
    protected $easyMeta;

    /**
     * @var \Omeka\Settings\Settings
     */
    protected $settings;

    /**
     * @var \Omeka\Settings\SiteSettings
     */
    protected $siteSettings;

    /**
     * @var \Laminas\Mvc\I18n\Translator
     */
    protected $translator;

    public function __construct(
        ApiManager $api,
        EasyMeta $easyMeta,
        Settings $settings,
        SiteSettings $siteSettings,
        Translator $translator
    ) {
        $this->api = $api;
        $this->easyMeta = $easyMeta;
        $this->settings = $settings;
        $this->siteSettings = $siteSettings;
        $this->translator = $translator;
    }

    /**
     * Extract titles, descriptions and dates from the timeline’s pool of items.
     */
    public function __invoke(array $itemPool, array $args): array
    {
        $events = [];

        $this->renderYear = $args['render_year'] ?? static::$renderYears['default'];

        $propertyItemTitle = ($args['item_title'] ?? 'default') === 'default' ? '' : $args['item_title'];
        $propertyItemDescription = ($args['item_description'] ?? 'default') === 'default' ? '' : $args['item_description'];
        $propertyItemDate = $args['item_date'] ?? 'dcterms:date';
        $propertyItemDateVa = !empty($args['item_date_va'])
            ? $args['item_date_va']
            : null;
        $propertyItemDateEnd = $args['item_date_end'] ?? null;
        $propertyItemDateEndVa = !empty($args['item_date_end_va'])
            ? $args['item_date_end_va']
            : null;
        $fieldsItem = $args['item_metadata'] ?? [];
        $fieldGroup = $args['group'] ?? null;
        $fieldGroupVa = !empty($args['group_va'])
            ? $args['group_va']
            : null;
        $groupDefault = empty($args['group_default'])
            ? null
            : $args['group_default'];
        $linkToSelf = !empty($args['link_to_self']);

        $eras = empty($args['eras'])
            ? []
            : $this->extractEras($args['eras']);
        $markers = empty($args['markers'])
            ? []
            : $this->extractMarkers($args['markers']);

        $thumbnailType = empty($args['thumbnail_type'])
            ? 'medium'
            : $args['thumbnail_type'];
        $thumbnailResource = !empty($args['thumbnail_resource']);

        $params = $itemPool;
        $params['property'][] = [
            'joiner' => 'and',
            'property' => $this->easyMeta->propertyId($propertyItemDate),
            'type' => 'ex',
        ];

        // To avoid overflow when requesting all items, use a
        // loop with id.
        $itemIds = $this->api->search(
            'items', $params, ['returnScalar' => 'id']
        )->getContent();

        /** @var \Omeka\Api\Representation\ItemRepresentation $item */
        foreach (array_chunk($itemIds, 100) as $idsChunk) foreach ($this->api->search('items', ['id' => $idsChunk])->getContent() as $item) {
            // All items without dates are already automatically removed.
            $itemDates = $propertyItemDateVa
                ? $this->valuesFromAnnotations($item, $propertyItemDate, $propertyItemDateVa)
                : $item->value($propertyItemDate, ['all' => true]);
            $itemTitle = strip_tags($propertyItemTitle
                ? (string) $item->value($propertyItemTitle)
                : $item->displayTitle());
            $itemDescription = $this->snippet($propertyItemDescription
                ? (string) $item->value($propertyItemDescription)
                : $item->displayDescription(), 200);
            $itemGroup = $fieldGroupVa
                ? $this->firstValueFromAnnotations($item, $fieldGroup, $fieldGroupVa)
                : $this->resourceMetadataSingle($item, $fieldGroup);
            $itemGroup = ($itemGroup ?: $groupDefault)
                ? strip_tags($itemGroup ?: $groupDefault) : null;
            $itemDatesEnd = $propertyItemDateEnd
                ? ($propertyItemDateEndVa
                    ? $this->valuesFromAnnotations($item, $propertyItemDateEnd, $propertyItemDateEndVa)
                    : $item->value($propertyItemDateEnd, ['all' => true]))
                : [];
            $itemLink = empty($args['site_slug'])
                ? null
                : $item->siteUrl($args['site_slug']);

            if ($thumbnailResource && $item->thumbnail()) {
                $thumbnailUrl = $item->thumbnail()->assetUrl();
                $thumbnailAltText = null;
            } elseif ($media = $item->primaryMedia()) {
                $thumbnailUrl = $thumbnailResource && $media->thumbnail()
                    ? $media->thumbnail()->assetUrl()
                    : $media->thumbnailUrl($thumbnailType);
                $thumbnailAltText = $media->altText();
            } else {
                $thumbnailUrl = null;
                $thumbnailAltText = null;
            }
            foreach ($itemDates as $key => $valueItemDate) {
                $event = [];
                $itemDate = $valueItemDate->value();
                $isEdtfStart = $valueItemDate->type() === 'edtf';
                $isEdtfEnd = !empty($itemDatesEnd[$key]) && $itemDatesEnd[$key]->type() === 'edtf';
                if ($isEdtfStart || $isEdtfEnd) {
                    [$dateStart, $dateEnd] = $isEdtfStart
                        ? $this->convertEdtfValue($valueItemDate)
                        : $this->convertAnyDate($itemDate, $this->renderYear);
                    if (!empty($itemDatesEnd[$key])) {
                        if ($isEdtfEnd) {
                            [$s2, $e2] = $this->convertEdtfValue($itemDatesEnd[$key]);
                            $dateEnd = $e2 ?? $s2 ?? $dateEnd;
                        } else {
                            [, $dateEnd2] = $this->convertTwoDates('', $itemDatesEnd[$key]->value(), $this->renderYear);
                            $dateEnd = $dateEnd2 ?? $dateEnd;
                        }
                    }
                } elseif (empty($itemDatesEnd[$key])) {
                    [$dateStart, $dateEnd] = $this->convertAnyDate($itemDate, $this->renderYear);
                } else {
                    [$dateStart, $dateEnd] = $this->convertTwoDates($itemDate, $itemDatesEnd[$key]->value(), $this->renderYear);
                }
                if (empty($dateStart)) {
                    continue;
                }
                if ($this->timelineJs === 'knightlab') {
                    $event['start_date'] = $this->dateToArray($dateStart);
                    if ($dateEnd !== null) {
                        $event['end_date'] = $this->dateToArray($dateEnd);
                    }
                    if ($isEdtfStart) {
                        $humanized = $this->humanizeEdtf($itemDate);
                        // When the EDTF value already expresses a range
                        // (interval), set display_date at the event level so
                        // Knightlab doesn't append the automatic end_date
                        // rendering.
                        if ($dateEnd !== null) {
                            $event['display_date'] = $humanized;
                        } else {
                            $event['start_date']['display_date'] = $humanized;
                        }
                    }
                    $event['text'] = [
                        'headline' => '<a href=' . $itemLink . '>' . $itemTitle . '</a>',
                    ];
                    if ($itemDescription) {
                        $event['text']['text'] = $itemDescription;
                    }
                    // If the record has a file attachment, include that.
                    // Limits based on returned JSON:
                    // If multiple images are attached to the record, it only
                    // shows the first.
                    // If a pdf is attached, it does not show it or indicate it.
                    // If an mp3 is attached in Files, it does not appear.
                    if ($thumbnailUrl) {
                        $event['media']['url'] = $thumbnailUrl;
                        $event['media']['link'] = $itemLink;
                        $event['media']['link_target'] = $linkToSelf ? null : '_blank';
                        if ($thumbnailAltText) {
                            $event['media']['alt'] = $thumbnailAltText;
                        }
                    }
                } else {
                    $event['start'] = $dateStart;
                    if ($dateEnd !== null) {
                        $event['end'] = $dateEnd;
                    }
                    $event['title'] = $itemTitle;
                    $event['link'] = $itemLink;
                    if ($itemDescription) {
                        $event['description'] = $itemDescription;
                    }
                    if ($thumbnailUrl) {
                        $event['image'] = $thumbnailUrl;
                    }
                }
                // Does not work with knighlab.
                $event['classname'] = $this->itemClass($item);
                if ($fieldsItem) {
                    $event['metadata'] = $this->resourceMetadata($item, $fieldsItem);
                }
                if ($itemGroup) {
                    $event['group'] = $itemGroup;
                }
                $events[] = $event;
            }
        }

        // Append markers.
        $groupLabel = $this->translator->translate('Events'); // @translate
        foreach ($markers as $markerData) {
            $markerData['group'] = $groupLabel;
            $events[] = $markerData;
        }

        $timeline = [];
        $timeline['dateTimeFormat'] = 'iso8601';
        if ($eras) {
            $timeline['eras'] = $eras;
        }
        $timeline['events'] = $events;

        return $timeline;
    }

    /**
     * Returns a string for timeline_json 'classname' attribute for an item.
     *
     * Default fields included are: 'item', item type name, all DC:Type values.
     */
    protected function itemClass(ItemRepresentation $item): string
    {
        $classes = ['item'];

        $type = $item->resourceClass() ? $item->resourceClass()->label() : null;

        if ($type) {
            $classes[] = $this->textToId($type);
        }
        $dcTypes = $item->value('dcterms:type', [
            'all' => true,
            'type' => 'literal',
            'default' => [],
        ]);
        foreach ($dcTypes as $type) {
            $classes[] = $this->textToId($type->value());
        }

        return implode(' ', $classes);
    }

    /**
     * Extract values from value annotations of a property.
     *
     * Get the annotation value of $annotationTerm from the first value of
     * $sourceTerm that has one.
     *
     * @return \Omeka\Api\Representation\ValueRepresentation[]
     */
    protected function valuesFromAnnotations(
        ItemRepresentation $item,
        string $sourceTerm,
        string $annotationTerm
    ): array {
        $sourceValues = $item->value($sourceTerm, ['all' => true]);
        foreach ($sourceValues as $sourceValue) {
            $annotation = $sourceValue->valueAnnotation();
            if (!$annotation) {
                continue;
            }
            $annValues = $annotation->value(
                $annotationTerm, ['all' => true]
            );
            if ($annValues) {
                return $annValues;
            }
        }
        return [];
    }

    /**
     * Extract the first value from value annotations of a property.
     */
    protected function firstValueFromAnnotations(
        ItemRepresentation $item,
        string $sourceTerm,
        string $annotationTerm
    ): ?string {
        $values = $this->valuesFromAnnotations(
            $item, $sourceTerm, $annotationTerm
        );
        return $values ? (string) $values[0] : null;
    }
}
