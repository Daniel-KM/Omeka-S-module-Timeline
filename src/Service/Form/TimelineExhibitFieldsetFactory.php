<?php
namespace Timeline\Service\Form;

use Timeline\Form\TimelineExhibitFieldset;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class TimelineExhibitFieldsetFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $urlHelper = $services->get('ViewHelperManager')->get('url');
        $form = new TimelineExhibitFieldset(null, $options);
        return $form
            ->setUrlHelper($urlHelper);
    }
}
