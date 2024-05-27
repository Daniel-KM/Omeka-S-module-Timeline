<?php declare(strict_types=1);

namespace Timeline\Mvc\Controller\Plugin;

use DateTime;
use DateTimeZone;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Omeka\Api\Manager as ApiManager;
use Omeka\Api\Representation\ItemRepresentation;

/**
 * Date parsing is adapted from module Numeric Data Type.
 * @see \NumericDataTypes\DataType\AbstractDateTimeDataType::getDateTimeFromValue()
 *
 * Copied in:
 * @see \BulkImport\Processor\DateTimeTrait
 * @see \Timeline\Mvc\Controller\Plugin\AbstractTimelineData
 */
abstract class AbstractTimelineData extends AbstractPlugin
{
    /**
     * @var \Omeka\Api\Manager
     */
    protected $api;

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

    /**
     * Minimum and maximum years.
     *
     * When converted to Unix timestamps, anything outside this range would
     * exceed the minimum or maximum range for a 64-bit integer.
     *
     * @var int
     */
    protected $yearMin = -292277022656;

    /**
     * @var int
     */
    protected $yearMax = 292277026595;

    /**
     * ISO 8601 datetime pattern
     *
     * The standard permits the expansion of the year representation beyond
     * 0000–9999, but only by prior agreement between the sender and the
     * receiver. Given that our year range is unusually large we shouldn't
     * require senders to zero-pad to 12 digits for every year. Users would have
     * to a) have prior knowledge of this unusual requirement, and b) convert
     * all existing ISO strings to accommodate it. This is needlessly
     * inconvenient and would be incompatible with most other systems. Instead,
     * we require the standard's zero-padding to 4 digits, but stray from the
     * standard by accepting non-zero padded integers beyond -9999 and 9999.
     *
     * Note that we only accept ISO 8601's extended format: the date segment
     * must include hyphens as separators, and the time and offset segments must
     * include colons as separators. This follows the standard's best practices,
     * which notes that "The basic format should be avoided in plain text."
     *
     * Unlike NumericDataTypes, allow non-padded date until 999 (no need 0999).
     *
     * @var string
     */
    protected $patternIso8601 = '^(?<date>(?<year>-?\d{1,})(-(?<month>\d{2}))?(-(?<day>\d{2}))?)(?<time>(T(?<hour>\d{2}))?(:(?<minute>\d{2}))?(:(?<second>\d{2}))?)(?<offset>((?<offset_hour>[+-]\d{2})?(:(?<offset_minute>\d{2}))?)|Z?)$';

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

        $this->renderYear = $args['render_year'] ?? static::$renderYears['default'];

        $propertyItemTitle = $args['item_title'] === 'default' ? '' : $args['item_title'];
        $propertyItemDescription = $args['item_description'] === 'default' ? '' : $args['item_description'];
        $propertyItemDate = $args['item_date'];
        $propertyItemDateEnd = $args['item_date_end'] ?? null;

        $eras = empty($args['eras']) ? [] : $this->eras($args['eras']);

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
            $itemDatesEnd = $propertyItemDateEnd
                ? $item->value($propertyItemDateEnd, ['all' => true])
                : [];
            $itemLink = empty($args['site_slug'])
                ? null
                : $item->siteUrl($args['site_slug']);
            if ($thumbnailResource && $item->thumbnail()) {
                $thumbnailUrl = $item->thumbnail()->assetUrl();
            } elseif ($media = $item->primaryMedia()) {
                $thumbnailUrl = $thumbnailResource && $media->thumbnail()
                    ? $media->thumbnail()->assetUrl()
                    : $media->thumbnailUrl($thumbnailType);
            } else {
                $thumbnailUrl = null;
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
                $event['start'] = $dateStart;
                if (!is_null($dateEnd)) {
                    $event['end'] = $dateEnd;
                }
                $event['title'] = $itemTitle;
                $event['link'] = $itemLink;
                $event['classname'] = $this->itemClass($item);
                if ($thumbnailUrl) {
                    $event['image'] = $thumbnailUrl;
                }
                $event['description'] = $itemDescription;
                $events[] = $event;
            }
        }

        $data = [];
        $data['dateTimeFormat'] = 'iso8601';
        if ($eras) {
            $data['eras'] = $eras;
        }
        $data['events'] = $events;

        return $data;
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
     * @todo See TimelineExhibitData
     */
    protected function eras(array $eras): array
    {
        $result = [];

        foreach (array_filter($eras) as $label => $dates) {
            if (strpos($dates, '/')) {
                [$startDate, $endDate] = array_map('trim', explode('/', $dates, 2));
            } else {
                $startDate = $endDate = trim($dates);
            }

            if (!strlen($startDate) && !strlen($endDate)) {
                continue;
            } elseif (!strlen($startDate)) {
                $startDate = $endDate;
            } elseif (!strlen($endDate)) {
                $endDate = $startDate;
            }

            // Check and normalize dates and years.
            /* // This process returns all components of the date.
            [$dateStart, $dateEnd] = $this->convertAnyDate($startDate .'/' . $endDate);
            if (!$dateStart || !$dateEnd) {
                continue;
            }
            $startDate = $this->dateToArray($dateStart);
            $endDate = $this->dateToArray($dateEnd);
            */
            $startDate = $this->dateToArray($startDate);
            $endDate = $this->dateToArray($endDate);
            if (empty($startDate['year']) || empty($endDate['year'])) {
                continue;
            }

            $result[] = [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'text' => [
                    'headline' => $label,
                ],
            ];
        }

        return $result;
    }

    /**
     * Extract year from a normalized date.
     */
    protected function dateToArray(?string $date): array
    {
        if (!$date) {
            return [];
        }

        $fullDate = $this->getDateTimeFromValue($date, true, false, false, false, true);
        /* // May return partial date.
        $emptyDate = [
            'year' => null,
            'month' => null,
            'day' => null,
            'hour' => null,
            'minute' => null,
            'second' => null,
            'millisecond' => null,
        ];
        return array_filter(array_intersect_key($fullDate, $emptyDate), 'is_int');
        */

        $result = ['year' => (int) $fullDate['year']];
        if (isset($fullDate['month'])) {
            $result['month'] = (int) $fullDate['month'];
            if (isset($fullDate['day'])) {
                $result['day'] = (int) $fullDate['day'];
                if (isset($fullDate['hour'])) {
                    $result['hour'] = (int) $fullDate['hour'];
                    if (isset($fullDate['minute'])) {
                        $result['minute'] = (int) $fullDate['minute'];
                        if (isset($fullDate['second'])) {
                            $result['second'] = (int) $fullDate['second'];
                            if (isset($fullDate['millisecond'])) {
                                $result['millisecond'] = (int) $fullDate['millisecond'];
                            }
                        }
                    }
                }
            }
        }

        return $result;
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

    /**
     * Get the decomposed date/time and DateTime object from an ISO 8601 value.
     *
     * Use $defaultFirst to set the default of each datetime component to its
     * first (true) or last (false) possible integer, if the specific component
     * is not passed with the value.
     *
     * Also used to validate the datetime since validation is a side effect of
     * parsing the value into its component datetime pieces.
     *
     * @return DateTime|string|null
     *
     * Adapted from module Numeric Data Type.
     * @see \NumericDataTypes\DataType\AbstractDateTimeDataType::getDateTimeFromValue()
     *
     * Copied in:
     * @see \BulkImport\Processor\DateTimeTrait
     * @see \Timeline\Mvc\Controller\Plugin\AbstractTimelineData
     *
     * @todo See TimelineExhibitData
     */
    protected function getDateTimeFromValue($value, bool $defaultFirst = true, bool $formatted = false, bool $fullDatetime = false, bool $forSql = false, $fullData = false)
    {
        // Match against ISO 8601, allowing for reduced accuracy.
        $matches = [];
        $isMatch = preg_match('/' . $this->patternIso8601 . '/', (string) $value, $matches);
        if (!$isMatch) {
            return null;
        }
        $matches = array_filter($matches); // remove empty values
        // An hour requires a day.
        if (isset($matches['hour']) && !isset($matches['day'])) {
            return null;
        }
        // An offset requires a time.
        if (isset($matches['offset']) && !isset($matches['time'])) {
            return null;
        }

        // Set the datetime components included in the passed value.
        $dateTime = [
            'value' => $value,
            'date_value' => $matches['date'],
            'time_value' => $matches['time'] ?? null,
            'offset_value' => $matches['offset'] ?? null,
            'year' => (int) $matches['year'],
            'month' => isset($matches['month']) ? (int) $matches['month'] : null,
            'day' => isset($matches['day']) ? (int) $matches['day'] : null,
            'hour' => isset($matches['hour']) ? (int) $matches['hour'] : null,
            'minute' => isset($matches['minute']) ? (int) $matches['minute'] : null,
            'second' => isset($matches['second']) ? (int) $matches['second'] : null,
            'offset_hour' => isset($matches['offset_hour']) ? (int) $matches['offset_hour'] : null,
            'offset_minute' => isset($matches['offset_minute']) ? (int) $matches['offset_minute'] : null,
        ];

        // Set the normalized datetime components. Each component not included
        // in the passed value is given a default value.
        $dateTime['month_normalized'] = $dateTime['month'] ?? ($defaultFirst ? 1 : 12);
        // The last day takes special handling, as it depends on year/month.
        $dateTime['day_normalized'] = $dateTime['day']
        ?? ($defaultFirst ? 1 : $this->getLastDay($dateTime['year'], $dateTime['month_normalized']));
        $dateTime['hour_normalized'] = $dateTime['hour'] ?? ($defaultFirst ? 0 : 23);
        $dateTime['minute_normalized'] = $dateTime['minute'] ?? ($defaultFirst ? 0 : 59);
        $dateTime['second_normalized'] = $dateTime['second'] ?? ($defaultFirst ? 0 : 59);
        $dateTime['offset_hour_normalized'] = $dateTime['offset_hour'] ?? 0;
        $dateTime['offset_minute_normalized'] = $dateTime['offset_minute'] ?? 0;
        // Set the UTC offset (+00:00) if no offset is provided.
        $dateTime['offset_normalized'] = isset($dateTime['offset_value'])
        ? ('Z' === $dateTime['offset_value'] ? '+00:00' : $dateTime['offset_value'])
        : '+00:00';

        // Validate ranges of the datetime component.
        if (($this->yearMin > $dateTime['year']) || ($this->yearMax < $dateTime['year'])) {
            return null;
        }
        if ((1 > $dateTime['month_normalized']) || (12 < $dateTime['month_normalized'])) {
            return null;
        }
        if ((1 > $dateTime['day_normalized']) || (31 < $dateTime['day_normalized'])) {
            return null;
        }
        if ((0 > $dateTime['hour_normalized']) || (23 < $dateTime['hour_normalized'])) {
            return null;
        }
        if ((0 > $dateTime['minute_normalized']) || (59 < $dateTime['minute_normalized'])) {
            return null;
        }
        if ((0 > $dateTime['second_normalized']) || (59 < $dateTime['second_normalized'])) {
            return null;
        }
        if ((-23 > $dateTime['offset_hour_normalized']) || (23 < $dateTime['offset_hour_normalized'])) {
            return null;
        }
        if ((0 > $dateTime['offset_minute_normalized']) || (59 < $dateTime['offset_minute_normalized'])) {
            return null;
        }

        // Set the ISO 8601 format.
        if (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['minute']) && isset($dateTime['second']) && isset($dateTime['offset_value'])) {
            $format = 'Y-m-d\TH:i:sP';
        } elseif (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['minute']) && isset($dateTime['offset_value'])) {
            $format = 'Y-m-d\TH:iP';
        } elseif (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['offset_value'])) {
            $format = 'Y-m-d\THP';
        } elseif (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['minute']) && isset($dateTime['second'])) {
            $format = 'Y-m-d\TH:i:s';
        } elseif (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['minute'])) {
            $format = 'Y-m-d\TH:i';
        } elseif (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour'])) {
            $format = 'Y-m-d\TH';
        } elseif (isset($dateTime['month']) && isset($dateTime['day'])) {
            $format = 'Y-m-d';
        } elseif (isset($dateTime['month'])) {
            $format = 'Y-m';
        } else {
            $format = 'Y';
        }
        $dateTime['format_iso8601'] = $format;

        // Set the render format.
        if (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['minute']) && isset($dateTime['second']) && isset($dateTime['offset_value'])) {
            $format = 'F j, Y H:i:s P';
        } elseif (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['minute']) && isset($dateTime['offset_value'])) {
            $format = 'F j, Y H:i P';
        } elseif (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['offset_value'])) {
            $format = 'F j, Y H P';
        } elseif (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['minute']) && isset($dateTime['second'])) {
            $format = 'F j, Y H:i:s';
        } elseif (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['minute'])) {
            $format = 'F j, Y H:i';
        } elseif (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour'])) {
            $format = 'F j, Y H';
        } elseif (isset($dateTime['month']) && isset($dateTime['day'])) {
            $format = 'F j, Y';
        } elseif (isset($dateTime['month'])) {
            $format = 'F Y';
        } else {
            $format = 'Y';
        }
        $dateTime['format_render'] = $format;

        // Adding the DateTime object here to reduce code duplication. To ensure
        // consistency, use Coordinated Universal Time (UTC) if no offset is
        // provided. This avoids automatic adjustments based on the server's
        // default timezone.
        // With strict type, "now" is required.
        $dateTime['date'] = new DateTime('now', new DateTimeZone($dateTime['offset_normalized']));
        $dateTime['date']->setDate(
            $dateTime['year'],
            $dateTime['month_normalized'],
            $dateTime['day_normalized']
        )->setTime(
            $dateTime['hour_normalized'],
            $dateTime['minute_normalized'],
            $dateTime['second_normalized']
        );

        if ($forSql) {
            return $dateTime['date']->format('Y-m-d H:i:s');
        }

        if ($formatted) {
            return $fullDatetime
                ? $dateTime['date']->format('Y-m-d\TH:i:s')
                : $dateTime['date']->format($dateTime['format_iso8601']);
        }

        return $fullData
            ? $dateTime
            : $dateTime['date'];
    }

    /**
     * Get the last day of a given year/month.
     */
    protected function getLastDay(int $year, int $month): int
    {
        switch ($month) {
            case 2:
                // February (accounting for leap year)
                $leapYear = date('L', mktime(0, 0, 0, 1, 1, $year));
                return $leapYear ? 29 : 28;
            case 4:
            case 6:
            case 9:
            case 11:
                // April, June, September, November
                return 30;
            default:
                // January, March, May, July, August, October, December
                return 31;
        }
    }
}
