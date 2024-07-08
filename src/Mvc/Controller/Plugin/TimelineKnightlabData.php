<?php declare(strict_types=1);

namespace Timeline\Mvc\Controller\Plugin;

/**
 * Create a list of events for Knightlab timeline.
 *
 * @link https://timeline.knightlab.com
 */
class TimelineKnightlabData extends AbstractTimelineData
{
    protected $timelineJs = 'knightlab';
}
