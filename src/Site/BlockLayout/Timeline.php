<?php declare(strict_types=1);

namespace Timeline\Site\BlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Mvc\Controller\Plugin\Api;
use Omeka\Mvc\Controller\Plugin\Messenger;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Site\BlockLayout\TemplateableBlockLayoutInterface;
use Omeka\Stdlib\ErrorStore;

class Timeline extends AbstractBlockLayout implements TemplateableBlockLayoutInterface
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/block-layout/timeline';

    /**
     * @var \Omeka\Mvc\Controller\Plugin\Api
     */
    protected $api;

    /**
     * @var \Omeka\Mvc\Controller\Plugin\Messenger
     */
    protected $messenger;

    public function __construct(Api $api, Messenger $messenger)
    {
        $this->api = $api;
        $this->messenger = $messenger;
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

        // In some cases, the ArrayTextarray store values as string.
        $eras = $data['eras'] ?? [];
        if (empty($eras)) {
            $data['eras'] = [];
        } elseif (is_string($eras)) {
            $arrayTextarea = new \Omeka\Form\Element\ArrayTextarea();
            $arrayTextarea->setAsKeyValue(true);
            $data['eras'] = $arrayTextarea->stringToArray($eras);
        }

        // In some cases, the ArrayTextarray store values as string.
        $markers = $data['markers'] ?? [];
        if (empty($markers)) {
            $data['markers'] = [];
        } elseif (is_string($markers)) {
            $dataTextarea = new \Common\Form\Element\DataTextarea();
            $dataTextarea->setDataOptions([
                'heading' => null,
                'dates' => null,
                'body' => null,
            ]);
            $data['markers'] = $dataTextarea->stringToArray($markers);
        }

        $data['viewer'] = trim($data['viewer'] ?? '{}');
        if ($data['viewer'] === '') {
            $data['viewer'] = '{}';
        }

        $viewer = json_decode($data['viewer'], true);
        if (strlen(preg_replace('~\s*~', '', $data['viewer'])) <= 2) {
            $data['viewer'] = '{}';
        } elseif (!$viewer || !is_array($viewer)) {
            $this->messenger->addWarning('The config of the Timeline viewer is not a valid json object. Nevertheless, the data are saved and it will be passed as it.'); // @translate
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

        // This property is automatically appended via the controller too.
        $query['property'][] = [
            'joiner' => 'and',
            'property' => empty($data['item_date_id']) ? 'dcterms:date' : $data['item_date_id'],
            'type' => 'ex',
        ];
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

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block, $templateViewScript = self::PARTIAL_NAME)
    {
        $vars = ['block' => $block, 'data' => $block->data()];
        return $view->partial($templateViewScript, $vars);
    }

    /**
     * Helper to get the item count for the item pool, filtered of empty dates.
     */
    protected function itemCount(array $query): int
    {
        // Don't load entities if the only information needed is total results.
        if (empty($query['limit'])) {
            $query['limit'] = 0;
        }
        return (int) $this->api->search('items', $query)->getTotalResults();
    }
}
