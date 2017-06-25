<?php
namespace Timeline;

use Omeka\Module\AbstractModule;
use Timeline\Form\Config as ConfigForm;
use Zend\Mvc\Controller\AbstractController;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Renderer\PhpRenderer;

class Module extends AbstractModule
{
    /**
     * Settings and their default values.
     *
     * @var array
     */
    protected $settings = [
        // Can be 'simile' or 'knightlab'.
        'timeline_library' => 'simile',
        'timeline_internal_assets' => false,
        'timeline_defaults' => [
            'item_title' => 'dcterms:title',
            'item_description' => 'dcterms:description',
            'item_date' => 'dcterms:date',
            'item_date_end' => '',
            // 'render_year' => \Timeline\Mvc\Controller\Plugin\TimelineData::RENDER_YEAR_DEFAULT,
            'render_year' => 'january_1',
            'center_date' => '9999-99-99',
            'viewer' => '{}',
            // The id of dcterms:date in the standard install of Omeka S.
            'item_date_id' => '7',
        ],
    ];

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);

        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        $acl->allow(null, 'Timeline\Controller\Timeline');
    }

    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $settings = $serviceLocator->get('Omeka\Settings');
        foreach ($this->settings as $name => $value) {
            $settings->set($name, $value);
        }
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $settings = $serviceLocator->get('Omeka\Settings');
        foreach ($this->settings as $name => $value) {
            $settings->delete($name);
        }
    }

    public function getConfigForm(PhpRenderer $renderer)
    {
        $services = $this->getServiceLocator();
        $formElementManager = $services->get('FormElementManager');
        $form = $formElementManager->get(ConfigForm::class);

        $data = [];
        $settings = $services->get('Omeka\Settings');
        foreach ($this->settings as $name => $value) {
            $data[$name] = $settings->get($name);
        }
        $form->setData($data);

        // Currently, Omeka S doesn't allow to display fieldsets in config form.
        $vars = [];
        $vars['form'] = $form;

        return $renderer->render('timeline/module/config.phtml', $vars);
    }

    public function handleConfigForm(AbstractController $controller)
    {
        $serviceLocator = $this->getServiceLocator();
        $settings = $serviceLocator->get('Omeka\Settings');

        $post = $controller->getRequest()->getPost()->toArray();

        $post['timeline_defaults']['viewer'] = trim($post['timeline_defaults']['viewer']);
        if ($post['timeline_defaults']['viewer'] === '') {
            $post['timeline_defaults']['viewer'] = '{}';
        }

        $vocabulary = strtok($post['timeline_defaults']['item_date'], ':');
        $name = strtok(':');
        $property = $this->getServiceLocator()->get('Omeka\ApiManager')
            ->search('properties', ['vocabulary_prefix' => $vocabulary, 'local_name' => $name])
            ->getContent();
        $property = reset($property);
        $post['timeline_defaults']['item_date_id'] = (string) $property->id();

        foreach ($this->settings as $settingKey => $settingValue) {
            if (isset($post[$settingKey])) {
                $settings->set($settingKey, $post[$settingKey]);
            }
        }
    }
}
