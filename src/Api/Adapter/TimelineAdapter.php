<?php
namespace Timeline\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Api\Adapter\SiteSlugTrait;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class TimelineAdapter extends AbstractEntityAdapter
{
    use SiteSlugTrait;

    /**
     * {@inheritDoc}
     */
    protected $sortFields = [
        'id' => 'id',
        'slug' => 'slug',
        'is_public' => 'isPublic',
        'created' => 'created',
        'modified' => 'modified',
    ];

    public function getResourceName()
    {
        return 'timelines';
    }

    public function getRepresentationClass()
    {
        return 'Timeline\Api\Representation\TimelineRepresentation';
    }

    public function getEntityClass()
    {
        return 'Timeline\Entity\Timeline';
    }

    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        // Partially inspired by Omeka\Api\Adapter\SiteAdapter and other ones.
        $this->hydrateOwner($request, $entity);
        $title = null;

        if ($this->shouldHydrate($request, 'o-module-timeline:title')) {
            $title = trim($request->getValue('o-module-timeline:title', ''));
            $entity->setTitle($title);
        }
        if ($this->shouldHydrate($request, 'o-module-timeline:slug')) {
            $default = null;
            $slug = trim($request->getValue('o-module-timeline:slug', ''));
            if ($slug === ''
                && $request->getOperation() === Request::CREATE
                && is_string($title)
                && $title !== ''
            ) {
                $slug = $this->getAutomaticSlug($title);
            }
            $entity->setSlug($slug);
        }
        if ($this->shouldHydrate($request, 'o-module-timeline:description')) {
            $description = trim($request->getValue('o-module-timeline:description', ''));
            $entity->setDescription($description);
        }
        if ($this->shouldHydrate($request, 'o-module-timeline:is_public')) {
            $entity->setIsPublic($request->getValue('o-module-timeline:is_public', true));
        }
        if ($this->shouldHydrate($request, 'o-module-timeline:parameters')) {
            $settings = $this->getServiceLocator()->get('Omeka\Settings');
            $parameters = $request->getValue(
                'o-module-timeline:parameters',
                $settings->get('timeline_default')
            );
            $entity->setParameters($parameters);
        }
        if ($this->shouldHydrate($request, 'o-module-timeline:item_pool')) {
            $entity->setItemPool($request->getValue('o-module-timeline:item_pool', []));
        }
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        $title = $entity->getTitle();
        if (!is_string($title) || $title === '') {
            $errorStore->addError('o-module-timeline:title', 'A timeline must have a title.'); // @translate
        }
        $slug = $entity->getSlug();
        if (!is_string($slug) || $slug === '') {
            $errorStore->addError('o-module-timeline:slug', 'The slug cannot be empty.'); // @translate
        }
        if (preg_match('/[^a-zA-Z0-9_-]/u', $slug)) {
            $errorStore->addError('o-module-timeline:slug', 'A slug can only contain letters, numbers, underscores, and hyphens.'); // @translate
        }
        if (!$this->isUnique($entity, ['slug' => $slug])) {
            $errorStore->addError('o-module-timeline:slug', new Message(
                'The slug "%s" is already taken.', // @translate
                $slug
            ));
        }

        if (!is_array($entity->getItemPool())) {
            $errorStore->addError('o-module-timeline:item_pool', 'A timeline must have item pool data.'); // @translate
        }
    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {
        if (isset($query['owner_id'])) {
            $userAlias = $this->createAlias();
            $qb->innerJoin(
                'Omeka\Entity\Site.owner',
                $userAlias
            );
            $qb->andWhere($qb->expr()->eq(
                "$userAlias.id",
                $this->createNamedParameter($qb, $query['owner_id']))
            );
        }

        if (isset($query['is_public'])) {
            $qb->andWhere($qb->expr()->eq(
                $this->getEntityClass() . '.isPublic',
                $this->createNamedParameter($qb, (bool) $query['is_public'])
            ));
        }
    }
}
