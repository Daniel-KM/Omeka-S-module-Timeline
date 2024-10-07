<?php declare(strict_types=1);

namespace Timeline\Mvc\Controller\Plugin;

use DateTime;
use DateTimeZone;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;

/**
 * Date parsing is adapted from module Numeric Data Type.
 * @see \NumericDataTypes\DataType\AbstractDateTimeDataType::getDateTimeFromValue()
 *
 * Copied in:
 * @see \BulkImport\Processor\DateTimeTrait
 * @see \Timeline\Mvc\Controller\Plugin\TraitTimelineData
 */
trait TraitTimelineData
{
    /**
     * @var bool
     */
    protected $isCosmological = false;

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
     * Unlike NumericDataTypes, allow non-padded date until 999 (no need 0999)
     * and allow missing ":" between hours and minutes in offset.
     *
     * @var string
     */
    protected $patternIso8601 = '^(?<date>(?<year>-?\d{1,})(-(?<month>\d{2}))?(-(?<day>\d{2}))?)(?<time>(T(?<hour>\d{2}))?(:(?<minute>\d{2}))?(:(?<second>\d{2}))?)(?<offset>((?<offset_hour>[+-]\d{2})?(:?(?<offset_minute>\d{2}))?)|Z?)$';

    /**
     * Get a single metadata from a resource for custom timelines.
     */
    protected function resourceMetadataSingle(AbstractResourceEntityRepresentation $resource, ?string $field): ?string
    {
        if (!$field) {
            return null;
        } elseif ($field === 'resource_class') {
            return $resource->resourceClass();
        } elseif ($field === 'resource_class_label') {
            $value = $resource->resourceClass();
            return $value ? $this->translate->__invoke($value->label()) : null;
        } elseif ($field === 'resource_template_label') {
            $value = $resource->resourceTemplate();
            return $value ? $value->label() : null;
        } elseif ($field === 'owner_name') {
            $value = $resource->owner();
            return $value ? $value->name() : null;
        } else {
            return $resource->value($field, ['default' => null]);
        }
    }

    /**
     * Get a list of metadata from a resource for custom timelines.
     */
    protected function resourceMetadata(AbstractResourceEntityRepresentation $resource, array $fields): array
    {
        $result = [];
        foreach ($fields as $field) {
            if ($field === 'resource_class') {
                $value = $resource->resourceClass();
                if ($value) {
                    $result['o:resource_class'][] = ['value' => $value->term()];
                }
            } elseif ($field === 'resource_class_label') {
                $value = $resource->resourceClass();
                if ($value) {
                    $result['o:resource_class'][] = ['value' => $this->translate->__invoke($value->label())];
                }
            } elseif ($field === 'resource_template_label') {
                $value = $resource->resourceTemplate();
                if ($value) {
                    $result['o:resource_template'][] = ['value' => $value->label()];
                }
            } elseif ($field === 'owner_name') {
                $value = $resource->owner();
                if ($value) {
                    $result['o:owner'][] = ['value' => $value->name()];
                }
            } else {
                foreach ($resource->value($field, ['all' => true]) as $value) {
                    $result[$field][] = ['value' => (string) $value];
                }
            }
        }
        return $result;
    }

    /**
     * @todo See TimelineExhibitData
     */
    protected function extractEras(array $eras): array
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
     * @todo See TimelineExhibitData
     */
    protected function extractMarkers(array $markers): array
    {
        return $this->timelineJs === 'knightlab'
            ? $this->extractMarkersKnigthlab($markers)
            : $this->extractMarkersSimile($markers);
    }

    protected function extractMarkersKnigthlab(array $markers): array
    {
        $result = [];

        foreach (array_filter($markers) as $data) {
            $heading = $data['heading'] ?? null;
            $dates = $data['dates'] ?? null;
            $body = $data['body'] ?? null;
            if (!$heading || !$dates) {
                continue;
            }
            [$dateStart, $dateEnd] = strpos($dates, '/') ? explode('/', $dates) : [$dates, null];
            if (empty($dateStart)) {
                continue;
            }
            $event = [];
            $event['start_date'] = $this->date($dateStart);
            if (!is_null($dateEnd)) {
                $event['end_date'] = $this->date($dateEnd);
            }
            $event['text'] = [
                'headline' => $heading,
            ];
            if ($body) {
                $event['text']['text'] = $body;
            }
            // Does not work with knighlab.
            $event['classname'] = 'extra-marker';
            $result[] = $event;
        }

        return $result;
    }

    protected function extractMarkersSimile(array $markers): array
    {
        $result = [];

        foreach (array_filter($markers) as $data) {
            $heading = $data['heading'] ?? null;
            $dates = $data['dates'] ?? null;
            $body = $data['body'] ?? null;
            if (!$heading || !$dates) {
                continue;
            }
            [$dateStart, $dateEnd] = $this->convertAnyDate($dates, $this->renderYear);
            if (empty($dateStart)) {
                continue;
            }
            $event = [];
            $event['start'] = $dateStart;
            if (!is_null($dateEnd)) {
                $event['end'] = $dateEnd;
            }
            $event['title'] = $heading;
            if ($body) {
                $event['description'] = $body;
            }
            $event['classname'] = 'extra-marker';
            $result[] = $event;
        }

        return $result;
    }

    /**
     * Extract year from a normalized date.
     *
     * @todo Merge with \Timeline\Mvc\Controller\Plugin\TraitTimelineData::date()
     * @see \Timeline\Mvc\Controller\Plugin\TraitTimelineData::date()
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

        if (!$fullDate) {
            return [];
        }

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
     * Get the decomposed date/time and DateTime object from an ISO 8601 value.
     *
     * Use $defaultFirst to set the default of each datetime component to its
     * first (true) or last (false) possible integer, if the specific component
     * is not passed with the value.
     *
     * Also used to validate the datetime since validation is a side effect of
     * parsing the value into its component datetime pieces.
     *
     * @return DateTime|string|array|null
     *
     * Adapted from module Numeric Data Type.
     * @see \NumericDataTypes\DataType\AbstractDateTimeDataType::getDateTimeFromValue()
     *
     * Copied in:
     * @see \BulkImport\Processor\DateTimeTrait
     * @see \Timeline\Mvc\Controller\Plugin\TraitTimelineData
     *
     * @todo See TimelineExhibitData
     */
    protected function getDateTimeFromValue(
        $value,
        bool $defaultFirst = true,
        bool $formatted = false,
        bool $fullDatetime = false,
        bool $forSql = false,
        $fullData = false
    ) {
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
    protected function getLastDay($year, $month): int
    {
        $month = (int) $month;
        if (in_array($month, [4, 6, 9, 11], true)) {
            return 30;
        } elseif ($month === 2) {
            return date('L', mktime(0, 0, 0, 1, 1, $year)) ? 29 : 28;
        } else {
            return 31;
        }
    }

    /**
     * Convert a date from string to array.
     *
     * @param string|\Omeka\Api\Representation\ValueRepresentation $date
     *
     * @todo Merge with \Timeline\Mvc\Controller\Plugin\TraitTimelineData::dateToArray()
     * @see \Timeline\Mvc\Controller\Plugin\TraitTimelineData::dateToArray()
     */
    protected function date($date, ?string $displayDate = null): ?array
    {
        $displayDate = strlen((string) $displayDate) ? $displayDate : null;
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
            $dateTime = $this->getDateTimeFromValue($date->value(), true, false, false, false, true);
            if (!is_array($dateTime)) {
                return null;
            }
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
            $dateTimeO->setTimestamp($timestamp);
            $parts['year'] = (int) $dateTimeO->format('Y');
            $parts['month'] = (int) $dateTimeO->format('m');
            $parts['day'] = (int) $dateTimeO->format('d');
        }

        $parts = array_filter($parts, fn ($v) => !is_null($v));

        if (!isset($parts['year'])) {
            return null;
        }

        if ($this->isCosmological) {
            return array_intersect_key($parts, ['year' => null, 'display_date' => null]);
        }

        return $parts;
    }
}
