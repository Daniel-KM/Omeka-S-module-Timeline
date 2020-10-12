<?php declare(strict_types=1);
namespace Timeline\Service\Form;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Timeline\Form\TimelineExhibitFieldset;

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
