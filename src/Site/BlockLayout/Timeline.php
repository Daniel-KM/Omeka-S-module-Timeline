<?php
namespace Timeline\Site\BlockLayout;

use Omeka\Api\Manager as ApiManager;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Stdlib\ErrorStore;
use Zend\View\Renderer\PhpRenderer;
use Zend\Form\Form;
use Timeline\Form\TimelineBlock;

class Timeline extends AbstractBlockLayout
{
    /**
     * @var ApiManager
     */
    protected $apiManager;

    public function __construct(ApiManager $apiManager)
    {
        $this->apiManager = $apiManager;
    }

    public function getLabel()
    {
        return 'Timeline'; // @translate
    }

    public function prepareForm(PhpRenderer $view)
    {
        $view->headLink()->prependStylesheet($view->assetUrl('css/advanced-search.css', 'Omeka'));
        $view->headScript()->appendFile($view->assetUrl('js/timeline-item-pool.js', 'Timeline'));
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null
    ) {
        $data = $block ? $block->data() : [];

        $services = $site->getServiceLocator();
        $api = $services->get('Omeka\ApiManager');

        $form = new TimelineBlock();
        $form->setApiManager($api);
        $form->init();

        $addedBlock = empty($data);
        if ($addedBlock) {
            $data['args'] = $view->setting('timeline_defaults');
            $data['item_pool'] = $site->itemPool();
            $itemCount = null;
        } else {
            $itemCount = $this->itemCount($api, $data);
        }

        $form->setData([
            'o:block[__blockIndex__][o:data][args]' => $data['args'],
            'o:block[__blockIndex__][o:data][item_pool]' => $data['item_pool'],
        ]);

        return $view->partial(
            'common/block-layout/timeline-form',
            [
                'form' => $form,
                'data' => $data,
                'itemCount' => $itemCount,
            ]
        );

        return $view->blockTimelineForm($block);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $config = $block->getServiceLocator()->get('Config');
        $external = $config['assets']['use_externals'];

        // TODO Merge the library header.

        return $view->partial('common/block-layout/timeline', [
            'blockId' => $block->id(),
            'data' => $block->data(),
            'external' => $external,
        ]);
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {
        $data = $block->getData();

        $data['item_pool'] = json_decode($data['item_pool'], true) ?: [];

        $data['args']['viewer'] = trim($data['args']['viewer']);
        if ($data['args']['viewer'] === '') {
            $data['args']['viewer'] = '{}';
        }

        $vocabulary = strtok($data['args']['item_date'], ':');
        $name = strtok(':');
        $property = $this->apiManager
            ->search('properties', ['vocabulary_prefix' => $vocabulary, 'local_name' => $name])
            ->getContent();
        $data['args']['item_date_id'] = (string) $property[0]->id();

        $block->setData($data);
    }

    /**
     * Helper to get the item count for the item pool, filtered of empty dates.
     *
     * @param ApiManager $api
     * @param array $data
     * @return int
     */
    protected function itemCount(ApiManager $api, $data)
    {
        $params = $data['item_pool'];
        // Add the param for the date: return only if not empty.
        $params['has_property'][$data['args']['item_date_id']] = 1;
        $params['limit'] = 0;
        $itemCount = $api->search('items', $params)->getTotalResults();
        return $itemCount;
    }
}
