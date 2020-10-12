<?php declare(strict_types=1);
namespace Timeline\Controller;

use Doctrine\ORM\EntityManager;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Omeka\Api\Exception\NotFoundException;
use Omeka\Stdlib\Message;

class TimelineController extends AbstractActionController
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function eventsAction()
    {
        $blockId = (int) $this->params('block-id');
        $block = $this->getBlock($blockId);

        $layout = $block->getLayout();
        if (!in_array($layout, ['timeline', 'timelineExhibit'])) {
            throw new NotFoundException(new Message(
                'Id %d is not a timeline.', // @translate
                $blockId
            ));
        }

        $blockData = $block->getData();
        $blockData['site_slug'] = $block->getPage()->getSite()->getSlug();

        // Get the site slug directly via the page.
        if ($block->getLayout() === 'timelineExhibit') {
            $data = $this->timelineExhibitData($blockData);
        } else {
            $query = $blockData['query'];
            unset($blockData['query']);
            $data = $this->timelineData($query, $blockData);
        }

        $view = new JsonModel();
        $view->setVariables($data);
        return $view;
    }

    /**
     * Helper to get a site page block.
     *
     * Note: Site page blocks are not available via the api or the adapter.
     * @see \Omeka\Api\Adapter\AbstractEntityAdapter::findEntity()
     *
     * @param int $blockId
     * @return \Omeka\Entity\SitePageBlock
     */
    protected function getBlock($blockId)
    {
        $entityClass = \Omeka\Entity\SitePageBlock::class;

        $isOldOmeka = \Omeka\Module::VERSION < 2;
        $alias = $isOldOmeka ? $entityClass : 'omeka_root';

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select($alias)
            ->from($entityClass, $alias)
            ->andWhere($qb->expr()->eq($alias . '.id', ':id'))
            ->setParameter('id', $blockId)
            ->setMaxResults(1);

        $entity = $qb->getQuery()->getOneOrNullResult();
        if (!$entity) {
            throw new NotFoundException(new Message(
                '%s entity with criteria %s not found', // @translate
                $entityClass,
                json_encode(['id' => $blockId])
            ));
        }

        return $entity;
    }

    /**
     * Get the entity manager.
     *
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager()
    {
        return $this->entityManager;
    }
}
