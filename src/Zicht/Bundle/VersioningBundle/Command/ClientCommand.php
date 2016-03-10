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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zicht\Bundle\VersioningBundle\Entity\EntityVersion;
use Zicht\Bundle\VersioningBundle\Entity\Test\ContentItem;
use Zicht\Bundle\VersioningBundle\Entity\Test\Page;

class ClientCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('zicht:versioning:client')
            ->addArgument('action', InputArgument::REQUIRED, 'The action the client should do')

            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'The page id, for identifing purposes')

            ->addOption('data', null, InputOption::VALUE_OPTIONAL|InputOption::VALUE_IS_ARRAY, 'The optional data, seperated by spaces')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $versioning = $this->getContainer()->get('zicht_versioning.manager');
        $serializer = $this->getContainer()->get('zicht_versioning.serializer');

        $data = [];
        foreach ($input->getOption('data') as $optionData) {
            $explodedData = explode(':', $optionData, 2);
            $data[$explodedData[0]] = $explodedData[1];
        }

        switch ($input->getArgument('action')) {

            case 'change-property':
                $id = $input->getOption('id');
                $property = $data['property'];
                $value = $data['value'];
                
                $page = $em->getRepository('Zicht\Bundle\VersioningBundle\Entity\Test\Page')->findById($id);

                if ($data['save-as-active']) {
                    $versioning->startActiveTransaction($page);
                }

                if ($data['version']) {
                    $versioning->setCurrentWorkingVersionNumber($page, $data['version']);
                }
                
                $methodName = 'set' . ucfirst($property);
                if (method_exists($page, $methodName)) {
                    call_user_func_array(array($page, $methodName), array($value));
                    $em->persist($page);
                    $em->flush();

                    //TODO: needed?
                    //$versioning->stopActiveTransaction($page);
                } else {
                    throw new \Exception(sprintf('Method %s does not exist on the page', $methodName));
                }
                break;

            case 'clear-test-records':
                $pageClassMetadata = $em->getClassMetadata('Zicht\Bundle\VersioningBundle\Entity\Test\Page');
                $contentItemClassMetadata = $em->getClassMetadata('Zicht\Bundle\VersioningBundle\Entity\Test\ContentItem');
                $entityVersionClassMetadata = $em->getClassMetadata('Zicht\Bundle\VersioningBundle\Entity\EntityVersion');
                $connection = $em->getConnection();

                $connection->beginTransaction();

                try {
                    $connection->query('SET FOREIGN_KEY_CHECKS=0');
                    $connection->query('DELETE FROM '.$pageClassMetadata->getTableName());
                    $connection->query('DELETE FROM '.$entityVersionClassMetadata->getTableName());
                    $connection->query('DELETE FROM '.$contentItemClassMetadata->getTableName());
                    $connection->query('SET FOREIGN_KEY_CHECKS=1');
                    $connection->commit();
                } catch (\Exception $e) {
                    $connection->rollback();
                }
                break;

            case 'create':
                $id = $input->getOption('id');
                $title = $data['title'];

                $page = new Page();
                $page->setId($id);
                $page->setTitle($title);

                $em->persist($page);
                $em->flush();

                $output->writeln(json_encode(['id' => $page->getId()]));
                break;

            case 'create-content-item':
                $id = $input->getOption('id');
                $title = $data['title'];
                $contentItemId = $data['id'];

                /** @var Page $page */
                $page = $em->getRepository('Zicht\Bundle\VersioningBundle\Entity\Test\Page')->findById($input->getOption('id'));

                $contentItem = new ContentItem();
                $contentItem->setId($contentItemId);
                $contentItem->setTitle($title);

                $page->addContentItem($contentItem);

                $em->persist($page);
                $em->flush();
                break;

            case 'get-active-version':
                $page = $em->getRepository('Zicht\Bundle\VersioningBundle\Entity\Test\Page')->findById($input->getOption('id'));
                $entityVersion = $versioning->getActiveVersion($page);

                $output->writeln(json_encode(['versionNumber' => $entityVersion->getVersionNumber(), 'basedOnVersion' => $entityVersion->getBasedOnVersion()]));
                break;

            case 'get-version-count':
                $page = $em->getRepository('Zicht\Bundle\VersioningBundle\Entity\Test\Page')->findById($input->getOption('id'));

                $output->writeln(json_encode(['count' => $versioning->getVersionCount($page)]));
                break;

            case 'inject-data':
                $page = $em->getRepository('Zicht\Bundle\VersioningBundle\Entity\Test\Page')->findById($input->getOption('id'));
                $version = $data['version'];

                /** @var EntityVersion $entityVersion */
                $entityVersion = $em->getRepository('ZichtVersioningBundle:EntityVersion')->findVersion($page, $version);
                $entityVersion->setData($data['data']);
                $em->persist($entityVersion);
                $em->flush();
                break;

            case 'retrieve':
                $page = $em->getRepository('Zicht\Bundle\VersioningBundle\Entity\Test\Page')->findById($input->getOption('id'));

                $output->writeln($serializer->serialize($page));
                break;

            case 'retrieve-version':
                $page = $em->getRepository('Zicht\Bundle\VersioningBundle\Entity\Test\Page')->findById($input->getOption('id'));
                $version = $data['version'];

                $entityVersion = $em->getRepository('ZichtVersioningBundle:EntityVersion')->findVersion($page, $version);
                $output->writeln($serializer->serialize($serializer->deserialize($entityVersion)));
                break;

            case 'retrieve-based-on-version':
                $page = $em->getRepository('Zicht\Bundle\VersioningBundle\Entity\Test\Page')->findById($input->getOption('id'));
                $basedOnVersion = $data['based-on-version'];

                $entityVersion = $em->getRepository('ZichtVersioningBundle:EntityVersion')->findByBasedOnVersion($page, $basedOnVersion);
                $output->writeln($serializer->serialize($serializer->deserialize($entityVersion)));
                break;

            case 'set-active':
                $page = $em->getRepository('Zicht\Bundle\VersioningBundle\Entity\Test\Page')->findById($input->getOption('id'));
                $version = $data['version'];

                if ($page) {
                    $versioning->setActive($page, $version);
                }
                break;

            case 'serialize':
                $page = $this->createPageWithContentItem();

                $serialize = $serializer->serialize($page);

                var_dump($serialize);
                exit;
                break;

            case 'deserialize':
                $page = $this->createPageWithContentItem();
                $entityVersion = new EntityVersion();
                $entityVersion->setData($serializer->serialize($page));
                $entityVersion->setSourceClass(get_class($page));
                $deserialize = $serializer->deserialize($entityVersion);

                echo 'echdfsdfskljdfskjdfs';
                var_dump($deserialize);
                echo 'daaaar' . PHP_EOL;
                exit;
                break;

            default:
                $output->writeln("<error>Invalid sub-command `{$input->getArgument('action')}`");
        }
    }

    private function createPageWithContentItem()
    {
        $page = new Page();
        $page->setId(1);
        $page->setTitle('titel');
        $page->setIntroduction('intro intro');

        $contentItem = new ContentItem();
        $contentItem->setId(2);
        $contentItem->setTitle('CI titel');

        $page->addContentItem($contentItem);

        return $page;
    }
}