<?php declare(strict_types=1);
namespace Timeline\Service\BlockLayout;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Timeline\Site\BlockLayout\TimelineExhibit;

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
