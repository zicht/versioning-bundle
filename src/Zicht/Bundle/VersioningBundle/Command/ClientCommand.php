<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\VersioningBundle\Command;

use Sensio\Bundle\GeneratorBundle\Command\Helper\DialogHelper;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
//use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\Table;
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
            ->addArgument('value', InputArgument::OPTIONAL)
            ->addOption('title', 't', InputArgument::OPTIONAL, 'Specify the page title to operate on')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        $pageTitle = $input->getOption('title');

        switch ($input->getArgument('action')) {

            case 'create':
                $page = new Page();
                $page->setTitle($pageTitle);
                $em->persist($page);
                $em->flush();
                break;

            case 'retrieve':
                $page = $em->getRepository('Zicht\Bundle\VersioningBundle\Entity\Page')->findByTitle($pageTitle);

                if ($page) {
                    $data = [];
                    $data['id'] = $page->getId();
                    $data['title'] = $page->getTitle();

                    $output->writeln(json_encode($data));
                }
                break;

            case 'clear-test-records':
                $output->writeln('##### clear-test-records');

                $cmd = $em->getClassMetadata('Zicht\Bundle\VersioningBundle\Entity\Page');
                $connection = $em->getConnection();

                $connection->beginTransaction();

                try {
                    $connection->query('SET FOREIGN_KEY_CHECKS=0');
                    $connection->query('DELETE FROM '.$cmd->getTableName());
                    $connection->query('SET FOREIGN_KEY_CHECKS=1');
                    $connection->commit();
                } catch (\Exception $e) {
                    $connection->rollback();
                }
                break;

            default:
                $output->writeln("<error>Invalid sub-command `{$input->getArgument('action')}`");
        }
    }
}