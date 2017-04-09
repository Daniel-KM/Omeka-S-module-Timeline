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
        'title' => 'title',
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

        if ($this->shouldHydrate($request, 'o:title')) {
            $title = trim($request->getValue('o:title', ''));
            $entity->setTitle($title);
        }
        if ($this->shouldHydrate($request, 'o:slug')) {
            $default = null;
            $slug = trim($request->getValue('o:slug', ''));
            if ($slug === ''
                && $request->getOperation() === Request::CREATE
                && is_string($title)
                && $title !== ''
            ) {
                $slug = $this->getAutomaticSlug($title);
            }
            $entity->setSlug($slug);
        }
        if ($this->shouldHydrate($request, 'o:description')) {
            $description = trim($request->getValue('o:description', ''));
            $entity->setDescription($description);
        }
        if ($this->shouldHydrate($request, 'o:is_public')) {
            $entity->setIsPublic($request->getValue('o:is_public', true));
        }
        if ($this->shouldHydrate($request, 'o:args')) {
            $services = $this->getServiceLocator();
            $settings = $services->get('Omeka\Settings');
            $args = $request->getValue('o:args', $settings->get('timeline_default'));
            if (empty($args['viewer'])) {
                $args['viewer'] = [];
            }
            $vocabulary = strtok($args['item_date'], ':');
            $name = strtok(':');
            $property = $services->get('Omeka\ApiManager')
                ->search('properties', ['vocabulary_prefix' => $vocabulary, 'local_name' => $name])
                ->getContent();
            $property = reset($property);
            $args['item_date_id'] = (string) $property->id();
            $entity->setArgs($args);
        }
        if ($this->shouldHydrate($request, 'o:item_pool')) {
            $entity->setItemPool($request->getValue('o:item_pool', []));
        }
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        $title = $entity->getTitle();
        if (!is_string($title) || $title === '') {
            $errorStore->addError('o:title', 'A timeline must have a title.'); // @translate
        }
        $slug = $entity->getSlug();
        if (!is_string($slug) || $slug === '') {
            $errorStore->addError('o:slug', 'The slug cannot be empty.'); // @translate
        }
        if (preg_match('/[^a-zA-Z0-9_-]/u', $slug)) {
            $errorStore->addError('o:slug', 'A slug can only contain letters, numbers, underscores, and hyphens.'); // @translate
        }
        if (!$this->isUnique($entity, ['slug' => $slug])) {
            $errorStore->addError('o:slug', new Message(
                'The slug "%s" is already taken.', // @translate
                $slug
            ));
        }

        if (!is_array($entity->getItemPool())) {
            $errorStore->addError('o:item_pool', 'A timeline must have item pool data.'); // @translate
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
