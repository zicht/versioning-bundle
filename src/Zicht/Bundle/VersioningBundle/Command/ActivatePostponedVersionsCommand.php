<?php
/**
 * @author Rik van der Kemp <rik@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Zicht\Bundle\VersioningBundle\Event\VersioningEvent;
use Zicht\Bundle\VersioningBundle\Manager\VersioningManager;
use Zicht\Itertools as iter;

/**
 * This command will look up versions that are supposed to be activated according to the setDateActiveFrom field.
 *
 * @package Zicht\Bundle\VersioningBundle\Command
 */
class ActivatePostponedVersionsCommand extends ContainerAwareCommand
{
    /**
     * @var VersioningManager
     */
    protected $versioning;
    /**
     * @var Registry
     */
    protected $doctrine;

    /**
     * Constructor.
     *
     * @param VersioningManager $versioning
     * @param Registry $doctrine
     */
    public function __construct(VersioningManager $versioning, Registry $doctrine)
    {
        parent::__construct();

        $this->versioning = $versioning;
        $this->doctrine = $doctrine;
    }

    /**
     * @{inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('zicht:versioning:schedule')
            ->setDescription('Search for versions that are supposed to be activated');
    }

    /**
     * @{inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** EventDispatcher */
        $dispatcher = $this->getContainer()->get('event_dispatcher');

        $this->versioning->setSystemToken();

        $unpublishRecords = 'UPDATE _entity_version SET is_active = false WHERE original_id = :original_id';
        $makeRecordActive = 'UPDATE _entity_version SET is_active = true WHERE id = :id LIMIT 1';

        /** @var Connection $connection */
        $connection = $this->doctrine->getConnection();
        $nowDate = new \DateTime('now');
        $now = $nowDate->format('Y-m-d H:i:s');

        $stmt = $connection->prepare('SELECT id, version_number, date_active_from, source_class, original_id, is_active FROM _entity_version WHERE date_active_from <= :date_active_from ORDER BY date_active_from DESC LIMIT 1');
        $stmt->execute([':date_active_from' => $now]);

        $result = $stmt->fetchAll();
        $result = iter\groupby('original_id', $result);

        $connection->beginTransaction();

        foreach ($result as $entityId => $records) {
            // First record is the one that should be published, we are not interested in the rest
            $scheduledVersion = $records[0];

            if ($scheduledVersion['is_active']) {
                // The latest scheduled entity is the one that should be active already so skip.
                continue;
            }

            $unpublishRecordsStmt = $connection->prepare($unpublishRecords);
            $unpublishRecordsStmt->bindValue(':original_id', $scheduledVersion['original_id']);
            $unpublishRecordsStmt->execute();

            // Set this record to active
            $makeActiveStmt = $connection->prepare($makeRecordActive);
            $makeActiveStmt->bindValue(':id', $scheduledVersion['id']);
            $makeActiveStmt->execute();
            $output->writeln(sprintf('<comment>Version Id: %d is set to active (OriginalId: %d)</comment>', $scheduledVersion['id'], $scheduledVersion['original_id']));
            $this->getContainer()->get('logger')->info(
                sprintf(
                    'Activated version %d of entity %s@%d (scheduled time)',
                    $scheduledVersion['version_number'],
                    $scheduledVersion['source_class'],
                    $scheduledVersion['original_id']
                )
            );

            $dispatcher->dispatch('zicht_versioning.activated', new VersioningEvent($scheduledVersion['id'], $scheduledVersion['original_id']));
        }

        $connection->commit();
    }
}
