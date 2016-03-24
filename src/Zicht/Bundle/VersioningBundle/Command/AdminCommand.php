<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyPath;
use Zicht\Bundle\VersioningBundle\Entity\EntityVersion;
use Zicht\Bundle\VersioningBundle\Model\EntityVersionInterface;
use Zicht\Bundle\VersioningBundle\Manager\VersioningManager;
use Zicht\Bundle\VersioningBundle\Model\VersionableInterface;

/**
 * Class AdminCommand
 *
 * @package Zicht\Bundle\VersioningBundle\Command\AdminCommand
 */
class AdminCommand extends ContainerAwareCommand
{
    public function __construct(VersioningManager $versioning, Registry $doctrine)
    {
        parent::__construct();

        $this->versioning = $versioning;
        $this->doctrine = $doctrine;
    }


    protected function configure()
    {
        $this
            ->setName('zicht:versioning:admin')
            ->setDescription('Administrative utilities related to versioning')
            ->addArgument('entityClass', InputArgument::REQUIRED, 'Entity to work on')
            ->addArgument('entityId', InputArgument::REQUIRED, 'Entity id to work on')
            ->addOption('set-active', '', InputOption::VALUE_REQUIRED, 'Activate a specific version')
            ->addOption('versions', '', InputOption::VALUE_NONE, 'List all versions')
//            ->addOption('check', '', InputOption::VALUE_NONE, 'Do a consistency check for all versions')
//            ->addOption('touch', '', InputOption::VALUE_NONE, 'Touch the active version (i.e. force a new version to be created)')
            ->addOption('column', '', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, "Additional fields to be shown");
        ;
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $object = $this->doctrine->getManager()->find($input->getArgument('entityClass'), $input->getArgument('entityId'));

        if (!$object) {
            $output->writeln("Object not found: '{$input->getArgument('entityClass')}'@'{$input->getArgument('entityId')}'");
        } elseif (!$object instanceof VersionableInterface) {
            $output->writeln("Object is not versionable: '{$input->getArgument('entityClass')}'@'{$input->getArgument('entityId')}'");
        } else {
            if ($version = $this->versioning->getActiveVersion($object)) {
                $output->writeln("Active version: {$version->getVersionNumber()}");
            } else {
                $output->writeln("<comment>No active version for this entity found</comment>");
            }

            if ($activateVersion = $input->getOption('set-active')) {
                $this->versioning->setActive($object, $activateVersion);
            }
            if ($input->getOption('versions')) {
                $table = new Table($output);
                $headers = ['Version', 'Based on', 'Date', 'Data'];
                foreach ($input->getOption('column') as $column) {
                    $headers[]= $column;
                }
                $table->setHeaders($headers);
                $versions = $this->versioning->getVersions($object);
                foreach ($versions as $version) {
                    $row = [
                        $version->getVersionNumber() . ($version->isActive() ? ' *' : ''),
                        $version->getBasedOnVersion(),
                        $version->getDateCreated()->format('Y-m-d H:i:s'),
                        $output->getVerbosity() > 1
                            ? json_encode(json_decode($version->getData()), JSON_PRETTY_PRINT)
                            : substr($version->getData(), 0, 40) . ' ...'
                    ];
                    foreach ($input->getOption('column') as $column) {
                        $row[]= PropertyAccess::createPropertyAccessor()->getValue(json_decode($version->getData()), new PropertyPath($column));
                    }
                    $table->addRow($row);
                }
                $table->render();
            }
        }
    }
}