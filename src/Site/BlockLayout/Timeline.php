<?php
namespace Timeline\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Stdlib\ErrorStore;
use Omeka\View\Helper\Api;
use Timeline\Form\TimelineBlockForm;
use Zend\Form\FormElementManager\FormElementManagerV3Polyfill as FormElementManager;
use Zend\View\Renderer\PhpRenderer;

class Timeline extends AbstractBlockLayout
{
    /**
     * @var Api
     */
    protected $api;

    /**
     * @var FormElementManager
     */
    protected $formElementManager;

    /**
     * @var bool
     */
    protected $useExternal;

    /**
     * @param Api $api
     * @param FormElementManager $formElementManager
     * @param bool $useExternal
     */
    public function __construct(Api $api, FormElementManager $formElementManager, $useExternal)
    {
        $this->api = $api;
        $this->formElementManager = $formElementManager;
        $this->useExternal = $useExternal;
    }

    public function getLabel()
    {
        return 'Timeline'; // @translate
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null
    ) {
        /** @var \Timeline\Form\TimelineBlockForm $form */
        $form = $this->formElementManager->get(TimelineBlockForm::class);
        $form->init();

        $addedBlock = empty($block);
        if ($addedBlock) {
            $data['args'] = $view->setting('timeline_defaults');
            $data['args']['query'] = ['site_id' => $site->id()];
            $itemCount = null;
        } else {
            $data = $block->data();
            $itemCount = $this->itemCount($data);
        }

        $data['args']['query'] = is_array($data['args']['query'])
            ? urldecode(http_build_query($data['args']['query'], "\n", '&', PHP_QUERY_RFC3986))
            : $data['args']['query'];

        $form->setData([
            'o:block[__blockIndex__][o:data][args]' => $data['args'],
        ]);

        return $view->partial(
            'common/block-layout/timeline-form',
            [
                'form' => $form,
                'data' => $data,
                'itemCount' => $itemCount,
            ]
        );
    }

    public function prepareRender(PhpRenderer $view)
    {
        $library = $view->setting('timeline_library');
        switch ($library) {
            case 'knightlab':
                $view->headLink()->appendStylesheet('//cdn.knightlab.com/libs/timeline3/latest/css/timeline.css');
                $view->headScript()->appendFile('//cdn.knightlab.com/libs/timeline3/latest/js/timeline.js');
                break;

            case 'simile':
            default:
                $internalAssets = $view->setting('timeline_internal_assets');
                $view->headLink()->appendStylesheet($view->assetUrl('css/timeline.css', 'Timeline'));
                $view->headScript()->appendFile($view->assetUrl('js/timeline.js', 'Timeline'));
                if ($this->useExternal && !$internalAssets) {
                    $view->headScript()->appendFile('//api.simile-widgets.org/timeline/2.3.1/timeline-api.js?bundle=true');
                    $view->headScript()->appendScript('SimileAjax.History.enabled = false; window.jQuery = SimileAjax.jQuery;');
                } else {
                    $timelineVariables = 'Timeline_ajax_url="' . $view->assetUrl('vendor/simile/ajax-api/simile-ajax-api.js', 'Timeline') . '";' . PHP_EOL;
                    $timelineVariables .= 'Timeline_urlPrefix="' . dirname($view->assetUrl('vendor/simile/timeline-api/timeline-api.js', 'Timeline')) . '/";' . PHP_EOL;
                    $timelineVariables .= 'Timeline_parameters="bundle=true";';
                    $view->headScript()->appendScript($timelineVariables);
                    $view->headScript()->appendFile($view->assetUrl('vendor/simile/timeline-api/timeline-api.js', 'Timeline'));
                    $view->headScript()->appendScript('SimileAjax.History.enabled = false; // window.jQuery = SimileAjax.jQuery;');
                }
                break;
        }
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $library = $view->setting('timeline_library');
        return $view->partial(
            'common/block-layout/timeline_' . $library,
            [
                'blockId' => $block->id(),
                'data' => $block->data(),
            ]
        );
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {
        $data = $block->getData();

        // Set some default values in case of error.
        $data += [
            'args' => [
                'item_date' => 'dcterms:date',
                'viewer' => '{}',
                'query' => [],
            ],
        ];

        $data['args']['viewer'] = trim($data['args']['viewer']);
        if ($data['args']['viewer'] === '') {
            $data['args']['viewer'] = '{}';
        }

        $property = $this->api
            ->searchOne('properties', ['term' => $data['args']['item_date']])
            ->getContent();
        $data['args']['item_date_id'] = (string) $property->id();

        parse_str($data['args']['query'], $query);
        $data['args']['query'] = $query;

        $block->setData($data);
    }

    /**
     * Helper to get the item count for the item pool, filtered of empty dates.
     *
     * @param array $data
     * @return int
     */
    protected function itemCount($data)
    {
        $params = $data['args']['query'];
        // Add the param for the date: return only if not empty.
        $params['property'][] = ['joiner' => 'and', 'property' => $data['args']['item_date_id'], 'type' => 'ex'];
        $itemCount = $this->api->search('items', $params)->getTotalResults();
        return $itemCount;
    }
}
