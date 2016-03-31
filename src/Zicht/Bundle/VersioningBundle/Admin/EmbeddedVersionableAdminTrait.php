<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Admin;

/**
 * This trait is needed for embedded items
 */
trait EmbeddedVersionableAdminTrait
{
    public function id($entity)
    {
        if ($this->getParent()) {
            $idx = null;
            // TODO remove hardcoded relation
            foreach (array_values($entity->getPage()->getContentItems()->toArray()) as $idx => $item) {
                if ($item->getId() === $entity->getId()) {
                    break;
                }
                $idx = null;
            }
            return $idx;
        }

        return parent::id($entity);
    }


    public function getObject($id)
    {
        if ($this->getParent()) {
            // TODO remove hardcoded relation
            foreach (array_values($this->getParent()->getSubject()->getContentItems()->toArray()) as $idx => $item) {
                if ($idx === (int)$id) {
                    return $item;
                }
            }
        }
        return parent::getObject(-1);
    }

    public function update($object)
    {
        if ($this->getParent()) {
            $this->id($object);
            /** @var PersistentCollection $coll */
            $parent = $this->getParent()->getSubject();

            // TODO remove hardcoded relation
            $coll = $parent->getContentItems();
            foreach (array_keys($coll->toArray()) as $idx => $localKey) {
                if ($coll[$localKey]->getId() === $object->getId()) {
                    $coll[$localKey]= $object;
                }
            }
            $this->getParent()->update($parent);
            return $object;
        } else {
            return parent::update($object);
        }
    }


    public function generateObjectUrl($name, $object, array $parameters = array(), $absolute = false)
    {
        $idx = $this->id($object);
        $parameters['id'] = $idx;
        return $this->generateUrl($name, $parameters, $absolute);
    }
}