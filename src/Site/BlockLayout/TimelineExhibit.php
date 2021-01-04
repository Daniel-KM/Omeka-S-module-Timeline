<?php declare(strict_types=1);
namespace Timeline\Site\BlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Exception\NotFoundException;
use Omeka\Api\Manager as ApiManager;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Stdlib\ErrorStore;
use Omeka\Stdlib\HtmlPurifier;

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
     * @var ApiManager
     */
    protected $api;

    /**
     * @param HtmlPurifier $htmlPurifier
     */
    public function __construct(
        HtmlPurifier $htmlPurifier,
        ApiManager $api
    ) {
        $this->htmlPurifier = $htmlPurifier;
        $this->api = $api;
    }

    public function getLabel()
    {
        return 'Timeline Exhibit'; // @translate
    }

    public function prepareForm(PhpRenderer $view): void
    {
        $assetUrl = $view->plugin('assetUrl');
        $view->headLink()
            ->appendStylesheet($assetUrl('css/asset-form.css', 'Omeka'))
            ->appendStylesheet($assetUrl('css/timeline-form.css', 'Timeline'));
        $view->headScript()
            ->appendFile($assetUrl('js/asset-form.js', 'Omeka'))
            ->appendFile($assetUrl('js/timeline-exhibit-form.js', 'Timeline'));
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore): void
    {
        $data = $block->getData();

        if (!isset($data['slides'])) {
            $data['slides'] = [];
        }

        $data['scale'] = $data['scale'] === 'cosmological' ? 'cosmological' : 'human';

        // Clean all values.
        $data['slides'] = array_values(
            array_map(function ($v) {
                return array_map(function ($w) {
                    $w = trim((string) $w);
                    return strlen($w) ? $w : null;
                }, $v);
            }, $data['slides'])
        );

        // Normalize values and purify html.
        $data['slides'] = array_map(function ($v) {
            // Simplify checks.
            $v += [
                'resource' => null,
                'type' => 'event',
                'start_date' => '',
                'start_display_date' => '',
                'end_date' => '',
                'end_display_date' => '',
                'display_date' => '',
                'headline' => '',
                'html' => '',
                'content' => '',
                'caption' => '',
                'credit' => '',
                'background' => null,
                'background_color' => '',
                'group' => '',
            ];
            if (empty($v['type'])) {
                $v['type'] = 'event';
            }
            if (empty($v['resource'])) {
                $v['resource'] = null;
            }
            if ($v['html']) {
                $v['html'] = $this->fixEndOfLine($this->htmlPurifier->purify($v['html']));
            }
            if ($v['content']) {
                $v['content'] = $this->fixEndOfLine($this->htmlPurifier->purify($v['content']));
                if ($v['resource'] == strip_tags($v['content'])) {
                    $v['content'] = '';
                } elseif (empty($v['resource']) && is_numeric($v['content']) && $v['content']) {
                    $v['resource'] = (string) (int) $v['content'];
                    $v['content'] = '';
                }
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
        $data['slides'] = array_filter($data['slides'], function ($v) {
            unset($v['type']);
            return (bool) array_filter($v);
        });

        $this->startDateProperty = $data['start_date_property'];
        usort($data['slides'], [$this, 'sortEvent']);

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
            unset($slide);
        } else {
            $data = $defaultSettings;
        }

        $dataForm = [];
        foreach ($data as $key => $value) {
            // Add fields for repeatable fieldsets with multiple fields.
            if (is_array($value)) {
                $subFieldsetName = "o:block[__blockIndex__][o:data][$key]";
                if (!$fieldset->has($subFieldsetName)) {
                    continue;
                }
                /** @var \Laminas\Form\Fieldset $subFieldset */
                $subFieldset = $fieldset->get($subFieldsetName);
                $subFieldsetBaseName = $subFieldsetName . '[__' . substr($key, 0, -1) . 'Index__]';
                /** @var \Laminas\Form\Fieldset $subFieldsetBase */
                if (!$subFieldset->has($subFieldsetBaseName)) {
                    continue;
                }
                $subFieldsetBase = $subFieldset->get($subFieldsetBaseName);
                foreach (array_values($value) as $subKey => $subValue) {
                    $newSubFieldsetName = $subFieldsetName . "[$subKey]";
                    /** @var \Laminas\Form\Fieldset $newSubFieldset */
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
    public function prepareRender(PhpRenderer $view): void
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
        // TODO Add resource title, description, date, etc.?
        $fulltext = $block->dataValue('heading', '');
        foreach ($block->dataValue('slides', []) as $slide) {
            $fulltext .= ' ' . $slide['start_date']
                . ' ' . $slide['start_display_date']
                . ' ' . $slide['end_date']
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
     * Compare two partial or full dates.
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    protected function sortEvent($a, $b)
    {
        // There is only one title.
        if ($b['type'] === 'title') {
            return 1;
        }
        if ($a['type'] === 'title') {
            return -1;
        }

        if ($a['type'] !== $b['type']) {
            // Type is event or era.
            return ($a['type'] === 'event') ? -1 : 1;
        }

        // Prepare the date for b first.
        // strtotime() is not used, because date are partial or may be very old.
        if ($b['start_date']) {
            $dateB = $b['start_date'];
        } elseif ($this->startDateProperty && $b['resource']) {
            try {
                $resourceB = $this->api->read('resources', ['id' => $b['resource']])->getContent();
            } catch (NotFoundException $e) {
                return -1;
            }
            $dateB = $resourceB->value($this->startDateProperty);
            if (empty($dateB)) {
                return -1;
            }
            $dateB = (string) $dateB->value();
        } else {
            return -1;
        }

        // Prepare the date for a.
        if ($a['start_date']) {
            $dateA = $a['start_date'];
        } elseif ($this->startDateProperty && $a['resource']) {
            try {
                $resourceA = $this->api->read('resources', ['id' => $a['resource']])->getContent();
            } catch (NotFoundException $e) {
                return 1;
            }
            $dateA = $resourceA->value($this->startDateProperty);
            if (empty($dateA)) {
                return 1;
            }
            $dateA = $dateA->value();
        } else {
            return 1;
        }

        if ($dateA == $dateB) {
            if ($a['headline'] == $b['headline']) {
                return 0;
            }
            return ($a['headline'] < $b['headline']) ? -1 : 1;
        }

        // Normalize date before comparaison to avoid issue with date before 0.
        $minusA = substr($dateA, 0, 1) === '-' ? '-' : '';
        $minusB = substr($dateB, 0, 1) === '-' ? '-' : '';
        if ($minusA && !$minusB) {
            return -1;
        } elseif (!$minusA && $minusB) {
            return 1;
        }

        // Compare each part to manage partial date. Not optimized, but used
        // only before save.

        // Make the two dates positive to simplify comparaison.
        $compare = (bool) $minusA ? -1 : 1;
        if ($compare === -1) {
            $dateA = substr($dateA, 1);
            $dateB = substr($dateB, 1);
        }

        // Compare the year. The year is always present and can be cosmological.
        $yearA = (int) strtok($dateA, '-');
        $yearB = (int) strtok($dateB, '-');
        if ($yearA !== $yearB) {
            return ($yearA < $yearB) ? -$compare : $compare;
        }

        // Only the year is compared with minus: in any year, January is before
        // February.

        $partsA = [];
        $partsB = [];
        $regex = '~^(\d+)-?(\d*)-?(\d*)T?(\d*):?(\d*):?(.*)$~';
        preg_match($regex, $dateA, $partsA);
        preg_match($regex, $dateB, $partsB);

        for ($i = 2; $i <= 6; $i++) {
            if ($partsA[$i] === '' && $partsB[$i] === '') {
                return 0;
            }
            if ($partsA[$i] === '') {
                return -1;
            }
            if ($partsB[$i] === '') {
                return 1;
            }
            if ($partsA[$i] !== $partsB[$i]) {
                return ($partsA[$i] < $partsB[$i]) ? -1 : 1;
            }
        }

        return 0;
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
