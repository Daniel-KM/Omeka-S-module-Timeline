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
     * @var Api
     */
    protected $api;

    /**
     * @param Api $api
     */
    public function __construct(
        Api $api
    ) {
        $this->api = $api;
    }

    public function getLabel()
    {
        return 'Timeline'; // @translate
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore): void
    {
        $data = $block->getData();

        $data['viewer'] = trim($data['viewer'] ?? '{}');
        if ($data['viewer'] === '') {
            $data['viewer'] = '{}';
        }

        $property = $this->api
            ->searchOne('properties', ['term' => $data['item_date'] ?? 'dcterms:date'])
            ->getContent();
        $data['item_date_id'] = (string) $property->id();

        $query = $data['query'] ?? [];
        if (is_string($query)) {
            $query = [];
            parse_str($data['query'], $query);
        }
        $data['query'] = $query;

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
            $data = $block->data() + $defaultSettings;
            $itemCount = $this->itemCount($data);
            if (is_array($data['query'])) {
                $data['query'] = urldecode(
                    http_build_query($data['query'], '', '&', PHP_QUERY_RFC3986)
                );
            }
        } else {
            $data = $defaultSettings;
            $data['query'] = 'site_id=' . $site->id();
            $itemCount = null;
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
            'common/block-layout/timeline-form',
            [
                'fieldset' => $fieldset,
                'data' => $dataForm,
                'itemCount' => $itemCount,
            ]
        );
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $data = $block->data();

        $library = $data['library'];
        // Simile online seems to be broken, so use local one currently.
        if ($library === 'simile_online') {
            $library = 'simile';
        }
        unset($data['library']);

        switch ($library) {
            case 'knightlab':
                $view->headLink()
                    ->appendStylesheet('//cdn.knightlab.com/libs/timeline3/latest/css/timeline.css');
                $view->headScript()
                    ->appendFile('//cdn.knightlab.com/libs/timeline3/latest/js/timeline.js', 'text/javascript', ['defer' => 'defer']);
                break;

            case 'simile_online':
                $assetUrl = $view->plugin('assetUrl');
                $view->headLink()
                    ->appendStylesheet($assetUrl('css/timeline.css', 'Timeline'));
                $view->headScript()
                    ->appendFile($assetUrl('js/timeline.js', 'Timeline'))
                    ->appendFile('//api.simile-widgets.org/timeline/2.3.1/timeline-api.js?bundle=true')
                    ->appendScript('SimileAjax.History.enabled = false; window.jQuery = SimileAjax.jQuery;');
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
     *
     * @param array $data
     * @return int
     */
    protected function itemCount($data)
    {
        $params = $data['query'];

        // Fixes some bad upgrades to 3.4.13.3.
        if (is_string($params)) {
            parse_str($data['query'], $params);
        }

        // Don't load entities if the only information needed is total results.
        if (empty($params['limit'])) {
            $params['limit'] = 0;
        }
        // Add the param for the date: return only if not empty.
        $params['property'][] = ['joiner' => 'and', 'property' => $data['item_date_id'], 'type' => 'ex'];
        return $this->api->search('items', $params)->getTotalResults();
    }
}
