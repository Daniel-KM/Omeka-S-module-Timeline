<?php
namespace Timeline\Controller;

use Doctrine\ORM\EntityManager;
use Omeka\Api\Exception\NotFoundException;
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
        $blockId = (integer) $this->params('block-id');
        $block = $this->getBlock($blockId);

        $blockData = $block->getData();

        $data = $this->timelineData($blockData['item_pool'], $blockData['args']);

        $view = new JsonModel();
        $view->setVariables($data);
        return $view;
    }

    /**
     * Helper to get a site page block.
     *
     * @internal Site page blocks are not available via the api or the adapter.
     * @see Omeka\Api\Adapter\AbstractEntityAdapter::findEntity()
     *
     * @param int $blockId
     * @return Omeka\Entity\SitePageBlock
     */
    protected function getBlock($blockId)
    {
        $entityClass = 'Omeka\Entity\SitePageBlock';

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select($entityClass)
            ->from($entityClass, $entityClass)
            ->andWhere($qb->expr()->eq("$entityClass.id", ':id'))
            ->setParameter('id', $blockId)
            ->setMaxResults(1);

        $entity = $qb->getQuery()->getOneOrNullResult();
        if (!$entity) {
            throw new NotFoundException(sprintf(
                $this->getTranslator()->translate('%s entity with criteria %s not found'),
                $this->getEntityClass(),
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
