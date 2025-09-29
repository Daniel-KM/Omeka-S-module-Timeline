<?php declare(strict_types=1);

namespace Timeline\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Api\Representation\ItemSetRepresentation;
use Omeka\Site\Theme\Theme;

class Timeline extends AbstractHelper
{
    /**
     * @var \Omeka\Site\Theme\Theme
     *
     * The current theme, if any.
     */
    protected $currentTheme;

    /**
     * @var array
     */
    protected $configDefault = [];

    /**
     * @var bool
     */
    protected $isSite;

    /**
     * @var AbstractResourceEntityRepresentation|AbstractResourceEntityRepresentation[]
     */
    protected $resource;

    /**
     * Construct the helper.
     *
     * @param Theme|null $currentTheme
     */
    public function __construct(Theme $currentTheme = null, array $configDefault = [])
    {
        $this->currentTheme = $currentTheme;
        $this->configDefault = $configDefault;
    }

    /**
     * Get the Timeline Viewer for the provided resource.
     *
     * Proxies to {@link render()}.
     *
     * @param AbstractResourceEntityRepresentation|AbstractResourceEntityRepresentation[] $resource
     * @param array $options Supported options:
     * - library (string): type of timeline, simile or knightlab (default: simile).
     * - template (string): the partial view script to use, depending on type.
     * @return string Html string corresponding to the viewer.
     */
    public function __invoke($resource, array $options = []): string
    {
        if (empty($resource)) {
            return '';
        }

        $this->resource = $resource;

        $view = $this->getView();
        $this->isSite = $view->status()->isSiteRequest();

        $library = strtolower($options['library'] ?? '') === 'knightlab' ? 'knightlab':  'simile';
        $template = $options['template']
            ?? ($library === 'knightlab'
                ? 'common/resource-page-block-layout/timeline-knightlab'
                : 'common/resource-page-block-layout/timeline');

        $data = array_replace($this->configDefault, $options);

        if ($resource instanceof ItemSetRepresentation) {
            return $view->partial($template, [
                'resource' => $resource,
                'data' => $data,
            ]);
        }

        // TODO Array of resource.

        return 'Timeline helper supports only item sets for now.';
    }
}
