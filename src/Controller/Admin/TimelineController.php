<?php
namespace Timeline\Controller\Admin;

use Omeka\Form\ConfirmForm;
use Omeka\Stdlib\Message;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Timeline\Form\Timeline as TimelineForm;
use Zend\View\Model\JsonModel;

class TimelineController extends AbstractActionController
{
    public function browseAction()
    {
        $this->setBrowseDefaults('created');
        $response = $this->api()->search('timelines', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));

        $view = new ViewModel;
        $timelines = $response->getContent();
        $view->setVariable('timelines', $timelines);
        $view->setVariable('resources', $timelines);
        return $view;
    }

    public function addAction()
    {
        $form = $this->getForm(TimelineForm::class);

        $defaults = [];
        $defaults['o-module-timeline:parameters'] = $this->settings()->get('timeline_defaults');
        $form->setData($defaults);

        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $data = $this->cleanData($data);
            $form->setData($data);
            if ($form->isValid()) {
                $response = $this->api($form)->create('timelines', $data);
                if ($response) {
                    $message = new Message(
                        'Timeline successfully created. %s', // @translate
                        sprintf(
                            '<a href="%s">%s</a>',
                            htmlspecialchars($this->url()->fromRoute(null, [], true)),
                            'Add another timeline?' // @translate
                    ));
                    $message->setEscapeHtml(false);
                    $this->messenger()->addSuccess($message);
                    return $this->redirect()->toUrl($response->getContent()->url());
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        return $view;
    }

    public function editAction()
    {
        $response = $this->api()->read(
            'timelines',
            ['slug' => $this->params('timeline-slug')]
        );
        $timeline = $response->getContent();

        $form = $this->getForm(TimelineForm::class);

        $data = $timeline->jsonSerialize();
        $data['item_pool'] = $data['o-module-timeline:item_pool'];
        $form->setData($data);

        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $data = $this->cleanData($data);
            $form->setData($data);
            if ($form->isValid()) {
                $response = $this->api($form)->update('timelines', ['slug' => $this->params('timeline-slug')], $data);
                if ($response) {
                    $this->messenger()->addSuccess('Timeline successfully updated.'); // @translate
                    // Explicitly re-read the site URL instead of using
                    // refresh() so we catch updates to the slug.
                    return $this->redirect()->toUrl($timeline->url());
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $view = new ViewModel;
        $view->setVariable('timeline', $timeline);
        $view->setVariable('resource', $timeline);
        $view->setVariable('form', $form);
        return $view;
    }

    public function showAction()
    {
        $response = $this->api()->read(
            'timelines',
            ['slug' => $this->params('timeline-slug')]
        );
        $timeline = $response->getContent();

        $config = $this->getEvent()->getApplication()->getServiceManager()->get('Config');
        $external = $config['assets']['use_externals'];

        $view = new ViewModel;
        $view->setVariable('timeline', $timeline);
        $view->setVariable('resource', $timeline);
        $view->setVariable('itemCount', $timeline->itemCount());
        $view->setVariable('external', $external);
        return $view;
    }

    public function showDetailsAction()
    {
        $response = $this->api()->read(
            'timelines',
            ['slug' => $this->params('timeline-slug')]
        );
        $timeline = $response->getContent();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('resource', $timeline);
        return $view;
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(ConfirmForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $response = $this->api($form)->delete('timelines', ['slug' => $this->params('timeline-slug')]);
                if ($response) {
                    $this->messenger()->addSuccess('Timeline successfully deleted.'); // @translate
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }
        return $this->redirect()->toRoute('admin/timeline');
    }

    public function deleteConfirmAction()
    {
        $response = $this->api()->read(
            'timelines',
            ['slug' => $this->params('timeline-slug')]
        );
        $timeline = $response->getContent();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('common/delete-confirm-details');
        $view->setVariable('resource', $timeline);
        $view->setVariable('resourceLabel', 'timeline');
        $view->setVariable('partialPath', 'timeline/admin/timeline/show-details');
        return $view;
    }

    public function itemsAction()
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

    /**
     * Helper to clean data before save.
     *
     * @param array $data
     * @return array
     */
    protected function cleanData($data)
    {
        $data['o-module-timeline:item_pool'] = json_decode($data['item_pool'], true);
        if (empty($data['o-module-timeline:parameters']['viewer'])) {
            $data['o-module-timeline:parameters']['viewer'] = '{}';
        }
        $vocabulary = strtok($data['o-module-timeline:parameters']['item_date'], ':');
        $name = strtok(':');
        $property = $this->api()
            ->searchOne('properties', ['vocabulary_prefix' => $vocabulary, 'local_name' => $name])
            ->getContent();
        $data['o-module-timeline:parameters']['item_date_id'] = (string) $property->id();
        return $data;
    }
}
