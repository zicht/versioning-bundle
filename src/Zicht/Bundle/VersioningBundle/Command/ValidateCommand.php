<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zicht\Bundle\VersioningBundle\Manager\VersioningManager;
use Zicht\Bundle\VersioningBundle\Model\VersionableInterface;

/**
 * Class ValidateCommand
 * @package Zicht\Bundle\VersioningBundle\Command
 */
class ValidateCommand extends Command
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
            ->addOption('fix', null, InputOption::VALUE_NONE, 'Try to fix reported problems')
        ;
    }


    /**
     * @{inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $checked = [];
        foreach ($this->doctrine->getEntityManager()->getMetadataFactory()->getAllMetadata() as $data) {
            $className = $data->name;
            $changes = [];
            if ($this->vm->isManaged($className)) {
                $output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL && $output->writeln($className, ': ');
                foreach ($this->doctrine->getRepository($className)->findAll() as $object) {
                    if (!isset($checked[$className])) {
                        $checked[$className] = 0;
                    }
                    $checked[$className]++;
                    /** @var VersionableInterface $object */
                    if (!$this->vm->findActiveVersion($object)) {
                        if ($input->getOption('fix') && ($v = $this->vm->fix($object))) {
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
                }
            }
        }

        foreach ($checked as $className => $numChecked) {
            $output->writeln(sprintf(' * %s: %d', $className, $numChecked));
        }
    }
}