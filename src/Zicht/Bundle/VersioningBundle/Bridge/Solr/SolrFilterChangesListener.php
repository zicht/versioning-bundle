<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Bridge\Solr;

use Zicht\Bundle\SolrBundle\Event\SolrFilterChangesEvent;
use Zicht\Bundle\VersioningBundle\Manager\VersioningManager;
use Zicht\Bundle\VersioningBundle\Model\VersionableInterface;

/**
 * This listener removes all inactive versions from the solr changeset to prevent inactive versions from
 * being indexed.
 *
 * Depends on the event-based solr indexing introduced in zicht/solr-bundle 2.8.0-beta.1
 */
class FilterInactiveVersionsChangesListener
{
    /**
     * Constructor
     *
     * @param VersioningManager $versioningManager
     */
    public function __construct(VersioningManager $versioningManager)
    {
        $this->versioningManager = $versioningManager;
    }

    /**
     * Triggered when the solr bundle fires the 'zicht.solr.filter.changes' event.
     *
     * Filters out all entities that were affected and are not active.
     *
     * @param SolrFilterChangesEvent $event
     */
    public function onSolrFilterChanges(SolrFilterChangesEvent $event)
    {
        $event->getChangeSet()->filter(function($change) {
            list(, $entity) = $change;
            if ($entity instanceof VersionableInterface) {
                $affectedVersions = $this->versioningManager->getAffectedEntityVersions($entity);
                if (count($affectedVersions)) {
                    $affectedVersion = array_pop($affectedVersions);
                    if (!$affectedVersion->isActive()) {
                        return false;
                    }
                }
            }

            return true;
        });
    }
}