<?php
namespace Timeline\Site\BlockLayout;

use Zend\Form\Element\Select;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Zend\View\Renderer\PhpRenderer;
use Zend\Form\Form;

class TimelineStatic extends AbstractBlockLayout
{
    public function getLabel()
    {
        return 'Timeline (static)'; // @translate
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null
    ) {
        $data = $block ? $block->data() : [];

        $form = new Form();
        $form->add([
            'name' => 'o:block[__blockIndex__][o:data][timeline_slug]',
            'type' => 'Timeline\Form\Element\TimelineSelect',
            'options' => [
                'empty_option' => 'Select below...', // @translate
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);
        $services = $site->getServiceLocator();
        $apiManager = $services->get('Omeka\ApiManager');

        $element = $form->get('o:block[__blockIndex__][o:data][timeline_slug]');
        $element->setApiManager($apiManager);
        if ($data) {
            $form->setData([
                'o:block[__blockIndex__][o:data][timeline_slug]' => $data['timeline_slug'],
            ]);
        }

        return $view->partial(
            'common/block-layout/timeline-static-form',
            ['form' => $form]
        );
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $response = $view->api()->read(
            'timelines',
            ['slug' => $block->data()['timeline_slug']]
        );
        $timeline = $response->getContent();

        $config = $block->getServiceLocator()->get('Config');
        $external = $config['assets']['use_externals'];

        return $view->partial('common/block-layout/timeline-static', [
            'timeline' => $timeline,
            'external' => $external,
        ]);
    }
}
