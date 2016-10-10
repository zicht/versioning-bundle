<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Admin;

/**
 * This trait is needed for embedded items
 */
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class EmbeddedVersionableAdminTrait
 * @package Zicht\Bundle\VersioningBundle\Admin
 */
trait EmbeddedVersionableAdminTrait
{
    /**
     * Uses the index in the collection as an id rather than the id itself.
     *
     * @param object $entity
     * @param bool $force
     * @return mixed
     */
    public function id($entity, $force = false)
    {
        if ($this->getParent() || $force) {
            $idx = null;
            // TODO remove hardcoded relation
            foreach (array_values($entity->getPage()->getContentItems()->toArray()) as $idx => $item) {
                if (($item->getId() && $item->getId() === $entity->getId())
                    || spl_object_hash($item) === spl_object_hash($entity)) {
                    // found it!
                    break;
                }
                $idx = null;
            }
            return $idx;
        } else {
            return parent::id($entity);
        }
    }


    /**
     * Overrides getObject() to fetch it from the parent object in stead of from it's own repository.
     *
     * @param mixed $id
     * @return mixed
     */
    public function getObject($id)
    {
        if ($this->getParent()) {
            // TODO remove hardcoded relation
            foreach (array_values($this->getParent()->getSubject()->getContentItems()->toArray()) as $idx => $item) {
                if ($idx === (int)$id) {
                    return $item;
                }
            }

            throw new NotFoundHttpException(sprintf('unable to find the object with _index_ : %s', $id));
        } else {
            return parent::getObject($id);
        }
    }

    /**
     * Overrides the regular 'update' function to follow the collection of the parent object in stead of the item
     * and trigger an update of the parent.
     *
     * @param mixed $object
     * @return mixed
     */
    public function update($object)
    {
        if ($this->getParent()) {
            /** @var PersistentCollection $coll */
            $parent = $this->getParent()->getSubject();

            $id = $this->id($object);
            // TODO remove hardcoded relation 'content items'
            $coll = $parent->getContentItems();
            foreach (array_keys($coll->toArray()) as $idx => $localKey) {
                if ($idx === $id) {
                    $coll[$localKey] = $object;
                }
            }

            $this->preUpdate($object);
            foreach ($this->extensions as $extension) {
                $extension->preUpdate($this, $object);
            }

            $this->getParent()->update($parent);

            $this->postUpdate($object);
            foreach ($this->extensions as $extension) {
                $extension->postUpdate($this, $object);
            }

            return $object;
        } else {
            return parent::update($object);
        }
    }


    /**
     * Use the index as an id in stead of the real id.
     *
     * @param string $name
     * @param mixed $object
     * @param array $parameters
     * @param bool|false $absolute
     * @return mixed
     */
    public function generateObjectUrl($name, $object, array $parameters = array(), $absolute = false)
    {
        $idx = $this->id($object);
        $parameters['id'] = $idx;
        return $this->generateUrl($name, $parameters, $absolute);
    }
}
