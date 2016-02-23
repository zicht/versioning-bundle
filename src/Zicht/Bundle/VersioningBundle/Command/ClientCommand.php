<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\VersioningBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zicht\Bundle\VersioningBundle\Entity\Test\Page;

class ClientCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('zicht:versioning:client')
            ->addArgument('action', InputArgument::REQUIRED, 'The action the client should do')

            ->addOption('id', null, InputArgument::OPTIONAL, 'The page id, for identifing purposes')

            ->addOption('title', null, InputArgument::OPTIONAL, 'The title to set')
            ->addOption('property', null, InputArgument::OPTIONAL, 'The property to change')
            ->addOption('value', null, InputArgument::OPTIONAL, 'The value what the new property should have')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $versioningService = $this->getContainer()->get('zicht_versioning.manager');

        switch ($input->getArgument('action')) {

            case 'create':
                $id = $input->getOption('id');
                $title = $input->getOption('title');
                $page = new Page();
                $page->setId($id);
                $page->setTitle($title);
                $em->persist($page);
                $em->flush();
                $output->writeln(json_encode(['id' => $page->getId()]));
                break;

            case 'retrieve':
                $page = $em->getRepository('Zicht\Bundle\VersioningBundle\Entity\Test\Page')->findById($input->getOption('id'));

                if ($page) {
                    $data = [];
                    $data['id'] = $page->getId();
                    $data['title'] = $page->getTitle();

                    $output->writeln(json_encode($data));
                }
                break;

            case 'change-property':
                $id = $input->getOption('id');
                $property = $input->getOption('property');
                $value = $input->getOption('value');

                $page = $em->getRepository('Zicht\Bundle\VersioningBundle\Entity\Test\Page')->findById($id);
                $methodName = 'set' . ucfirst($property);
                if (method_exists($page, $methodName)) {
                    call_user_func_array(array($page, $methodName), array($value));
                    $em->persist($page);
                    $em->flush();
                } else {
                    throw new \Exception(sprintf('Method %s does not exist on the page', $methodName));
                }
                break;

            case 'get-version-count':
                $page = $em->getRepository('Zicht\Bundle\VersioningBundle\Entity\Test\Page')->findById($input->getOption('id'));

                $output->writeln(json_encode(['count' => $versioningService->getVersionCount($page)]));
                break;

            case 'clear-test-records':
                $pageClassMetadata = $em->getClassMetadata('Zicht\Bundle\VersioningBundle\Entity\Test\Page');
                $entityVersionClassMetadata = $em->getClassMetadata('Zicht\Bundle\VersioningBundle\Entity\EntityVersion');
                $connection = $em->getConnection();

                $connection->beginTransaction();

                try {
                    $connection->query('SET FOREIGN_KEY_CHECKS=0');
                    $connection->query('DELETE FROM '.$pageClassMetadata->getTableName());
                    $connection->query('DELETE FROM '.$entityVersionClassMetadata->getTableName());
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