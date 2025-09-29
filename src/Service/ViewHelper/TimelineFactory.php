<?php declare(strict_types=1);

namespace Timeline\Service\ViewHelper;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Timeline\View\Helper\Timeline;

/**
 * Service factory for the Timeline view helper.
 */
class TimelineFactory implements FactoryInterface
{
    /**
     * Create and return the Timeline view helper
     *
     * @return Timeline
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $config = $services->get('Config');
        $currentTheme = $services->get('Omeka\Site\ThemeManager')
            ->getCurrentTheme();

        return new Timeline(
            $currentTheme,
            $config['timeline']['block_settings']['timeline']
        );
    }
}
