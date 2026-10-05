<?php declare(strict_types=1);

namespace TimelineTest\Controller;

use CommonTest\AbstractHttpControllerTestCase;
use TimelineTest\TimelineTestTrait;

/**
 * Tests for the events built by the timeline api.
 */
class TimelineDataTest extends AbstractHttpControllerTestCase
{
    use TimelineTestTrait;

    const NBSP = "\u{00A0}";

    /**
     * @var mixed
     */
    protected $humanizerSetting;

    public function setUp(): void
    {
        parent::setUp();
        $this->loginAdmin();
        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        $this->humanizerSetting = $settings->get('datatypeedtf_humanizer');
        $settings->set('datatypeedtf_humanizer', 'fr_usage');
    }

    public function tearDown(): void
    {
        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        $this->humanizerSetting === null
            ? $settings->delete('datatypeedtf_humanizer')
            : $settings->set('datatypeedtf_humanizer', $this->humanizerSetting);
        $this->cleanupResources();
        parent::tearDown();
    }

    public function testDateWithoutTimeHasNoTime(): void
    {
        $item = $this->createItem([
            'dcterms:date' => [['@value' => '2006-07-01']],
        ]);

        $events = $this->fetchEvents(['id' => $item->id()]);

        $this->assertCount(1, $events);
        $this->assertSame(['year' => 2006, 'month' => 7, 'day' => 1], $events[0]['start_date']);
    }

    public function testValueAnnotationUsesFirstAnnotatedValue(): void
    {
        $item = $this->createItem([
            'dcterms:creator' => [
                ['@value' => 'Without date'],
                ['@value' => 'First', '@annotation' => ['dcterms:date' => [['@value' => '1950-05-04']]]],
                ['@value' => 'Second', '@annotation' => ['dcterms:date' => [['@value' => '1960-06-07']]]],
            ],
        ]);

        $events = $this->fetchEvents([
            'id' => $item->id(),
            'item_date' => 'dcterms:creator',
            'item_date_va' => 'dcterms:date',
        ]);

        $this->assertCount(1, $events);
        $this->assertSame(1950, $events[0]['start_date']['year']);
    }

    public function testInvalidQueryOverrideIsIgnored(): void
    {
        $item = $this->createItem([
            'dcterms:date' => [['@value' => '2001-05-06']],
        ]);

        $events = $this->fetchEvents([
            'id' => $item->id(),
            'item_date' => 'unknown:property',
        ]);

        $this->assertCount(1, $events);
        $this->assertSame(2001, $events[0]['start_date']['year']);
    }

    public function testNonLiteralDateIsSkipped(): void
    {
        $item = $this->createItem([
            'dcterms:date' => [['type' => 'uri', '@id' => 'https://example.org/date']],
        ]);

        $events = $this->fetchEvents(['id' => $item->id()]);

        $this->assertSame([], $events);
    }

    public function testEdtfPartialDateIsAPoint(): void
    {
        $this->requireEdtf();
        $item = $this->createItem([
            'dcterms:date' => [['type' => 'edtf', '@value' => '1980-03']],
        ]);

        $events = $this->fetchEvents(['id' => $item->id()]);

        $this->assertCount(1, $events);
        $this->assertArrayNotHasKey('end_date', $events[0]);
        $this->assertSame(1980, $events[0]['start_date']['year']);
        $this->assertSame(3, $events[0]['start_date']['month']);
        $this->assertSame('mars 1980', $this->normalize($events[0]['start_date']['display_date']));
    }

    public function testEdtfIntervalDisplayDateIsSetOnEvent(): void
    {
        $this->requireEdtf();
        $item = $this->createItem([
            'dcterms:date' => [['type' => 'edtf', '@value' => '1865/1870']],
        ]);

        $events = $this->fetchEvents(['id' => $item->id()]);

        $this->assertCount(1, $events);
        $this->assertSame('1865 – 1870', $this->normalize($events[0]['display_date']));
        $this->assertArrayNotHasKey('display_date', $events[0]['start_date']);
        $this->assertSame(['year' => 1870], $events[0]['end_date']);
    }

    public function testEdtfCenturyIsAnInterval(): void
    {
        $this->requireEdtf();
        $item = $this->createItem([
            'dcterms:date' => [['type' => 'edtf', '@value' => '19XX']],
        ]);

        $events = $this->fetchEvents(['id' => $item->id()]);

        $this->assertCount(1, $events);
        $this->assertSame(1900, $events[0]['start_date']['year']);
        $this->assertSame(1999, $events[0]['end_date']['year']);
        $this->assertSame('20ᵉ siècle', $this->normalize($events[0]['display_date']));
    }

    public function testEdtfCaptionForSimile(): void
    {
        $this->requireEdtf();
        $item = $this->createItem([
            'dcterms:date' => [['type' => 'edtf', '@value' => '1950/1999']],
        ]);

        $events = $this->fetchEvents(['id' => $item->id()], 'simile');

        $this->assertCount(1, $events);
        $this->assertSame('2ᵈᵉ moitié du 20ᵉ siècle', $this->normalize($events[0]['caption']));
    }

    protected function fetchEvents(array $query, string $output = 'knightlab'): array
    {
        $this->dispatch('/api/timeline', 'GET', $query + ['output' => $output]);
        $this->assertResponseStatusCode(200);
        $data = json_decode($this->getResponse()->getBody(), true);
        return $data['events'] ?? [];
    }

    protected function requireEdtf(): void
    {
        $dataTypes = $this->getServiceLocator()->get('Omeka\DataTypeManager');
        if (!$dataTypes->has('edtf')) {
            $this->markTestSkipped('Requires module DataTypeEdtf.');
        }
    }

    protected function normalize(string $string): string
    {
        return strtr($string, [self::NBSP => ' ']);
    }
}
