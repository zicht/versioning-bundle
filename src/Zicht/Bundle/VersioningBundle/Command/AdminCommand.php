<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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
            ->addArgument('entityClass', InputArgument::REQUIRED, 'Entity to work on')
            ->addArgument('entityId', InputArgument::REQUIRED, 'Entity id to work on')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $object = $this->versioning->find($input->getArgument('entityClass'), $input->getArgument('entityId'));
        $this->versioning->getActiveVersion($object);
    }
}