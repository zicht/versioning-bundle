<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\VersioningBundle\Serializer;

use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Zicht\Bundle\VersioningBundle\Model\EntityVersionInterface;
use Zicht\Bundle\VersioningBundle\Model\VersionableInterface;
use Zicht\Bundle\VersioningBundle\Serializer\Normalizer\DateTimeNormalizer;
use Zicht\Bundle\VersioningBundle\Serializer\Normalizer\DoctrineEntityNormalizer;
use Symfony\Component\Serializer\Serializer as BaseSerializer;
use Zicht\Bundle\VersioningBundle\Serializer\Normalizer\FileNormalizer;

/**
 * Class Serializer
 *
 * @package Zicht\Bundle\VersioningBundle\Serializer
 */
class Serializer
{
    /** @var Serializer */
    private $serializer;
    /** @var EntityManager  */
    protected $manager;

    /**
     * SerializerService constructor.
     *
     * @param EntityManager $manager
     */
    public function __construct(EntityManager $manager)
    {
        $this->manager = $manager;
        $this->serializer = new BaseSerializer(
            [new DateTimeNormalizer(), new FileNormalizer(), new DoctrineEntityNormalizer($manager)],
            [new JsonEncoder()]
        );
    }

    /**
     * Serializes the given entity
     *
     * @param VersionableInterface $entity
     * @return string
     */
    public function serialize(VersionableInterface $entity)
    {
        return $this->serializer->serialize($entity, 'json');
    }

    /**
     * Deserializes the given entity
     *
     * @param EntityVersionInterface $entityVersion
     * @param VersionableInterface $targetObject
     * @return VersionableInterface $entity
     */
    public function deserialize(EntityVersionInterface $entityVersion, $targetObject = null)
    {
        $className = $entityVersion->getSourceClass();
        if (!class_exists($className)) {
            throw new \InvalidArgumentException("Entity version does not have a source class");
        }
        if (null !== $targetObject && !$targetObject instanceof $className) {
            throw new \InvalidArgumentException("Trying to deserialize into an object of a mismatching type");
        }
        $this->validate($entityVersion);
        return $this->serializer->deserialize(
            $entityVersion->getData(),
            $entityVersion->getSourceClass(),
            'json',
            ['object' => $targetObject]
        );
    }

    /**
     * Some pre-flight checkup before we try to deserialize the object
     *
     * @param EntityVersionInterface $entityVersion
     */
    protected function validate(EntityVersionInterface $entityVersion)
    {
        $data = json_decode($entityVersion->getData(), true);
        $conn = $this->manager->getConnection();
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $this->checkCorpsReferences($value, $data, $key, $conn);
            }
        }
        $entityVersion->setData(json_encode($data));
    }

    /**
     * check and remove references for linked entities that
     * no longer exists in the db.
     *
     * @param array $data
     * @param array $ref
     * @param string $key
     * @param Connection $conn
     */
    protected function checkCorpsReferences(array $data, &$ref, $key, Connection $conn)
    {
        foreach($data as $name => $value) {
            if ('__class__' === $name) {
                try {
                    $metadata = $this->manager->getClassMetadata($value);
                    $idName = $metadata->getSingleIdentifierFieldName();
                    if (isset($data[$idName])) {
                        $stmt = $conn->query($this->fmtQuery($metadata, $conn, $data[$idName]));
                        $stmt->execute();
                        if (0 === (int)$stmt->fetchColumn()) {
                            unset($ref[$key]);
                        }
                    }
                } catch (MappingException $e) {
                    if (!class_exists($value)) {
                        unset($ref[$key]);
                    }
                }

            }
            if (is_array($value)) {
                $this->checkCorpsReferences($value, $ref[$key], $name, $conn);
            }
        }
    }

    /**
     * @param ClassMetadata $meta
     * @param Connection $conn
     * @param int $id
     * @return string
     */
    protected function fmtQuery(ClassMetadata $meta, Connection $conn, $id)
    {
        return sprintf("SELECT COUNT(*) FROM `%s` WHERE `%s` = %s", $meta->getTableName(), $meta->getSingleIdentifierColumnName(), $conn->quote($id));
    }
}
