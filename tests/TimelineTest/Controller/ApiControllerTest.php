<?php declare(strict_types=1);

namespace TimelineTest\Controller;

use CommonTest\AbstractHttpControllerTestCase;
use TimelineTest\TimelineTestTrait;

/**
 * Tests for the Timeline API controller.
 */
class ApiControllerTest extends AbstractHttpControllerTestCase
{
    use TimelineTestTrait;

    public function setUp(): void
    {
        parent::setUp();
        $this->loginAdmin();
    }

    public function tearDown(): void
    {
        $this->cleanupResources();
        parent::tearDown();
    }

    /**
     * Test that the timeline API route matches the controller.
     */
    public function testTimelineApiRouteMatchesController(): void
    {
        // A query parameter is needed to avoid the exception.
        $this->dispatch('/api/timeline', 'GET', ['output' => 'simile']);
        $this->assertControllerName('Timeline\Controller\ApiController');
    }

    /**
     * Test that the API returns JSON for a valid request.
     */
    public function testApiReturnsJsonResponse(): void
    {
        $item = $this->createItem([
            'dcterms:title' => [
                ['@value' => 'Timeline Test Item'],
            ],
            'dcterms:date' => [
                ['@value' => '2020-01-15'],
            ],
        ]);

        $this->dispatch('/api/timeline', 'GET', ['output' => 'simile']);
        $this->assertResponseStatusCode(200);

        $response = $this->getResponse();
        $contentType = $response->getHeaders()->get('Content-Type');
        $this->assertStringContainsString('json', $contentType->getFieldValue());
    }

    /**
     * Test that the API returns Knightlab format data.
     */
    public function testApiReturnsKnightlabFormat(): void
    {
        $item = $this->createItem([
            'dcterms:title' => [
                ['@value' => 'Knightlab Test Item'],
            ],
            'dcterms:date' => [
                ['@value' => '2021-06-15'],
            ],
        ]);

        $this->dispatch('/api/timeline', 'GET', ['output' => 'knightlab']);
        $this->assertResponseStatusCode(200);

        $body = $this->getResponse()->getBody();
        $data = json_decode($body, true);
        $this->assertIsArray($data);
    }

    /**
     * Test that the API returns Simile format data.
     */
    public function testApiReturnsSimileFormat(): void
    {
        $item = $this->createItem([
            'dcterms:title' => [
                ['@value' => 'Simile Test Item'],
            ],
            'dcterms:date' => [
                ['@value' => '2019-03-20'],
            ],
        ]);

        $this->dispatch('/api/timeline', 'GET', ['output' => 'simile']);
        $this->assertResponseStatusCode(200);

        $body = $this->getResponse()->getBody();
        $data = json_decode($body, true);
        $this->assertIsArray($data);
    }

    /**
     * Test that the block-id route segment accepts valid formats.
     */
    public function testBlockIdRouteAcceptsBlockPrefix(): void
    {
        // b prefix for block id — non-existent block returns error but route matches.
        $this->dispatch('/api/timeline/b1');
        $this->assertControllerName('Timeline\Controller\ApiController');
    }

    /**
     * Test that the block-id route segment accepts resource prefix.
     */
    public function testBlockIdRouteAcceptsResourcePrefix(): void
    {
        // r prefix for resource id — non-existent resource returns error but route matches.
        $this->dispatch('/api/timeline/r1');
        $this->assertControllerName('Timeline\Controller\ApiController');
    }

    /**
     * Test that the block-id route segment accepts numeric id.
     */
    public function testBlockIdRouteAcceptsNumericId(): void
    {
        $this->dispatch('/api/timeline/1');
        $this->assertControllerName('Timeline\Controller\ApiController');
    }
}
