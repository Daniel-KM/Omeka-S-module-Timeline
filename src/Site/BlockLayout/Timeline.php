<?php
namespace Timeline\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Mvc\Controller\Plugin\Api;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Stdlib\ErrorStore;
use Timeline\Form\TimelineBlockForm;
use Zend\Form\FormElementManager\FormElementManagerV3Polyfill as FormElementManager;
use Zend\View\Renderer\PhpRenderer;

class Timeline extends AbstractBlockLayout
{
    /**
     * @var FormElementManager
     */
    protected $formElementManager;

    /**
     * @var array
     */
    protected $defaultSettings = [];

    /**
     * @var Api
     */
    protected $api;

    /**
     * @param FormElementManager $formElementManager
     * @param array $defaultSettings
     * @param Api $api
     */
    public function __construct(
        FormElementManager $formElementManager,
        array $defaultSettings,
        Api $api
    ) {
        $this->formElementManager = $formElementManager;
        $this->defaultSettings = $defaultSettings;
        $this->api = $api;
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

        $addedBlock = empty($block);
        if ($addedBlock) {
            $data = $this->defaultSettings;
            $data['query'] = 'site_id=' . $site->id();
            $itemCount = null;
        } else {
            $data = $block->data() + $this->defaultSettings;
            $itemCount = $this->itemCount($data);
            if (is_array($data['query'])) {
                $data['query'] = urldecode(
                    http_build_query($data['query'], "\n", '&', PHP_QUERY_RFC3986)
                );
            }
        }

        $dataToSet = [];
        foreach ($data as $key => $value) {
            $dataToSet['o:block[__blockIndex__][o:data][' . $key . ']'] = $value;
        }
        $form->setData($dataToSet);

        return $view->partial(
            'common/block-layout/timeline-form',
            [
                'form' => $form,
                'data' => $dataToSet,
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

            case 'simile_online':
                $view->headLink()->appendStylesheet($view->assetUrl('css/timeline.css', 'Timeline'));
                $view->headScript()->appendFile($view->assetUrl('js/timeline.js', 'Timeline'));
                $view->headScript()->appendFile('//api.simile-widgets.org/timeline/2.3.1/timeline-api.js?bundle=true');
                $view->headScript()->appendScript('SimileAjax.History.enabled = false; window.jQuery = SimileAjax.jQuery;');
                break;

            case 'simile':
            default:
                $view->headLink()->appendStylesheet($view->assetUrl('css/timeline.css', 'Timeline'));
                $view->headScript()->appendFile($view->assetUrl('js/timeline.js', 'Timeline'));
                $timelineVariables = 'Timeline_ajax_url="' . $view->assetUrl('vendor/simile/ajax-api/simile-ajax-api.js', 'Timeline') . '";' . PHP_EOL;
                $timelineVariables .= 'Timeline_urlPrefix="' . dirname($view->assetUrl('vendor/simile/timeline-api/timeline-api.js', 'Timeline')) . '/";' . PHP_EOL;
                $timelineVariables .= 'Timeline_parameters="bundle=true";';
                $view->headScript()->appendScript($timelineVariables);
                $view->headScript()->appendFile($view->assetUrl('vendor/simile/timeline-api/timeline-api.js', 'Timeline'));
                $view->headScript()->appendScript('SimileAjax.History.enabled = false; // window.jQuery = SimileAjax.jQuery;');
                break;
        }
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $data = $block->data();

        $library = $data['library'];
        if ($library === 'simile_online') {
            $library = 'simile';
        }
        unset($data['library']);

        return $view->partial(
            'common/block-layout/timeline_' . $library,
            [
                'blockId' => $block->id(),
                'data' => $data,
            ]
        );
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {
        $data = $block->getData();

        $data['viewer'] = trim($data['viewer']);
        if ($data['viewer'] === '') {
            $data['viewer'] = '{}';
        }

        $property = $this->api
            ->searchOne('properties', ['term' => $data['item_date']])
            ->getContent();
        $data['item_date_id'] = (string) $property->id();

        parse_str($data['query'], $query);
        $data['query'] = $query;

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
        $params = $data['query'];
        // Add the param for the date: return only if not empty.
        $params['property'][] = ['joiner' => 'and', 'property' => $data['item_date_id'], 'type' => 'ex'];
        $itemCount = $this->api->search('items', $params)->getTotalResults();
        return $itemCount;
    }
}
