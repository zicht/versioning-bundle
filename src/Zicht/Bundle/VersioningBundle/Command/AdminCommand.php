<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zicht\Bundle\VersioningBundle\Entity\EntityVersion;
use Zicht\Bundle\VersioningBundle\Services\VersioningService;

/**
 * Class AdminCommand
 *
 * @package Zicht\Bundle\VersioningBundle\Command\AdminCommand
 */
class AdminCommand extends ContainerAwareCommand
{
    public function __construct(VersioningService $versioning)
    {
        parent::__construct();

        $this->versioning = $versioning;
    }


    protected function configure()
    {
        $this
            ->setName('zicht:versioning:admin')
            ->setDescription('Administrative utilities related to versioning')
            ->addArgument('entityClass', InputArgument::REQUIRED, 'Entity to work on')
            ->addArgument('entityId', InputArgument::REQUIRED, 'Entity id to work on')
            ->addOption('activate', '', InputOption::VALUE_REQUIRED, 'Activate a specific version')
            ->addOption('versions', '', InputOption::VALUE_NONE, 'List all versions')
            ->addOption('touch', '', InputOption::VALUE_NONE, 'Touch the active version (i.e. force a new version to be created)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $object = $this->versioning->find($input->getArgument('entityClass'), $input->getArgument('entityId'));

        if (!$object) {
            $output->writeln("Object not found: '{$input->getArgument('entityClass')}'@'{$input->getArgument('entityId')}'");
        } else {
            if ($version = $this->versioning->getActiveVersion($object)) {
                $output->writeln("Active version: {$version->getVersionNumber()}");
            } else {
                $output->writeln("<comment>No active version for this entity found</comment>");
            }

            if ($activateVersion = $input->getOption('activate')) {
                $this->versioning->setActive($object, $activateVersion);
            }
            if ($input->getOption('versions')) {
                $table = new Table($output);
                $table->setHeaders(['Version', 'Based on', 'Date', 'Data']);
                /** @var EntityVersion[] $versions */
                $versions = $this->versioning->getVersions($object);
                foreach ($versions as $version) {
                    $table->addRow([
                        $version->getVersionNumber() . ($version->isActive() ?  ' *' : ''),
                        $version->getBasedOnVersion(),
                        $version->getDateCreated()->format('Y-m-d H:i:s'),
                        $output->getVerbosity() > 1
                            ? json_encode(json_decode($version->getData()), JSON_PRETTY_PRINT)
                            : substr($version->getData(), 0, 40) . ' ...'
                    ]);
                }
                $table->render();
            }
        }
    }
}