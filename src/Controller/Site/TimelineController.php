<?php
namespace Timeline\Controller\Site;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class TimelineController extends AbstractActionController
{
    public function browseAction()
    {
        $site = $this->currentSite();

        $this->setBrowseDefaults('created');
        $response = $this->api()->search('timelines', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));

        $view = new ViewModel;
        $timelines = $response->getContent();
        $view->setVariable('site', $site);
        $view->setVariable('timelines', $timelines);
        $view->setVariable('resources', $timelines);
        return $view;
    }

    public function showAction()
    {
        $site = $this->currentSite();

        $response = $this->api()->read(
            'timelines',
            ['slug' => $this->params('timeline-slug')]
        );
        $timeline = $response->getContent();

        $config = $this->getEvent()->getApplication()->getServiceManager()->get('Config');
        $external = $config['assets']['use_externals'];

        $view = new ViewModel;
        $view->setVariable('site', $site);
        $view->setVariable('timeline', $timeline);
        $view->setVariable('resource', $timeline);
        $view->setVariable('external', $external);
        return $view;
    }
}
