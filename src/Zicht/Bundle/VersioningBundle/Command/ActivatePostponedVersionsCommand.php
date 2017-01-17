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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zicht\Bundle\VersioningBundle\Manager\VersioningManager;
use Zicht\Bundle\VersioningBundle\Model\VersionableInterface;
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
            ->addOption('dry-run', '', InputOption::VALUE_NONE, 'Do a dry run (don\'t commit changes)')
            ->setDescription('Search for versions that are supposed to be activated');
    }

    /**
     * @{inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->versioning->setSystemToken();
        $isDryRun = $input->getOption('dry-run');

        /** @var Connection $connection */
        $connection = $this->doctrine->getConnection();
        $nowDate = new \DateTime('now');
        $now = $nowDate->format('Y-m-d H:i:s');

        $stmt = $connection->prepare(
            'SELECT 
                id, 
                version_number, 
                date_active_from, 
                source_class, 
                original_id, 
                is_active, 
                based_on_version 
            FROM 
                _entity_version 
            WHERE 
                date_active_from <= :date_active_from 
            ORDER BY 
                date_active_from DESC
            '
        );

        $stmt->execute([':date_active_from' => $now]);

        $result = $stmt->fetchAll();
        $result = iter\groupby('original_id', $result);

        $connection->beginTransaction();
        $i = 0;
        foreach ($result as $entityId => $records) {
            // First record is the one that should be published, we are not interested in the rest
            $scheduledVersion = iter\first($records);
            if ($scheduledVersion['is_active']) {
                // The latest scheduled entity is the one that is being activated right now, so if it's already active,
                // nothing to be done here.
                continue;
            }

            try {
                $i ++;

                $this->versioning->setVersionToLoad($scheduledVersion['source_class'], $scheduledVersion['original_id'], $scheduledVersion['version_number']);
                /** @var VersionableInterface $entity */
                $entity = $this->doctrine->getManager()->find($scheduledVersion['source_class'], $scheduledVersion['original_id']);
                $this->versioning->setVersionOperation($entity, VersioningManager::VERSION_OPERATION_ACTIVATE, $scheduledVersion['version_number'], $scheduledVersion['based_on_version']);
                $this->doctrine->getManager()->persist($entity);

                $this->getContainer()->get('logger')->info(
                    sprintf(
                        'Activated version %d of entity %s@%d (scheduled time) %s',
                        $scheduledVersion['version_number'],
                        $scheduledVersion['source_class'],
                        $scheduledVersion['original_id'],
                        $isDryRun ? ' [DRY RUN]' : ''
                    )
                );

                if ($output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
                    $output->writeln(sprintf('<comment>Version Id: %d is set to active (OriginalId: %d) %s</comment>', $scheduledVersion['id'], $scheduledVersion['original_id'], $isDryRun ? ' [DRY RUN]' : ''));
                }
            } catch (\Exception $e) {
                $message = sprintf('"%s" while trying to activate version %d', $e->getMessage(), $scheduledVersion['id']);
                $output->writeln(sprintf('<error>%s</error>', $message));
                $this->getContainer()->get('logger')->error($message);
            }
        }

        if ($i > 0) {
            if ($isDryRun) {
                $connection->rollBack();
            } else {
                $this->doctrine->getManager()->flush();
                $connection->commit();
            }
        }
    }
}
