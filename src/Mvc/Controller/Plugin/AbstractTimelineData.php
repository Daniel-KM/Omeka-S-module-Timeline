<?php declare(strict_types=1);

namespace Timeline\Mvc\Controller\Plugin;

use DateTime;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Omeka\Api\Manager as ApiManager;
use Omeka\Api\Representation\ItemRepresentation;

abstract class AbstractTimelineData extends AbstractPlugin
{
    use TraitTimelineData;

    /**
     * @var \Omeka\Api\Manager
     */
    protected $api;

    /**
     * @var \Laminas\I18n\View\Helper\Translate
     */
    protected $translate;

    /**
     * @var string "simile" or "knightlab".
     */
    protected $timelineJs = null;

    public static $renderYears = [
        'january_1' => 'january_1',
        'july_1' => 'july_1',
        'december_31' => 'december_31',
        'june_30' => 'june_30',
        'full_year' => 'full_year',
        // Render a year as a range: use convertSingleDate().
        'skip' => 'skip',
        'default' => 'january_1',
    ];

    protected $renderYear;

    public function __construct(ApiManager $api)
    {
        $this->api = $api;
    }

    /**
     * Extract titles, descriptions and dates from the timeline’s pool of items.
     */
    public function __invoke(array $itemPool, array $args): array
    {
        $events = [];

        $controller = $this->getController();
        $this->translate = $controller->viewHelpers()->get('translate');

        $this->renderYear = $args['render_year'] ?? static::$renderYears['default'];

        $propertyItemTitle = $args['item_title'] === 'default' ? '' : $args['item_title'];
        $propertyItemDescription = $args['item_description'] === 'default' ? '' : $args['item_description'];
        $propertyItemDate = $args['item_date'];
        $propertyItemDateEnd = $args['item_date_end'] ?? null;
        $fieldsItem = $args['item_metadata'] ?? [];
        $fieldGroup = $args['group'] ?? null;
        $groupDefault = empty($args['group_default']) ? null : $args['group_default'];

        $eras = empty($args['eras']) ? [] : $this->extractEras($args['eras']);
        $markers = empty($args['markers']) ? [] : $this->extractMarkers($args['markers']);

        $thumbnailType = empty($args['thumbnail_type']) ? 'medium' : $args['thumbnail_type'];
        $thumbnailResource = !empty($args['thumbnail_resource']);

        $params = $itemPool;
        $params['property'][] = ['joiner' => 'and', 'property' => $args['item_date_id'], 'type' => 'ex'];

        // To avoid overflow when requesting all items, use a loop with id.
        $itemIds = $this->api->search('items', $params, ['returnScalar' => 'id'])->getContent();

        /** @var \Omeka\Api\Representation\ItemRepresentation $item */
        foreach (array_chunk($itemIds, 100) as $idsChunk) foreach ($this->api->search('items', ['id' => $idsChunk])->getContent() as $item) {
            // All items without dates are already automatically removed.
            $itemDates = $item->value($propertyItemDate, ['all' => true]);
            $itemTitle = strip_tags($propertyItemTitle ? (string) $item->value($propertyItemTitle) : $item->displayTitle());
            $itemDescription = $this->snippet($propertyItemDescription ? (string) $item->value($propertyItemDescription) : $item->displayDescription(), 200);
            $itemGroup = $this->resourceMetadataSingle($item, $fieldGroup) ?: $groupDefault;
            $itemGroup = $itemGroup ? strip_tags($itemGroup) : null;
            $itemDatesEnd = $propertyItemDateEnd
                ? $item->value($propertyItemDateEnd, ['all' => true])
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
                if (empty($itemDatesEnd[$key])) {
                    [$dateStart, $dateEnd] = $this->convertAnyDate($itemDate, $this->renderYear);
                } else {
                    [$dateStart, $dateEnd] = $this->convertTwoDates($itemDate, $itemDatesEnd[$key]->value(), $this->renderYear);
                }
                if (empty($dateStart)) {
                    continue;
                }
                if ($this->timelineJs === 'knightlab') {
                    $event['start_date'] = $this->dateToArray($dateStart);
                    if (!is_null($dateEnd)) {
                        $event['end_date'] = $this->dateToArray($dateEnd);
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
                        $event['media']['link_target'] = '_blank';
                        if ($thumbnailAltText) {
                            $event['media']['alt'] = $thumbnailAltText;
                        }
                    }
                } else {
                    $event['start'] = $dateStart;
                    if (!is_null($dateEnd)) {
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
        $groupLabel = $this->translate->__invoke('Events'); // @translate
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
     * Generates an ISO-8601 date from a date string
     *
     * @param string $date
     * @param string renderYear Force the format of a single number as a year.
     * @return string ISO-8601 date
     */
    protected function convertDate(string $date, ?string $renderYear = null): ?string
    {
        if (empty($renderYear)) {
            $renderYear = $this->renderYear;
        }

        // Check if the date is a single number.
        if (preg_match('/^-?\d{1,4}$/', $date)) {
            // Normalize the year.
            $date = $date < 0
                ? '-' . str_pad(mb_substr($date, 1), 4, '0', STR_PAD_LEFT)
                : str_pad($date, 4, '0', STR_PAD_LEFT);
            switch ($renderYear) {
                case static::$renderYears['january_1']:
                    $dateOut = $date . '-01-01' . 'T00:00:00+00:00';
                    break;
                case static::$renderYears['july_1']:
                    $dateOut = $date . '-07-01' . 'T00:00:00+00:00';
                    break;
                case static::$renderYears['december_31']:
                    $dateOut = $date . '-12-31' . 'T00:00:00+00:00';
                    break;
                case static::$renderYears['june_30']:
                    $dateOut = $date . '-06-30' . 'T00:00:00+00:00';
                    break;
                case static::$renderYears['full_year']:
                    // Render a year as a range: use timeline_convert_single_date().
                case static::$renderYears['skip']:
                default:
                    $dateOut = false;
                    break;
            }
            return $dateOut;
        }

        try {
            $dateTime = new DateTime($date);

            $dateOut = $dateTime->format(DateTime::ISO8601);
            $dateOut = preg_replace('/^(-?)(\d{3}-)/', '${1}0\2', $dateOut);
            $dateOut = preg_replace('/^(-?)(\d{2}-)/', '${1}00\2', $dateOut);
            $dateOut = preg_replace('/^(-?)(\d{1}-)/', '${1}000\2', $dateOut);
        } catch (\Exception $e) {
            $dateOut = null;
        }

        return $dateOut;
    }

    /**
     * Generates an array of one or two ISO-8601 dates from a string.
     *
     * @todo manage the case where the start is empty and the end is set.
     *
     * @param string $date
     * @param string renderYear Force the format of a single number as a year.
     * @return array Array of two dates.
     */
    protected function convertAnyDate(string $date, ?string $renderYear = null): array
    {
        return $this->convertTwoDates($date, '', $renderYear);
    }

    /**
     * Generates an array of one or two ISO-8601 dates from two strings.
     *
     * @todo manage the case where the start is empty and the end is set.
     *
     * @param string $date
     * @param string $dateEnd
     * @param string renderYear Force the format of a single number as a year.
     * @return array Array of two dates.
     */
    protected function convertTwoDates($date, $dateEnd, ?string $renderYear = null): array
    {
        if (empty($renderYear)) {
            $renderYear = $this->renderYear;
        }

        // Manage a common issue (2016-2017).
        $dateArray = preg_match('/^\d{4}-\d{4}$/', $date)
            ? array_map('trim', explode('-', $date))
            : array_map('trim', explode('/', $date));

        // A range of dates.
        if (count($dateArray) == 2) {
            return $this->convertRangeDates($dateArray, $renderYear);
        }

        $dateEndArray = explode('/', $dateEnd);
        $dateEnd = trim(reset($dateEndArray));

        // A single date, or a range when the two dates are years and when the
        // render is "full_year".
        if (empty($dateEnd)) {
            return $this->convertSingleDate($dateArray[0], $renderYear);
        }

        return $this->convertRangeDates([$dateArray[0], $dateEnd], $renderYear);
    }

    /**
     * Generates an ISO-8601 date from a date string, with an exception for
     * "full_year" render, that returns two dates.
     *
     * @param string $date
     * @param string renderYear Force the format of a single number as a year.
     * @return array Array of two dates.
     */
    protected function convertSingleDate($date, ?string $renderYear = null): array
    {
        if (empty($renderYear)) {
            $renderYear = $this->renderYear;
        }

        // Manage a special case for render "full_year" with a single number.
        if ($renderYear == static::$renderYears['full_year'] && preg_match('/^-?\d{1,4}$/', $date)) {
            $dateStartValue = $this->convertDate(strval($date), static::$renderYears['january_1']);
            $dateEndValue = $this->convertDate(strval($date), static::$renderYears['december_31']);
            return [$dateStartValue, $dateEndValue];
        }

        // Only one date.
        $dateStartValue = $this->convertDate(strval($date), $renderYear);
        return [$dateStartValue, null];
    }

    /**
     * Generates two ISO-8601 dates from an array of two strings.
     *
     * By construction, no "full_year" is returned.
     *
     * @param array $dates
     * @param string renderYear Force the format of a single number as a year.
     * @return array $dates
     */
    protected function convertRangeDates($dates, ?string $renderYear = null): array
    {
        if (!is_array($dates)) {
            return [null, null];
        }

        if (empty($renderYear)) {
            $renderYear = $this->renderYear;
        }

        $dateStart = $dates[0];
        $dateEnd = $dates[1];

        // Check if the date are two numbers (years).
        if ($renderYear == static::$renderYears['skip']) {
            $dateStartValue = $this->convertDate(strval($dateStart), $renderYear);
            $dateEndValue = $this->convertDate(strval($dateEnd), $renderYear);
            return [$dateStartValue, $dateEndValue];
        }

        // Check if there is one number and one date.
        if (!preg_match('/^-?\d{1,4}$/', $dateStart)) {
            if (!preg_match('/^-?\d{1,4}$/', $dateEnd)) {
                // TODO Check order to force the start or the end.
                $dateStartValue = $this->convertDate(strval($dateStart), $renderYear);
                $dateEndValue = $this->convertDate(strval($dateEnd), $renderYear);
                return [$dateStartValue, $dateEndValue];
            }
            // Force the format for the end.
            $dateStartValue = $this->convertDate(strval($dateStart), $renderYear);
            if ($renderYear == static::$renderYears['full_year']) {
                $renderYear = static::$renderYears['december_31'];
            }
            $dateEndValue = $this->convertDate(strval($dateEnd), $renderYear);
            return [$dateStartValue, $dateEndValue];
        }
        // The start is a year.
        elseif (!preg_match('/^-?\d{1,4}$/', $dateEnd)) {
            // Force the format of the start.
            $dateEndValue = $this->convertDate(strval($dateEnd), $renderYear);
            if ($renderYear == static::$renderYears['full_year']) {
                $renderYear = static::$renderYears['january_1'];
            }
            $dateStartValue = $this->convertDate(strval($dateStart), $renderYear);
            return [$dateStartValue, $dateEndValue];
        }

        $dateStart = (int) $dateStart;
        $dateEnd = (int) $dateEnd;

        // Same years.
        if ($dateStart == $dateEnd) {
            $dateStartValue = $this->convertDate(strval($dateStart), static::$renderYears['january_1']);
            $dateEndValue = $this->convertDate(strval($dateEnd), static::$renderYears['december_31']);
            return [$dateStartValue, $dateEndValue];
        }

        // The start and the end are years, so reorder them (may be useless).
        if ($dateStart > $dateEnd) {
            $kdate = $dateEnd;
            $dateEnd = $dateStart;
            $dateStart = $kdate;
        }

        switch ($renderYear) {
            case static::$renderYears['july_1']:
                $dateStartValue = $this->convertDate(strval($dateStart), static::$renderYears['july_1']);
                $dateEndValue = $this->convertDate(strval($dateEnd), static::$renderYears['june_30']);
                return [$dateStartValue, $dateEndValue];
            case static::$renderYears['january_1']:
                $dateStartValue = $this->convertDate(strval($dateStart), static::$renderYears['january_1']);
                $dateEndValue = $this->convertDate(strval($dateEnd), static::$renderYears['january_1']);
                return [$dateStartValue, $dateEndValue];
            case static::$renderYears['full_year']:
            default:
                $dateStartValue = $this->convertDate(strval($dateStart), static::$renderYears['january_1']);
                $dateEndValue = $this->convertDate(strval($dateEnd), static::$renderYears['december_31']);
                return [$dateStartValue, $dateEndValue];
        }
    }

    /**
     * Remove html tags and truncate a string to the specified length.
     *
     * @param string $string
     * @param int $length
     * @return string
     */
    protected function snippet($string, $length): string
    {
        $str = strip_tags((string) $string);
        return mb_strlen($str) <= $length ? $str : mb_substr($str, 0, $length - 1) . '&hellip;';
    }

    /**
     * Convert a word or phrase to a valid HTML ID.
     *
     * For example: 'Foo Bar' becomes 'foo-bar'.
     *
     * This function converts to lowercase, replaces whitespace with hyphens,
     * removes all non-alphanumerics, removes leading or trailing delimiters,
     * and optionally prepends a piece of text.
     *
     * See Omeka Classic application/libraries/globals.php text_to_id()
     *
     * @package Omeka\Function\Text
     * @param string $text The text to convert
     * @param string $prepend Another string to prepend to the ID
     * @param string $delimiter The delimiter to use (- by default)
     * @return string
     */
    protected function textToId($text, ?string $prepend = null, string $delimiter = '-'): string
    {
        $text = mb_strtolower((string) $text);
        $id = preg_replace('/\s/', $delimiter, $text);
        $id = preg_replace('/[^\w\-]/', '', $id);
        $id = trim($id, $delimiter);
        return strlen((string) $prepend) ? $prepend . $delimiter . $id : $id;
    }
}
