<?php
namespace Timeline\Site\BlockLayout;

use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Stdlib\ErrorStore;
use Omeka\Stdlib\HtmlPurifier;
use Zend\View\Renderer\PhpRenderer;

class TimelineExhibit extends AbstractBlockLayout
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/block-layout/timeline-exhibit';

    /**
     * @var HtmlPurifier
     */
    protected $htmlPurifier;

    /**
     * @param HtmlPurifier $htmlPurifier
     */
    public function __construct(
        HtmlPurifier $htmlPurifier
    ) {
        $this->htmlPurifier = $htmlPurifier;
    }

    public function getLabel()
    {
        return 'Timeline Exhibit'; // @translate
    }

    public function prepareForm(PhpRenderer $view)
    {
        $assetUrl = $view->plugin('assetUrl');
        $view->headLink()
            ->appendStylesheet($assetUrl('css/asset-form.css', 'Omeka'))
            ->appendStylesheet($assetUrl('css/timeline-form.css', 'Timeline'));
        $view->headScript()
            ->appendFile($assetUrl('js/asset-form.js', 'Omeka'))
            ->appendFile($assetUrl('js/timeline-exhibit-form.js', 'Timeline'));
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {
        $data = $block->getData();

        if (!isset($data['slides'])) {
            $data['slides'] = [];
        }

        $data['scale'] = $data['scale'] === 'cosmological' ? 'cosmological' : 'human';

        // Clean all values.
        $data['slides'] = array_values(
            array_map(function ($v) {
                return array_map(function($w) {
                    $w = trim($w);
                    return strlen($w) ? $w : null;
                }, $v);
            }, $data['slides'])
            );

        // Normalize values and purify html.
        $data['slides'] = array_map(function ($v) {
            // Simplify checks.
            $v += [
                'type' => '',
                'start_date' => '',
                'end_date' => '',
                'start_display_date' => '',
                'end_display_date' => '',
                'display_date' => '',
                'headline' => '',
                'html' => '',
                'resource' => null,
                'content' => '',
                'caption' => '',
                'credit' => '',
                'background' => null,
                'background_color' => '',
                'group' => '',
            ];
            if ($v['html']) {
                $v['html'] = $this->fixEndOfLine($this->htmlPurifier->purify($v['html']));
            }
            if ($v['content']) {
                $v['content'] = $this->fixEndOfLine($this->htmlPurifier->purify($v['content']));
            }
            if ($v['resource'] == strip_tags($v['content'])) {
                $v['content'] = '';
            }
            if ($v['caption']) {
                $v['caption'] = $this->fixEndOfLine($this->htmlPurifier->purify($v['caption']));
            }
            if ($v['credit']) {
                $v['credit'] = $this->fixEndOfLine($this->htmlPurifier->purify($v['credit']));
            }
            return $v;
        }, $data['slides']);

        // Remove empty slides.
        $data['slides'] = array_filter($data['slides'], function($v) {
            unset($v['type']);
            return (bool) array_filter($v);
        });

        // TODO Reorder by start date automatically, according to property, date, item date, etc.
        // usort($data['slides'], function ($a, $b) {
        // }

        $data = $block->setData($data);
    }

    public function form(
        PhpRenderer $view,
        SiteRepresentation $site,
        SitePageRepresentation $page = null,
        SitePageBlockRepresentation $block = null
    ) {
        $services = $site->getServiceLocator();
        $formElementManager = $services->get('FormElementManager');
        $defaultSettings = $services->get('Config')['timeline']['block_settings']['timelineExhibit'];
        $fieldset = $formElementManager->get(\Timeline\Form\TimelineExhibitFieldset::class);

        // Updated block with new params.
        if ($block) {
            $defaultSlides = $defaultSettings['slides'][0];
            unset($defaultSettings['slides']);
            $data = $block->data() + $defaultSettings;
            foreach ($data['slides'] as &$slide) {
                $slide += $defaultSlides;
            }
        } else {
            $data = $defaultSettings;
        }

        $dataForm = [];
        foreach ($data as $key => $value) {
            // Add fields for repeatable fieldsets with multiple fields.
            if (is_array($value)) {
                $subFieldsetName = "o:block[__blockIndex__][o:data][$key]";
                /** @var \Zend\Form\Fieldset $subFieldset */
                if (!$fieldset->has($subFieldsetName)) {
                    continue;
                }
                $subFieldset = $fieldset->get($subFieldsetName);
                $subFieldsetBaseName = $subFieldsetName . '[__' . substr($key, 0, -1) . 'Index__]';
                /** @var \Zend\Form\Fieldset $subFieldsetBase */
                if (!$subFieldset->has($subFieldsetBaseName)) {
                    continue;
                }
                $subFieldsetBase = $subFieldset->get($subFieldsetBaseName);
                foreach (array_values($value) as $subKey => $subValue) {
                    $newSubFieldsetName = $subFieldsetName . "[$subKey]";
                    /** @var \Zend\Form\Fieldset $newSubFieldset */
                    $newSubFieldset = clone $subFieldsetBase;
                    $newSubFieldset
                        ->setName($newSubFieldsetName)
                        ->setAttribute('data-index', $subKey);
                    $subFieldset->add($newSubFieldset);
                    foreach ($subValue as $subSubKey => $subSubValue) {
                        $elementBaseName = $subFieldsetBaseName . "[$subSubKey]";
                        $elementName = "o:block[__blockIndex__][o:data][$key][$subKey][$subSubKey]";
                        if (!$newSubFieldset->has($elementBaseName)) {
                            continue;
                        }
                        $newSubFieldset
                            ->get($elementBaseName)
                            ->setName($elementName)
                            ->setValue($subSubValue);
                        $dataForm[$elementName] = $subSubValue;
                    }
                    // $newSubFieldset->populateValues($dataForm);
                }
                $subFieldset
                    ->remove($subFieldsetBaseName)
                    ->setAttribute('data-next-index', count($value));
            } else {
                $dataForm['o:block[__blockIndex__][o:data][' . $key . ']'] = $value;
            }
        }

        $fieldset->populateValues($dataForm);

        // The slides are currently filled manually (use default form).

        return $view->formCollection($fieldset);
    }

    /**
     * Prepare the view to enable the block layout render.
     *
     * Typically used to append JavaScript to the head.
     *
     * @param PhpRenderer $view
     */
    public function prepareRender(PhpRenderer $view)
    {
        $view->headLink()
            ->appendStylesheet('//cdn.knightlab.com/libs/timeline3/latest/css/timeline.css');
        $view->headScript()
            ->appendFile('//cdn.knightlab.com/libs/timeline3/latest/js/timeline.js');
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $vars = [
            'block' => $block,
            'heading' => $block->dataValue('heading', ''),
            'options' => $block->dataValue('options', '{}'),
        ];
        return $view->partial(self::PARTIAL_NAME, $vars);
    }

    public function getFulltextText(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        // TODO Add resource titles and descriptions?
        $fulltext = $block->dataValue('heading', '');
        foreach ($block->dataValue('slides', []) as $slide) {
            $fulltext .= ' ' . $slide['start_date']
                . ' ' . $slide['end_date']
                . ' ' . $slide['start_display_date']
                . ' ' . $slide['end_display_date']
                . ' ' . $slide['display_date']
                . ' ' . $slide['headline']
                . ' ' . $slide['html']
                . ' ' . $slide['caption']
                . ' ' . $slide['credit'];
        }
        return $fulltext;
    }

    /**
     * Clean the text area from end of lines.
     *
     * This method fixes Windows and Apple copy/paste from a textarea input.
     *
     * @param string $string
     * @return string
     */
    protected function fixEndOfLine($string)
    {
        return str_replace(["\r\n", "\n\r", "\r"], ["\n", "\n", "\n"], $string);
    }
}
