<?php declare(strict_types=1);

namespace Timeline\Site\BlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Mvc\Controller\Plugin\Api;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Stdlib\ErrorStore;

class Timeline extends AbstractBlockLayout
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/block-layout/timeline_simile';

    /**
     * @var \Omeka\Mvc\Controller\Plugin\Api
     */
    protected $api;

    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    public function getLabel()
    {
        return 'Timeline'; // @translate
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore): void
    {
        $data = $block->getData() ?? [];

        if (empty($data['query'])) {
            $data['query'] = [];
        } elseif (!is_array($data['query'])) {
            $query = [];
            parse_str(ltrim($data['query'], "? \t\n\r\0\x0B"), $query);
            $data['query'] = $query;
        }

        $data['viewer'] = trim($data['viewer'] ?? '{}');
        if ($data['viewer'] === '') {
            $data['viewer'] = '{}';
        }

        $property = $this->api
            ->searchOne('properties', ['term' => $data['item_date'] ?? 'dcterms:date'])
            ->getContent();
        $data['item_date_id'] = (string) $property->id();

        $block->setData($data);
    }

    public function form(
        PhpRenderer $view,
        SiteRepresentation $site,
        SitePageRepresentation $page = null,
        SitePageBlockRepresentation $block = null
    ) {
        // Factory is not used to make rendering simpler.
        $services = $site->getServiceLocator();
        $formElementManager = $services->get('FormElementManager');
        $defaultSettings = $services->get('Config')['timeline']['block_settings']['timeline'];
        $blockFieldset = \Timeline\Form\TimelineFieldset::class;

        if ($block) {
            $data = ($block->data() ?? []) + $defaultSettings;
            if (empty($data['query'])) {
                $data['query'] = 'site_id=' . $site->id();
            } elseif (is_array($data['query'])) {
                $data['query'] = urldecode(
                    http_build_query($data['query'], '', '&', PHP_QUERY_RFC3986)
                );
            }
        } else {
            $data = $defaultSettings;
            $data['query'] = 'site_id=' . $site->id();
        }

        $query = null;
        parse_str($data['query'], $query);
        $query['property'][] = ['joiner' => 'and', 'property' => empty($data['item_date_id']) ? 'dcterms:date' : $data['item_date_id'], 'type' => 'ex'];
        $itemCount = $this->itemCount($query);
        if (!empty($query['limit'])) {
            $itemCount = min($itemCount, $query['limit']);
        }

        $dataForm = [];
        foreach ($data as $key => $value) {
            $dataForm['o:block[__blockIndex__][o:data][' . $key . ']'] = $value;
        }

        $fieldset = $formElementManager->get($blockFieldset);
        $fieldset->get('o:block[__blockIndex__][o:data][query]')
            ->setOption('query_resource_type', $data['resource_type'] ?? 'items');
        $fieldset->populateValues($dataForm);

        return $view->partial(
            'common/block-layout/admin/timeline-form',
            [
                'fieldset' => $fieldset,
                'data' => $dataForm,
                'query' => $query,
                'itemCount' => $itemCount,
            ]
        );
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $data = $block->data();

        $library = $data['library'];
        unset($data['library']);

        switch ($library) {
            case 'knightlab':
                $view->headLink()
                    ->appendStylesheet('https://cdn.knightlab.com/libs/timeline3/latest/css/timeline.css');
                $view->headScript()
                    ->appendFile('https://cdn.knightlab.com/libs/timeline3/latest/js/timeline.js', 'text/javascript', ['defer' => 'defer']);
                break;

            case 'simile_online':
                $assetUrl = $view->plugin('assetUrl');
                $view->headLink()
                    ->appendStylesheet($assetUrl('css/timeline.css', 'Timeline'));
                $view->headScript()
                    ->appendFile($assetUrl('js/timeline.js', 'Timeline'))
                    ->appendFile('https://simile-widgets.org/timeline/api/timeline-api.js?bundle=true')
                    ->appendScript('SimileAjax.History.enabled = false; window.jQuery = SimileAjax.jQuery;');
                $library = 'simile';
                break;

            case 'simile':
            default:
                $assetUrl = $view->plugin('assetUrl');
                $timelineVariables = 'Timeline_ajax_url="' . $assetUrl('vendor/simile/ajax-api/simile-ajax-api.js', 'Timeline') . '";' . PHP_EOL;
                $timelineVariables .= 'Timeline_urlPrefix="' . dirname($assetUrl('vendor/simile/timeline-api/timeline-api.js', 'Timeline')) . '/";' . PHP_EOL;
                $timelineVariables .= 'Timeline_parameters="bundle=true";';
                $view->headLink()
                    ->appendStylesheet($assetUrl('css/timeline.css', 'Timeline'));
                $view->headScript()
                    ->appendFile($assetUrl('js/timeline.js', 'Timeline'))
                    ->appendScript($timelineVariables)
                    ->appendFile($assetUrl('vendor/simile/timeline-api/timeline-api.js', 'Timeline'))
                    ->appendScript('SimileAjax.History.enabled = false; // window.jQuery = SimileAjax.jQuery;');
                break;
        }

        return $view->partial(
            'common/block-layout/timeline_' . $library,
            [
                'block' => $block,
                'data' => $data,
            ]
        );
    }

    /**
     * Helper to get the item count for the item pool, filtered of empty dates.
     */
    protected function itemCount(array $data): int
    {
        $params = $data['query'] ?? [];

        // Don't load entities if the only information needed is total results.
        if (empty($params['limit'])) {
            $params['limit'] = 0;
        }

        // Add the param for the date: return only if not empty.
        $params['property'][] = ['joiner' => 'and', 'property' => empty($data['item_date_id']) ? 'dcterms:date' : $data['item_date_id'], 'type' => 'ex'];
        return (int) $this->api->search('items', $params)->getTotalResults();
    }
}
