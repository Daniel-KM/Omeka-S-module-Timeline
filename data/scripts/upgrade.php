<?php declare(strict_types=1);
namespace Timeline;

use Omeka\Mvc\Controller\Plugin\Messenger;
use Omeka\Stdlib\Message;

/**
 * @var Module $this
 * @var \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
 * @var string $newVersion
 * @var string $oldVersion
 *
 * @var \Doctrine\DBAL\Connection $connection
 * @var \Doctrine\ORM\EntityManager $entityManager
 * @var \Omeka\Api\Manager $api
 */
$services = $serviceLocator;
$settings = $services->get('Omeka\Settings');
$connection = $services->get('Omeka\Connection');
$entityManager = $services->get('Omeka\EntityManager');
$config = require dirname(__DIR__, 2) . '/config/module.config.php';

if (version_compare($oldVersion, '3.4.6', '<')) {
    // Replace item pool by a search query.
    $sql = <<<'SQL'
SELECT id, data
FROM site_page_block
WHERE layout = 'timeline';
SQL;
    $stmt = $connection->executeQuery($sql);
    $timelines = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
    foreach ($timelines as $id => $data) {
        $data = json_decode($data, true);
        $data['args']['query'] = empty($data['item_pool']) ? [] : $data['item_pool'];
        unset($data['item_pool']);
        if (empty($data['args']['query'])) {
            $data['args']['query'] = ['item_date_id' => '7'];
        } elseif (is_string($data['args']['query'])) {
            $query = [];
            parse_str($data['args']['query'], $query);
            $data['args']['query'] = $query;
        }
        $data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $data = $connection->quote($data);
        $sql = <<<SQL
UPDATE site_page_block
SET data = $data
WHERE id = $id;
SQL;
        $connection->exec($sql);
    }
}

if (version_compare($oldVersion, '3.4.7', '<')) {
    $timelineLibrary = $settings->get('timeline_library', $config['timeline']['block_settings']['timeline']['library']);
    $internalAssets = $settings->get('timeline_internal_assets', true);
    if ($timelineLibrary === 'simile' && !$internalAssets) {
        $timelineLibrary = 'simile_online';
    }
    $settings->delete('timeline_library');
    $settings->delete('timeline_internal_assets');
    $settings->delete('timeline_defaults');

    $repository = $entityManager->getRepository(\Omeka\Entity\SitePageBlock::class);
    /** @var \Omeka\Entity\SitePageBlock[] $blocks */
    $blocks = $repository->findBy(['layout' => 'timeline']);
    foreach ($blocks as $block) {
        $data = $block->getData();
        if (empty($data['args']['query'])) {
            $data['args']['query'] = ['item_date_id' => '7'];
        } elseif (is_string($data['args']['query'])) {
            parse_str($data['args']['query'], $query);
            $data['args']['query'] = $query;
        }
        $data['args']['library'] = $timelineLibrary;
        $data['args']['item_date_id'] = empty($data['args']['item_date_id'])
            ? (empty($data['item_date_id']) ? '7' : $data['item_date_id'])
            : $data['args']['item_date_id'];
        $block->setData($data['args']);
        $entityManager->persist($block);
    }
    $entityManager->flush();
}

if (version_compare($oldVersion, '3.4.13.3', '<')) {
    $messenger = new Messenger();
    $message = new Message(
        'The json is now built dynamically from the url /api/timeline.' // @translate
    );
    $messenger->addWarning($message);
}
