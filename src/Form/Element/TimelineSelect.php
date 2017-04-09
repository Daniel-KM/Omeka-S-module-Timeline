<?php
namespace Timeline\Form\Element;

use Omeka\Api\Manager as ApiManager;
use Zend\Form\Element\Select;

class TimelineSelect extends Select
{
    /**
     * @var ApiManager
     */
    protected $apiManager;

    public function getValueOptions()
    {
        $valueOptions = [];
        $response = $this->getApiManager()->search('timelines');
        foreach ($response->getContent() as $timeline) {
            $valueOptions[$timeline->slug()] = $timeline->title();
        }
        return $valueOptions;
    }

    /**
     * @param ApiManager $apiManager
     */
    public function setApiManager(ApiManager $apiManager)
    {
        $this->apiManager = $apiManager;
    }

    /**
     * @return ApiManager
     */
    public function getApiManager()
    {
        return $this->apiManager;
    }
}
