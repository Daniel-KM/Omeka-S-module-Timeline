<?php
namespace Timeline\Service\BlockLayout;

use Timeline\Site\BlockLayout\TimelineExhibit;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class TimelineExhibitFactory implements FactoryInterface
{
    /**
     * Create the TimelineExhibit block layout service.
     *
     * @param ContainerInterface $serviceLocator
     * @return TimelineExhibit
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new TimelineExhibit(
            $services->get('Omeka\HtmlPurifier'),
            $services->get('Omeka\ApiManager')
        );
    }
}
