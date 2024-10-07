<?php declare(strict_types=1);

namespace Timeline\Controller;

use Doctrine\ORM\EntityManager;
use Laminas\Http\Response;
use Omeka\Api\Manager as ApiManager;
use Omeka\Entity\SitePageBlock;
use Omeka\Mvc\Exception\NotFoundException;
use Omeka\Stdlib\Message;
use Omeka\Stdlib\Paginator;
use Omeka\View\Model\ApiJsonModel;

/**
 * This controller extends the Omeka Api controller in order to manage rights
 * in the same way. This part is not a rest api (it does not manage resources).
 */
class ApiController extends \Omeka\Controller\ApiController
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    /**
     * @var array
     */
    protected $config;

    public function __construct(Paginator $paginator, ApiManager $api, EntityManager $entityManager, array $config)
    {
        $this->paginator = $paginator;
        $this->api = $api;
        $this->entityManager = $entityManager;
        $this->config = $config;
    }

    public function create($data, $fileData = [])
    {
        return $this->returnErrorMethodNotAllowed();
    }

    public function delete($id)
    {
        return $this->returnErrorMethodNotAllowed();
    }

    public function deleteList($data)
    {
        return $this->returnErrorMethodNotAllowed();
    }

    public function get($id)
    {
        return $this->returnErrorMethodNotAllowed();
    }

    public function getList()
    {
        $query = $this->cleanQuery();
        $blockId = $this->params('block-id');
        if (!$blockId) {
            $blockId = empty($query['block_id']) ? null : $query['block_id'];
        }

        if (!$blockId && empty($query)) {
            throw new \Omeka\Mvc\Exception\NotFoundException((string) new Message(
                'A well-formed url or a query is needed to get a timeline.' // @translate
            ));
        }

        if ($blockId) {
            /** @var \Omeka\Entity\SitePageBlock|\Omeka\Api\Representation\ItemSetRepresentation $blockOrResource */
            $blockOrResource = $this->getBlockOrResource($blockId);
            if (!$blockOrResource) {
                return $this->getErrorResult(
                    $this->getEvent(),
                    new NotFoundException('Block or resource not found') // @translate
                );
            }

            $isResource = $blockOrResource instanceof \Omeka\Api\Representation\AbstractResourceEntityRepresentation;
            if ($isResource) {
                // Warning: the site is undefined here, except if set in query.
                $resource = $blockOrResource;
                // Use the query and the options set in the config.
                $blockData = $this->config['timeline']['block_settings']['timeline'];
                $query['item_set_id'] = $resource->id();
                $output = ($query['output'] ?? 'simile') === 'knightlab' ? 'knightlab' : 'simile';
                $data = $output === 'knightlab'
                    ? $this->timelineKnightlabData($query, $blockData)
                    : $this->timelineSimileData($query, $blockData);
                return new ApiJsonModel($data, $this->getViewOptions());
            }

            $block = $blockOrResource;
            $layout = $block->getLayout();
            if (!in_array($layout, ['timeline', 'timelineExhibit'])) {
                return $this->getErrorResult(
                    $this->getEvent(),
                    new NotFoundException((string) new Message(
                        'Id %d is not a timeline.', // @translate
                        $blockId
                    ))
                );
            }

            /**
             * @see \Timeline\Mvc\Controller\Plugin\TimelineExhibitData
             * @see \Timeline\Mvc\Controller\Plugin\TimelineKnightlabData
             * @see \Timeline\Mvc\Controller\Plugin\TimelineSimileData
             */

            $blockData = $block->getData();
            $blockData['site_slug'] = null;
            try {
                // May be an issue when the page or the site is private.
                /** @see https://gitlab.com/Daniel-KM/Omeka-S-module-Timeline/-/issues/24 */
                /** @see https://github.com/Daniel-KM/Omeka-S-module-Timeline/issues/25 */
                $blockData['site_slug'] = $block->getPage()->getSite()->getSlug();
            } catch (\Exception $e) {
                $sql = <<<SQL
SELECT site.slug
FROM site
JOIN site_page ON site_page.site_id = site.id
JOIN site_page_block ON site_page_block.page_id = site_page.id
WHERE site_page_block.id = :block_id
;
SQL;
                $blockData['site_slug'] = $this->entityManager->getConnection()
                    ->executeQuery($sql, ['block_id' => $blockId], ['block_id' => \Doctrine\DBAL\ParameterType::INTEGER])
                    ->fetchOne();
            }

            // Get the site slug directly via the page.
            if ($layout === 'timelineExhibit') {
                $data = $this->timelineExhibitData($blockData);
            } else {
                $layoutData = $block->getLayoutData() ?? [];
                $layoutLibrary = $layoutData['template_name'] ?? 'timeline-simile';
                $query = $blockData['query'];
                unset($blockData['query']);
                $data = $layoutLibrary === 'timeline-knightlab'
                    ? $this->timelineKnightlabData($query, $blockData)
                    : $this->timelineSimileData($query, $blockData);
            }
            return new ApiJsonModel($data, $this->getViewOptions());
        }

        // Use the query and the options set in the config.
        $blockData = $this->config['timeline']['block_settings']['timeline'];
        $data = ($query['output'] ?? 'simile') === 'knightlab'
            ? $this->timelineKnightlabData($query, $blockData)
            : $this->timelineSimileData($query, $blockData);
        return new ApiJsonModel($data, $this->getViewOptions());
    }

    /**
     * Helper to get a site page block or an item set.
     *
     * Note: Site page blocks are not available via the api or the adapter.
     * @see \Omeka\Api\Adapter\AbstractEntityAdapter::findEntity()
     *
     * @param string|int $blockId
     * @return \Omeka\Entity\SitePageBlock|\Omeka\Api\Representation\ItemSetRepresentation|null
     */
    protected function getBlockOrResource($blockOrResourceId)
    {
        if (!$blockOrResourceId) {
            return null;
        }

        $first = substr((string) $blockOrResourceId, 0, 1);
        if ($first === 'r') {
            $resourceId = (int) substr((string) $blockOrResourceId, 1);
            try {
                return $this->api()->read('item_sets', $resourceId)->getContent();
            } catch (\Exception $e) {
                return null;
            }
        }

        $blockId = $first === 'b'
            ? (int) substr((string) $blockOrResourceId, 1)
            : (int) $blockOrResourceId;
        if (!$blockId) {
            return null;
        }

        $qb = $this->entityManager->createQueryBuilder();
        return $qb
            ->select('omeka_root')
            ->from(SitePageBlock::class, 'omeka_root')
            ->andWhere($qb->expr()->eq('omeka_root.id', ':id'))
            ->setParameter('id', $blockId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function head($id = null)
    {
        return $this->returnErrorMethodNotAllowed();
    }

    public function options()
    {
        return $this->returnErrorMethodNotAllowed();
    }

    public function patch($id, $data)
    {
        return $this->returnErrorMethodNotAllowed();
    }

    public function replaceList($data)
    {
        return $this->returnErrorMethodNotAllowed();
    }

    public function patchList($data)
    {
        return $this->returnErrorMethodNotAllowed();
    }

    public function update($id, $data)
    {
        return $this->returnErrorMethodNotAllowed();
    }

    public function notFoundAction()
    {
        return $this->returnError(
            $this->translate('Page not found'), // @translate
            Response::STATUS_CODE_404
        );
    }

    protected function returnErrorMethodNotAllowed()
    {
        return $this->returnError(
            $this->translate('Method Not Allowed'), // @translate
            Response::STATUS_CODE_405
        );
    }

    protected function returnError($message, $statusCode = Response::STATUS_CODE_400, array $errors = null)
    {
        $response = $this->getResponse();
        $response->setStatusCode($statusCode);
        $result = [
            'status' => $statusCode,
            'message' => $message,
        ];
        if (is_array($errors)) {
            $result['errors'] = $errors;
        }
        return new ApiJsonModel($result, $this->getViewOptions());
    }

    /**
     * Clean the query (site slug is not managed by Omeka).
     *
     * @return array
     */
    protected function cleanQuery()
    {
        $query = $this->params()->fromQuery();
        if (empty($query['site_id']) && !empty($query['site_slug'])) {
            $siteSlug = $query['site_slug'];
            if ($siteSlug) {
                $api = $this->api();
                $siteId = $api->searchOne('sites', ['slug' => $siteSlug], ['initialize' => false, 'returnScalar' => 'id'])->getContent();
                if ($siteId) {
                    $query['site_id'] = $siteId;
                }
            }
        }
        return $query;
    }
}
