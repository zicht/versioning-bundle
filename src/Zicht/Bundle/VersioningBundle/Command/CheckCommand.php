<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Logging\EchoSQLLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zicht\Bundle\VersioningBundle\Doctrine\EventSubscriber;
use Zicht\Bundle\VersioningBundle\Manager\VersioningManager;
use Zicht\Bundle\VersioningBundle\Model\VersionableInterface;

/**
 * Class CheckCommand
 *
 * @package Zicht\Bundle\VersioningBundle\Command
 */
class CheckCommand extends Command
{
    /**
     * Constructor
     *
     * @param VersioningManager $versioning
     * @param Registry $doctrine
     */
    public function __construct(VersioningManager $versioning, Registry $doctrine)
    {
        parent::__construct();

        $this->vm = $versioning;
        $this->doctrine = $doctrine;
    }


    /**
     * @{inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('zicht:versioning:check')
            ->setDescription('Do some helpful integrity checks of the versioned data')
            ->addArgument('class', InputArgument::OPTIONAL, 'Class')
            ->addArgument('id', InputArgument::OPTIONAL, 'ID')
            ->addOption('fix', null, InputOption::VALUE_NONE, 'Try to fix reported problems')
            ->addOption('debug', null, InputOption::VALUE_NONE, 'Debug')
        ;
    }


    /**
     * @{inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->vm->setSystemToken();

        /** @var EventManager $evm */
        $evm = $this->doctrine->getManager()->getEventManager();

        foreach ($evm->getListeners('postLoad') as $listener) {
            if ($listener instanceof EventSubscriber) {
                $evm->removeEventListener('postLoad', $listener);
            }
        }

        if ($input->getOption('debug')) {
            $this->doctrine
                ->getConnection()
                ->getConfiguration()
                ->setSQLLogger(new EchoSQLLogger())
            ;
        }

        $numChanges = [];
        $em = $this->doctrine->getEntityManager();
        foreach ($em->getMetadataFactory()->getAllMetadata() as $data) {
            $className = $data->name;
            $changes = [];
            if ($input->getArgument('class') && $className !== $input->getArgument('class')) {
                continue;
            }

            if (!$this->vm->isManaged($className)) {
                $output->writeln(sprintf('%s is not managed, skipping', $className));
                continue;
            } else {
                $output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL && $output->writeln($className, ': ');

                foreach ($this->doctrine->getRepository($className)->findAll() as $object) {
                    if (get_class($object) !== $className) {
                        // don't do this on inheritance models
                        continue;
                    }
                    if ($input->getArgument('id') && $object->getId() != $input->getArgument('id')) {
                        continue;
                    }

                    if (!isset($numChanges[$className])) {
                        $numChanges[$className] = [0, 0];
                    }
                    $numChanges[$className][0]++;
                    /** @var VersionableInterface $object */
                    if (!$this->vm->findActiveVersion($object)) {
                        if ($input->getOption('fix') && ($v = $this->vm->fix($object))) {
                            $numChanges[$className][1] ++;

                            $changes[]= $v;
                            $output->writeln(
                                sprintf(
                                    '<comment>* %s@%d changed</comment>',
                                    get_class($object),
                                    $object->getId()
                                )
                            );
                        } else {
                            $output->writeln(
                                sprintf(
                                    '<comment>* %s@%d does not have an active version</comment>',
                                    get_class($object),
                                    $object->getId()
                                )
                            );
                        }
                    }
                }

                if ($changes) {
                    $output->writeln("Flushing changes ... ");
                    $this->vm->flushChanges($changes);
                    $this->vm->clear();
                }
                $em->clear();
            }
        }

        $output->writeln("Number of entities changed / checked");
        if (empty($numChanges)) {
            $output->writeln('None');
        } else {
            foreach ($numChanges as $className => list($numChecked, $numChanged)) {
                $output->writeln(sprintf(' * %s: %d / %d', $className, $numChanged, $numChecked));
            }
        }
    }
}