<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\VersioningBundle\Command;

use Sensio\Bundle\GeneratorBundle\Command\Helper\DialogHelper;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
//use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zicht\Bundle\VersioningBundle\Entity\Page;

class ClientCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('zicht:versioning:client')
            ->addArgument('action', InputArgument::REQUIRED, 'The action the client should do')
            ->addArgument('title', InputArgument::REQUIRED, 'Specify the page title to operate on')
            ->addArgument('value', InputArgument::OPTIONAL)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $doctrine = $this->getContainer()->get('doctrine');

        $pageTitle = $input->getArgument('title');

        switch ($input->getArgument('action')) {

            //TODO dit moet nog via versioning lopen ^^
            case 'create-new':
                $p = new Page();
                $p->setTitle($pageTitle);
                $doctrine->getManager()->persist($p);
                $doctrine->getManager()->flush();
                break;
            default:
                $output->writeln("<error>Invalid sub-command `{$input->getArgument('action')}`");
        }
    }
}