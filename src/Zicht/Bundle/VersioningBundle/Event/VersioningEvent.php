<?php
/**
 * @author Rik van der Kemp <rik@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class VersioningEvent
 *
 * @package Zicht\Bundle\VersioningBundle\Event
 */
class VersioningEvent extends Event
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var int
     */
    private $original_id;

    /**
     * Constructor
     *
     * @param integer $id
     * @param integer $original_id
     */
    public function __construct($id, $original_id)
    {
        $this->id = $id;
        $this->original_id = $original_id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getOriginalId()
    {
        return $this->original_id;
    }
}