<?php declare(strict_types=1);

namespace Timeline;

use Omeka\Stdlib\Message;

/**
 * @var Module $this
 * @var \Laminas\ServiceManager\ServiceLocatorInterface $services
 * @var string $newVersion
 * @var string $oldVersion
 *
 * @var \Omeka\Api\Manager $api
 * @var \Omeka\Settings\Settings $settings
 * @var \Doctrine\DBAL\Connection $connection
 * @var \Doctrine\ORM\EntityManager $entityManager
 * @var \Omeka\Mvc\Controller\Plugin\Messenger $messenger
 */
$plugins = $services->get('ControllerPluginManager');
$api = $plugins->get('api');
$settings = $services->get('Omeka\Settings');
$connection = $services->get('Omeka\Connection');
$messenger = $plugins->get('messenger');
$entityManager = $services->get('Omeka\EntityManager');

$localConfig = require dirname(__DIR__, 2) . '/config/module.config.php';

if (version_compare($oldVersion, '3.4.6', '<')) {
    // Replace item pool by a search query.
    $sql = <<<'SQL'
SELECT id, data
FROM site_page_block
WHERE layout = 'timeline';
SQL;
    $timelines = $connection->executeQuery($sql)->fetchAllKeyValue();
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
        $connection->executeStatement($sql);
    }
}

if (version_compare($oldVersion, '3.4.7', '<')) {
    $timelineLibrary = $settings->get('timeline_library', $localConfig['timeline']['block_settings']['timeline']['library']);
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
    $message = new Message(
        'The json is now built dynamically from the url /api/timeline.' // @translate
    );
    $messenger->addWarning($message);
}

if (version_compare($oldVersion, '3.4.19', '<')) {
    // Fix a possible issue in upgrade 3.4.7.
    $repository = $entityManager->getRepository(\Omeka\Entity\SitePageBlock::class);
    /** @var \Omeka\Entity\SitePageBlock[] $blocks */
    $blocks = $repository->findBy(['layout' => 'timeline']);
    foreach ($blocks as $block) {
        $data = $block->getData();
        if (empty($data['query'])) {
            $data['query'] = [];
        } elseif (is_string($data['query'])) {
            $query = [];
            parse_str($data['query'], $query);
            $data['query'] = $query;
        }
        $block->setData($data);
        $entityManager->persist($block);
    }
    $entityManager->flush();
}

if (version_compare($oldVersion, '3.4.20', '<')) {
    /** @see /BlockPlus/data/scripts/upgrade.php */

    $logger = $services->get('Omeka\Logger');

    $pageRepository = $entityManager->getRepository(\Omeka\Entity\SitePage::class);
    $blocksRepository = $entityManager->getRepository(\Omeka\Entity\SitePageBlock::class);

    /**
     * Replace filled setttings "heading" by a specific block "Heading" or "Html".
     */

    $hasBlockPlus = class_exists('BlockPlus\Module', false);
    $viewHelpers = $services->get('ViewHelperManager');
    $escape = $viewHelpers->get('escapeHtml');

    $blockTemplates = [
        'timeline' => 'common/block-layout/timeline',
        'timelineExhibit' => 'common/block-layout/timeline-exhibit',
    ];
    $blockTemplatesHeading = $blockTemplates;

    $pagesWithHeading = [];
    $processedBlocksId = [];
    foreach ($pageRepository->findAll() as $page) {
        $pageSlug = $page->getSlug();
        $siteSlug = $page->getSite()->getSlug();
        $position = 0;
        foreach ($page->getBlocks() as $block) {
            $block->setPosition(++$position);
            $layout = $block->getLayout();
            if (!isset($blockTemplatesHeading[$layout])) {
                continue;
            }
            $blockId = $block->getId();
            $data = $block->getData() ?: [];
            $heading = $data['heading'] ?? '';
            if (strlen($heading) && !isset($processedBlocksId[$blockId])) {
                $b = new \Omeka\Entity\SitePageBlock();
                $b->setLayout($hasBlockPlus ? 'heading' : 'html');
                $b->setPage($page);
                $b->setPosition(++$position);
                $b->setData($hasBlockPlus
                    ? ['text' => $heading, 'level' => 2]
                    :  ['html' => '<h2>' . $escape($heading) . '</h2>']
                );
                $entityManager->persist($b);
                $block->setPosition(++$position);
                $pagesWithHeading[$siteSlug][$pageSlug] = $pageSlug;
                $processedBlocksId[$blockId] = $blockId;
            }
            unset($data['heading']);
            $block->setData($data);
        }
    }

    // Do a clear to fix issues with new blocks created during migration.
    $entityManager->flush();
    $entityManager->clear();

    if (!empty($pagesWithHeading)) {
        $pagesWithHeading = array_map('array_values', $pagesWithHeading);
        $message = new Message(
            'The setting "heading" was removed from blocks. A new block "Heading" (module BlockPlus) or "Html" was prepended to all blocks that had a filled heading. You may check pages for styles: %s', // @translate
            json_encode($pagesWithHeading, 448)
        );
        $messenger->addWarning($message);
        $logger->warn((string) $message);
    }

    /**
     * Replace filled settings "template" by the new layout data for timeline.
     */

    $blockTemplatesRenamed = [
        'simile_online' => 'timeline-simile-online', // @translate
        'knightlab' => 'timeline-knightlab', // @translate
    ];

    foreach ($blocksRepository->findBy(['layout' => 'timeline']) as $block) {
        $data = $block->getData();
        $library = $data['library'] ?? null;
        if (isset($blockTemplatesRenamed[$library])) {
            $layoutData = $block->getLayoutData();
            $layoutData['template_name'] = $blockTemplatesRenamed[$library];
            $block->setLayoutData($layoutData);
        }
        unset($data['library']);
        $block->setData($data);
    }

    $entityManager->flush();
}

if (version_compare($oldVersion, '3.4.19', '<')) {
    $message = new Message(
        'It is now possible to add groups, eras and extra-markers, for example historical events, in timelines.' // @translate
    );
    $messenger->addSuccess($message);

    $message = new Message(
        'The timeline for Knightlab has been updated to avoid a js transformation. Check if you used the output of the api directly.' // @translate
    );
    $messenger->addWarning($message);
}
