<?php
namespace Timeline\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

class TimelineController extends AbstractActionController
{
    public function eventsAction()
    {
        $response = $this->api()->read(
            'timelines',
            ['slug' => $this->params('timeline-slug')]
        );
        $timeline = $response->getContent();

        $data = $this->timelineData($timeline);

        $view = new JsonModel();
        $view->setVariables($data);
        return $view;
    }
}
