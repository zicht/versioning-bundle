<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\VersioningBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zicht\Bundle\VersioningBundle\Entity\EntityVersion;
use Zicht\Bundle\VersioningBundle\Entity\Test\ChildOfNestedContentItem;
use Zicht\Bundle\VersioningBundle\Entity\Test\ContentItem;
use Zicht\Bundle\VersioningBundle\Entity\Test\NestedContentItem;
use Zicht\Bundle\VersioningBundle\Entity\Test\OtherOneToManyRelation;
use Zicht\Bundle\VersioningBundle\Entity\Test\Page;

class TestUtilCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('zicht:versioning:test-util')
            ->addArgument('action', InputArgument::REQUIRED, 'The action the client should do')

            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'The page id, for identifing purposes')

            ->addOption('data', null, InputOption::VALUE_OPTIONAL|InputOption::VALUE_IS_ARRAY, 'The optional data, seperated by spaces')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $versioning = $this->getContainer()->get('zicht_versioning.manager');
        $serializer = $versioning->getSerializer();

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

                if (!empty($data['save-as-active'])) {
                    $versioning->startActiveTransaction($page);
                }

                if (!empty($data['version'])) {
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
                $otherOneToManyRelationClassMetadata = $em->getClassMetadata('Zicht\Bundle\VersioningBundle\Entity\Test\OtherOneToManyRelation');
                $nestedContentItemClassMetadata = $em->getClassMetadata('Zicht\Bundle\VersioningBundle\Entity\Test\NestedContentItem');
                $nestedChildContentItemClassMetadata = $em->getClassMetadata('Zicht\Bundle\VersioningBundle\Entity\Test\ChildOfNestedContentItem');
                $entityVersionClassMetadata = $em->getClassMetadata('Zicht\Bundle\VersioningBundle\Entity\EntityVersion');
                $connection = $em->getConnection();

                $connection->beginTransaction();

                try {
                    $connection->query('SET FOREIGN_KEY_CHECKS=0');
                    $connection->query('DELETE FROM '.$pageClassMetadata->getTableName());
                    $connection->query('DELETE FROM '.$entityVersionClassMetadata->getTableName());
                    $connection->query('DELETE FROM '.$contentItemClassMetadata->getTableName());
                    $connection->query('DELETE FROM '.$otherOneToManyRelationClassMetadata->getTableName());
                    $connection->query('DELETE FROM '.$nestedContentItemClassMetadata->getTableName());
                    $connection->query('DELETE FROM '.$nestedChildContentItemClassMetadata->getTableName());
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
                $page->setTestingId($id);
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

                if (!empty($data['save-as-active'])) {
                    $versioning->startActiveTransaction($page);
                }

                $contentItem = new ContentItem();
                $contentItem->setTestingId($contentItemId);
                $contentItem->setTitle($title);

                $page->addContentItem($contentItem);

                $em->persist($page);
                $em->flush();
                break;

            case 'create-other-otmr':
                $id = $input->getOption('id');
                $title = $data['title'];
                $contentItemId = $data['id'];

                /** @var Page $page */
                $page = $em->getRepository('Zicht\Bundle\VersioningBundle\Entity\Test\Page')->findById($input->getOption('id'));

                if (!empty($data['save-as-active'])) {
                    $versioning->startActiveTransaction($page);
                }

                $otherEntity = new OtherOneToManyRelation();
                $otherEntity->setTestingId($contentItemId);
                $otherEntity->setTitelo($title);

                $page->addOtherOneToManyRelation($otherEntity);

                $em->persist($page);
                $em->flush();
                break;

            case 'create-nested-contenitem':
                /** @var Page $page */
                $page = $em->getRepository('Zicht\Bundle\VersioningBundle\Entity\Test\Page')->findById($input->getOption('id'));

                if (!empty($data['save-as-active'])) {
                    $versioning->startActiveTransaction($page);
                }

                $nestedContentItem = new NestedContentItem();
                $nestedContentItem->setTestingId($data['nestedContentItemId']);
                $nestedContentItem->setTitle($data['nestedContentItemTitle']);
                $page->addNestedContentItem($nestedContentItem);

                $childNestedContentItem = new ChildOfNestedContentItem();
                $childNestedContentItem->setTestingId($data['childNestedContentItemId']);
                $childNestedContentItem->setTitle($data['childNestedContentItemTitle']);
                $nestedContentItem->addChildContentItem($childNestedContentItem);

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

                echo 'START --- ' . PHP_EOL;
                var_dump($deserialize);
                echo 'END --- ' . PHP_EOL;
                exit;
                break;

            default:
                $output->writeln("<error>Invalid sub-command `{$input->getArgument('action')}`");
        }
    }

    private function createPageWithContentItem()
    {
        $page = new Page();
        $page->setTestingId(1);
        $page->setTitle('titel');
        $page->setIntroduction('intro intro');

        $contentItem = new ContentItem();
        $contentItem->setTestingId(1);
        $contentItem->setTitle('CI titel');

        $page->addContentItem($contentItem);

        $contentItem2 = new ContentItem();
        $contentItem2->setTestingId(2);
        $contentItem2->setTitle('CI titel 2');

        $page->addContentItem($contentItem2);

        return $page;
    }
}