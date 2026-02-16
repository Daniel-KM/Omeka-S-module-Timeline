<?php declare(strict_types=1);

namespace TimelineTest\Mvc\Controller\Plugin;

use PHPUnit\Framework\TestCase;
use Timeline\Mvc\Controller\Plugin\TraitTimelineData;

/**
 * Unit tests for TraitTimelineData date parsing methods.
 *
 * Uses a simple concrete class to test the trait methods directly.
 */
class TraitTimelineDataTest extends TestCase
{
    /**
     * @var object Instance using TraitTimelineData.
     */
    protected $helper;

    public function setUp(): void
    {
        $this->helper = new class {
            use TraitTimelineData;

            public function __construct()
            {
                $this->renderYear = 'january_1';
            }

            // Expose protected methods for testing.
            public function callGetDateTimeFromValue($value, $defaultFirst = true, $formatted = false, $fullDatetime = false, $forSql = false, $fullData = false)
            {
                return $this->getDateTimeFromValue($value, $defaultFirst, $formatted, $fullDatetime, $forSql, $fullData);
            }

            public function callConvertDate(string $date, ?string $renderYear = null): ?string
            {
                return $this->convertDate($date, $renderYear);
            }

            public function callConvertAnyDate(string $date, ?string $renderYear = null): array
            {
                return $this->convertAnyDate($date, $renderYear);
            }

            public function callConvertSingleDate($date, ?string $renderYear = null): array
            {
                return $this->convertSingleDate($date, $renderYear);
            }

            public function callConvertRangeDates($dates, ?string $renderYear = null): array
            {
                return $this->convertRangeDates($dates, $renderYear);
            }

            public function callDate($date, ?string $displayDate = null): ?array
            {
                return $this->date($date, $displayDate);
            }

            public function callDateToArray(?string $date): array
            {
                return $this->dateToArray($date);
            }

            public function callSnippet(string $string, int $length): string
            {
                return $this->snippet($string, $length);
            }

            public function callTextToId($text, ?string $prepend = null, string $delimiter = '-'): string
            {
                return $this->textToId($text, $prepend, $delimiter);
            }

            public function callGetLastDay($year, $month): int
            {
                return $this->getLastDay($year, $month);
            }

            public function setRenderYear(string $value): void
            {
                $this->renderYear = $value;
            }

            public function setIsCosmological(bool $value): void
            {
                $this->isCosmological = $value;
            }
        };
    }

    // getDateTimeFromValue() tests.

    public function testGetDateTimeFromValueFullDate(): void
    {
        $result = $this->helper->callGetDateTimeFromValue('2020-06-15', true, false, false, false, true);
        $this->assertIsArray($result);
        $this->assertEquals(2020, $result['year']);
        $this->assertEquals(6, $result['month']);
        $this->assertEquals(15, $result['day']);
    }

    public function testGetDateTimeFromValueYearOnly(): void
    {
        $result = $this->helper->callGetDateTimeFromValue('2020', true, false, false, false, true);
        $this->assertIsArray($result);
        $this->assertEquals(2020, $result['year']);
        $this->assertNull($result['month']);
        $this->assertNull($result['day']);
    }

    public function testGetDateTimeFromValueYearMonth(): void
    {
        $result = $this->helper->callGetDateTimeFromValue('2020-03', true, false, false, false, true);
        $this->assertIsArray($result);
        $this->assertEquals(2020, $result['year']);
        $this->assertEquals(3, $result['month']);
        $this->assertNull($result['day']);
    }

    public function testGetDateTimeFromValueWithTime(): void
    {
        $result = $this->helper->callGetDateTimeFromValue('2020-06-15T14:30:00', true, false, false, false, true);
        $this->assertIsArray($result);
        $this->assertEquals(2020, $result['year']);
        $this->assertEquals(14, $result['hour']);
        $this->assertEquals(30, $result['minute']);
        $this->assertEquals(0, $result['second']);
    }

    public function testGetDateTimeFromValueWithOffset(): void
    {
        $result = $this->helper->callGetDateTimeFromValue('2020-06-15T14:30:00+02:00', true, false, false, false, true);
        $this->assertIsArray($result);
        $this->assertEquals(2, $result['offset_hour']);
        $this->assertEquals(0, $result['offset_minute']);
    }

    public function testGetDateTimeFromValueWithZuluOffset(): void
    {
        $result = $this->helper->callGetDateTimeFromValue('2020-06-15T14:30:00Z', true, false, false, false, true);
        $this->assertIsArray($result);
        $this->assertEquals('+00:00', $result['offset_normalized']);
    }

    public function testGetDateTimeFromValueNegativeYear(): void
    {
        $result = $this->helper->callGetDateTimeFromValue('-0500', true, false, false, false, true);
        $this->assertIsArray($result);
        $this->assertEquals(-500, $result['year']);
    }

    public function testGetDateTimeFromValueInvalidReturnsNull(): void
    {
        $this->assertNull($this->helper->callGetDateTimeFromValue('not-a-date'));
    }

    public function testGetDateTimeFromValueEmptyReturnsNull(): void
    {
        $this->assertNull($this->helper->callGetDateTimeFromValue(''));
    }

    public function testGetDateTimeFromValueFormattedIso(): void
    {
        $result = $this->helper->callGetDateTimeFromValue('2020-06-15', true, true);
        $this->assertIsString($result);
        $this->assertEquals('2020-06-15', $result);
    }

    public function testGetDateTimeFromValueFormattedYearOnly(): void
    {
        $result = $this->helper->callGetDateTimeFromValue('2020', true, true);
        $this->assertIsString($result);
        $this->assertEquals('2020', $result);
    }

    public function testGetDateTimeFromValueForSql(): void
    {
        $result = $this->helper->callGetDateTimeFromValue('2020-06-15T14:30:00', true, false, false, true);
        $this->assertIsString($result);
        $this->assertEquals('2020-06-15 14:30:00', $result);
    }

    public function testGetDateTimeFromValueReturnsDateTime(): void
    {
        $result = $this->helper->callGetDateTimeFromValue('2020-06-15');
        $this->assertInstanceOf(\DateTime::class, $result);
    }

    public function testGetDateTimeFromValueDefaultFirstTrue(): void
    {
        $result = $this->helper->callGetDateTimeFromValue('2020', true, false, false, false, true);
        $this->assertEquals(1, $result['month_normalized']);
        $this->assertEquals(1, $result['day_normalized']);
    }

    public function testGetDateTimeFromValueDefaultFirstFalse(): void
    {
        $result = $this->helper->callGetDateTimeFromValue('2020', false, false, false, false, true);
        $this->assertEquals(12, $result['month_normalized']);
        $this->assertEquals(31, $result['day_normalized']);
    }

    public function testGetDateTimeFromValueHourWithoutDayReturnsNull(): void
    {
        // An hour requires a day per ISO 8601.
        $this->assertNull($this->helper->callGetDateTimeFromValue('2020-06T14'));
    }

    // convertDate() tests.

    public function testConvertDateSingleYearJanuary1(): void
    {
        $result = $this->helper->callConvertDate('2020', 'january_1');
        $this->assertEquals('2020-01-01T00:00:00+00:00', $result);
    }

    public function testConvertDateSingleYearJuly1(): void
    {
        $result = $this->helper->callConvertDate('2020', 'july_1');
        $this->assertEquals('2020-07-01T00:00:00+00:00', $result);
    }

    public function testConvertDateSingleYearDecember31(): void
    {
        $result = $this->helper->callConvertDate('2020', 'december_31');
        $this->assertEquals('2020-12-31T00:00:00+00:00', $result);
    }

    public function testConvertDateSingleYearJune30(): void
    {
        $result = $this->helper->callConvertDate('2020', 'june_30');
        $this->assertEquals('2020-06-30T00:00:00+00:00', $result);
    }

    public function testConvertDateSingleYearSkip(): void
    {
        $result = $this->helper->callConvertDate('2020', 'skip');
        $this->assertNull($result);
    }

    public function testConvertDateNegativeYear(): void
    {
        $result = $this->helper->callConvertDate('-500', 'january_1');
        $this->assertStringContainsString('-0500-01-01', $result);
    }

    public function testConvertDateFullDateString(): void
    {
        $result = $this->helper->callConvertDate('2020-06-15');
        $this->assertIsString($result);
        $this->assertStringContainsString('2020', $result);
    }

    public function testConvertDateInvalidReturnsNull(): void
    {
        $result = $this->helper->callConvertDate('not-a-date');
        $this->assertNull($result);
    }

    public function testConvertDatePadsShortYear(): void
    {
        $result = $this->helper->callConvertDate('5', 'january_1');
        $this->assertStringContainsString('0005-01-01', $result);
    }

    // convertAnyDate() tests.

    public function testConvertAnyDateSingleDate(): void
    {
        $result = $this->helper->callConvertAnyDate('2020-06-15');
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertNotNull($result[0]);
    }

    public function testConvertAnyDateRangeWithSlash(): void
    {
        $result = $this->helper->callConvertAnyDate('2020-01-01/2020-12-31');
        $this->assertIsArray($result);
        $this->assertNotNull($result[0]);
        $this->assertNotNull($result[1]);
    }

    public function testConvertAnyDateYearRange(): void
    {
        $result = $this->helper->callConvertAnyDate('2016-2017');
        $this->assertIsArray($result);
        $this->assertNotNull($result[0]);
        $this->assertNotNull($result[1]);
    }

    public function testConvertAnyDateSingleYear(): void
    {
        $result = $this->helper->callConvertAnyDate('2020');
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertNotNull($result[0]);
    }

    // convertSingleDate() tests.

    public function testConvertSingleDateFullYear(): void
    {
        $this->helper->setRenderYear('full_year');
        $result = $this->helper->callConvertSingleDate('2020', 'full_year');
        $this->assertIsArray($result);
        // full_year renders a year as a range.
        $this->assertNotNull($result[0]);
        $this->assertNotNull($result[1]);
        $this->assertStringContainsString('01-01', $result[0]);
        $this->assertStringContainsString('12-31', $result[1]);
    }

    public function testConvertSingleDateNonYearValue(): void
    {
        $result = $this->helper->callConvertSingleDate('2020-06-15');
        $this->assertIsArray($result);
        $this->assertNotNull($result[0]);
        $this->assertNull($result[1]);
    }

    // convertRangeDates() tests.

    public function testConvertRangeDatesTwoYears(): void
    {
        $result = $this->helper->callConvertRangeDates(['2016', '2020']);
        $this->assertIsArray($result);
        $this->assertNotNull($result[0]);
        $this->assertNotNull($result[1]);
    }

    public function testConvertRangeDatesSameYear(): void
    {
        $result = $this->helper->callConvertRangeDates(['2020', '2020']);
        $this->assertIsArray($result);
        $this->assertStringContainsString('01-01', $result[0]);
        $this->assertStringContainsString('12-31', $result[1]);
    }

    public function testConvertRangeDatesReordersYears(): void
    {
        $result = $this->helper->callConvertRangeDates(['2020', '2016']);
        $this->assertIsArray($result);
        // Start should be before end.
        $this->assertStringContainsString('2016', $result[0]);
        $this->assertStringContainsString('2020', $result[1]);
    }

    public function testConvertRangeDatesNonArray(): void
    {
        $result = $this->helper->callConvertRangeDates('not-array');
        $this->assertEquals([null, null], $result);
    }

    // date() tests.

    public function testDateFullIso(): void
    {
        $result = $this->helper->callDate('2020-06-15');
        $this->assertIsArray($result);
        $this->assertEquals(2020, $result['year']);
        $this->assertEquals(6, $result['month']);
        $this->assertEquals(15, $result['day']);
    }

    public function testDateYearOnly(): void
    {
        $result = $this->helper->callDate('2020');
        $this->assertIsArray($result);
        $this->assertEquals(2020, $result['year']);
        $this->assertArrayNotHasKey('month', $result);
    }

    public function testDateYearMonth(): void
    {
        $result = $this->helper->callDate('2020-06');
        $this->assertIsArray($result);
        $this->assertEquals(2020, $result['year']);
        $this->assertEquals(6, $result['month']);
        $this->assertArrayNotHasKey('day', $result);
    }

    public function testDateWithTime(): void
    {
        $result = $this->helper->callDate('2020-06-15T14:30:00');
        $this->assertIsArray($result);
        $this->assertEquals(14, $result['hour']);
        $this->assertEquals(30, $result['minute']);
        $this->assertEquals(0, $result['second']);
    }

    public function testDateWithDisplayDate(): void
    {
        $result = $this->helper->callDate('2020', 'Circa 2020');
        $this->assertIsArray($result);
        $this->assertEquals('Circa 2020', $result['display_date']);
    }

    public function testDateNegativeYear(): void
    {
        $result = $this->helper->callDate('-500');
        $this->assertIsArray($result);
        $this->assertEquals(-500, $result['year']);
    }

    public function testDateReturnsNullForInvalid(): void
    {
        $result = $this->helper->callDate('not-a-date');
        $this->assertNull($result);
    }

    public function testDateWithHour(): void
    {
        $result = $this->helper->callDate('2020-06-15T14');
        $this->assertIsArray($result);
        $this->assertEquals(14, $result['hour']);
    }

    public function testDateWithHourMinute(): void
    {
        $result = $this->helper->callDate('2020-06-15T14:30');
        $this->assertIsArray($result);
        $this->assertEquals(14, $result['hour']);
        $this->assertEquals(30, $result['minute']);
    }

    // dateToArray() tests.

    public function testDateToArrayFullDate(): void
    {
        $result = $this->helper->callDateToArray('2020-06-15');
        $this->assertIsArray($result);
        $this->assertEquals(2020, $result['year']);
        $this->assertEquals(6, $result['month']);
        $this->assertEquals(15, $result['day']);
    }

    public function testDateToArrayYearOnly(): void
    {
        $result = $this->helper->callDateToArray('2020');
        $this->assertIsArray($result);
        $this->assertEquals(2020, $result['year']);
        $this->assertArrayNotHasKey('month', $result);
    }

    public function testDateToArrayNullReturnsEmpty(): void
    {
        $result = $this->helper->callDateToArray(null);
        $this->assertEmpty($result);
    }

    public function testDateToArrayEmptyReturnsEmpty(): void
    {
        $result = $this->helper->callDateToArray('');
        $this->assertEmpty($result);
    }

    public function testDateToArrayInvalidReturnsEmpty(): void
    {
        $result = $this->helper->callDateToArray('not-a-date');
        $this->assertEmpty($result);
    }

    // snippet() tests.

    public function testSnippetShortString(): void
    {
        $result = $this->helper->callSnippet('Hello', 10);
        $this->assertEquals('Hello', $result);
    }

    public function testSnippetLongString(): void
    {
        $result = $this->helper->callSnippet('Hello World Test', 10);
        $this->assertEquals('Hello Wor&hellip;', $result);
    }

    public function testSnippetStripsHtml(): void
    {
        $result = $this->helper->callSnippet('<p>Hello</p>', 100);
        $this->assertEquals('Hello', $result);
    }

    public function testSnippetExactLength(): void
    {
        $result = $this->helper->callSnippet('12345', 5);
        $this->assertEquals('12345', $result);
    }

    // textToId() tests.

    public function testTextToIdBasic(): void
    {
        $result = $this->helper->callTextToId('Foo Bar');
        $this->assertEquals('foo-bar', $result);
    }

    public function testTextToIdWithPrepend(): void
    {
        $result = $this->helper->callTextToId('Foo Bar', 'prefix');
        $this->assertEquals('prefix-foo-bar', $result);
    }

    public function testTextToIdRemovesSpecialChars(): void
    {
        $result = $this->helper->callTextToId('Foo @Bar!');
        $this->assertEquals('foo-bar', $result);
    }

    public function testTextToIdCustomDelimiter(): void
    {
        $result = $this->helper->callTextToId('Foo Bar', null, '_');
        $this->assertEquals('foo_bar', $result);
    }

    // getLastDay() tests.

    public function testGetLastDayFebruaryLeapYear(): void
    {
        $result = $this->helper->callGetLastDay(2020, 2);
        $this->assertEquals(29, $result);
    }

    public function testGetLastDayFebruaryNonLeapYear(): void
    {
        $result = $this->helper->callGetLastDay(2021, 2);
        $this->assertEquals(28, $result);
    }

    public function testGetLastDay30DayMonth(): void
    {
        $result = $this->helper->callGetLastDay(2020, 4);
        $this->assertEquals(30, $result);
    }

    public function testGetLastDay31DayMonth(): void
    {
        $result = $this->helper->callGetLastDay(2020, 1);
        $this->assertEquals(31, $result);
    }
}
