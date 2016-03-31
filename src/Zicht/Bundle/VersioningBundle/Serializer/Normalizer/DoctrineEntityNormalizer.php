<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\VersioningBundle\Serializer\Normalizer;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * Normalizer handling the serialization of doctrine entities based on doctrine meta data
 *
 * @package Zicht\Bundle\VersioningBundle\Serializer\Normalizer
 */
class DoctrineEntityNormalizer extends AbstractNormalizer
{
    /**
     * Construct the normalizer
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        parent::__construct();

        $this->em = $em;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * {@inheritDoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return is_object($data) && $this->em->getMetadataFactory()->hasMetadataFor(ClassUtils::getRealClass(get_class($data)));
    }


    /**
     * {@inheritDoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        /** @var ClassMetadataInfo $classMetadata */
        list($className, $classMetadata) = $this->getClassMetaData($object);

        $ret = ['__class__' => $className];

        foreach ($classMetadata->getFieldNames() as $fieldName) {
            $ret[$fieldName] = $this->propertyAccessor->getValue($object, $fieldName);
            if (null !== $ret[$fieldName] && !is_scalar($ret[$fieldName])) {
                $ret[$fieldName] = $this->serializer->normalize($ret[$fieldName], $format, $context);
            }
        }
        foreach ($classMetadata->getAssociationNames() as $associationName) {
            $associationMetadata = $classMetadata->associationMappings[$associationName];

            switch ($associationMetadata['type']) {
                case ClassMetadataInfo::ONE_TO_MANY:
                    $ret[$associationName] = [];
                    if ($this->propertyAccessor->getValue($object, $associationName)) {
                        foreach ($this->propertyAccessor->getValue($object, $associationName) as $association) {
                            $child = $this->normalize($association, $format, $context);
                            unset($child['id']);
                            $ret[$associationName][]= $child;
                        }
                    }
                    break;
                case ClassMetadataInfo::MANY_TO_ONE:
                    if ($association = $this->propertyAccessor->getValue($object, $associationName)) {
                        $ret[$associationName]= $this->serializeReferencedAssociation($association);
                    } else {
                        $ret[$associationName]= null;
                    }
                    break;
                case ClassMetadataInfo::MANY_TO_MANY:
                    $ret[$associationName] = [];
                    if ($associations = $this->propertyAccessor->getValue($object, $associationName)) {
                        foreach ($associations as $association) {
                            $ret[$associationName][]= $this->serializeReferencedAssociation($association);
                        }
                    }
                    break;
                default:
                    throw new UnexpectedValueException("Could not normalize assocation '{$associationName}' on '{$className}'");
            }
        }

        return $ret;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        if ($type && $this->em->getMetadataFactory()->hasMetadataFor($type)) {
            return true;
        }
        return isset($data['__class__']) && $this->em->getMetadataFactory()->hasMetadataFor($data['__class__']);
    }

    /**
     * {@inheritDoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        if (isset($data['__class__'])) {
            $class = $data['__class__'];
        }
        if (isset($context['object'])) {
            $object = $context['object'];
            unset($context['object']);
        } else {
            $reflectionClass = new \ReflectionClass($class);
            $object = $reflectionClass->newInstance();
        }

        /** @var ClassMetadataInfo $classMetadata */
        list($className, $classMetadata) = $this->getClassMetaData($object);

        foreach ($classMetadata->getFieldNames() as $fieldName) {
            if ($fieldName === 'id') {
                continue;
            }

            if (array_key_exists($fieldName, $data)) {
                $fieldValue = $data[$fieldName];
                if (null !== $fieldValue && !is_scalar($fieldValue)) {
                    if (isset($fieldValue['__class__'])) {
                        $fieldValue = $this->serializer->denormalize($fieldValue, $fieldValue['__class__'], $format, $context);
                    }
                }
            } else {
                $fieldValue = null;
            }

            try {
                $this->propertyAccessor->setValue($object, $fieldName, $fieldValue);
            } catch (NoSuchPropertyException $e) {
                try {
                    $refl = new \ReflectionProperty(
                        $classMetadata->fieldMappings[$fieldName]['declared'],
                        $classMetadata->fieldMappings[$fieldName]['fieldName']
                    );
                    $refl->setAccessible(true);
                    $refl->setValue($object, $fieldValue);
                } catch (\Exception $e) {
                }
            }
        }
        foreach ($classMetadata->getAssociationNames() as $associationName) {
            if (!array_key_exists($associationName, $data)) {
                continue;
            }

            $associationMetadata = $classMetadata->associationMappings[$associationName];

            switch ($associationMetadata['type']) {
                case ClassMetadataInfo::ONE_TO_MANY:
                    $values = [];
                    foreach ($data[$associationName] as $association) {
                        $values[] = $this->denormalize($association, $association['__class__'], $format, $context);
                    }

                    $this->propertyAccessor->setValue($object, $associationName, $values);
                    break;
                case ClassMetadataInfo::MANY_TO_ONE:
                    if (null !== $data[$associationName]) {
                        $this->propertyAccessor->setValue($object, $associationName, $this->resolveReferencedAssociation($data[$associationName]));
                    } else {
                        $this->propertyAccessor->setValue($object, $associationName, null);
                    }
                    break;
                case ClassMetadataInfo::MANY_TO_MANY:
                    $values = [];
                    foreach ($data[$associationName] as $association) {
                        $values[] = $this->resolveReferencedAssociation($association);
                    }
                    $this->propertyAccessor->setValue($object, $associationName, $values);
                    break;
                default:
                    throw new UnexpectedValueException("Could not denormalize assocation '{$associationName}' on '{$className}'");
            }
        }

        return $object;
    }

    /**
     * Helper method to find the class meta data.
     *
     * @param object $object
     * @return array
     *
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Exception
     */
    protected function getClassMetaData($object)
    {
        $className = ClassUtils::getRealClass(is_object($object) ? get_class($object) : $object);
        $classMetadata = $this->em->getMetadataFactory()->getMetadataFor($className);
        return array($className, $classMetadata);
    }

    /**
     * Finds the referenced entity in the entity manager.
     *
     * @param array $reference
     * @return null|object
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    protected function resolveReferencedAssociation($reference)
    {
        return $this->em->find($reference['__class__'], $reference['id']);
    }

    /**
     * Returns the class and id in an array which can be used to deserialize as a reference to an existing object.
     *
     * @param object $associatedObject
     * @return array
     */
    protected function serializeReferencedAssociation($associatedObject)
    {
        return ['__class__' => ClassUtils::getRealClass(get_class($associatedObject)), 'id' => $associatedObject->getId()];
    }
}