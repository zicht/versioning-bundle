<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Services;


use Doctrine\Bundle\DoctrineBundle\Registry;
use Zicht\Bundle\VersioningBundle\Entity\IVersionable;

/**
 * Class VersioningService
 *
 * @package Zicht\Bundle\VersioningBundle\Services
 */
class VersioningService
{
    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * VersioningService constructor.
     *
     * @param Registry $doctrine
     */
    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * Get the version count for the given entity
     *
     * @param IVersionable $entity
     * @return integer
     */
    public function getVersionCount(IVersionable $entity)
    {
        $em = $this->doctrine->getManager();
        $result = $em->getRepository('ZichtVersioningBundle:EntityVersion')->findVersions($entity);
        return count($result);
    }
}