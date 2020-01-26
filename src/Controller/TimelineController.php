<?php
namespace Timeline\Controller;

use Doctrine\ORM\EntityManager;
use Omeka\Api\Exception\NotFoundException;
use Omeka\Stdlib\Message;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

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

        if ($block->getLayout() !== 'timeline') {
            throw new NotFoundException(new Message(
                'Id %d is not a timeline.', // @translate
                $blockId
            ));
        }

        $blockData = $block->getData();
        // Get the site slug directly via the page.
        $blockData['site-slug'] = $block->getPage()->getSite()->getSlug();

        $query = $blockData['query'];
        unset($blockData['query']);
        $data = $this->timelineData($query, $blockData);

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
