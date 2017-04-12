<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\Groups;

/**
 * Entity ImportKeyColumn
 * @package AppBundle\Entity
 * @ORM\Entity()
 */
class ImportKeyColumn implements JsonSerializable
{
    const DATA_GROUP = "dataImportKeyColumn";
    const ID_GROUP = "idImportKeyColumn";

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({ImportKeyColumn::ID_GROUP})
     */
    private $id;

    /**
     * @ORM\Column(type="string", nullable=false)
     * @Groups({ImportKeyColumn::DATA_GROUP})
     */
    private $name;

    /**
     * @ORM\Column(type="string", nullable=false)
     * @Groups({ImportKeyColumn::DATA_GROUP})
     */
    private $dqlName;

    /**
     * @ORM\Column(type="string", nullable=false)
     * @Groups({ImportKeyColumn::DATA_GROUP})
     */
    private $importObjectName;

    public function jsonSerialize()
    {
        return array(
            'name' => $this->name(),
            'dqlName' => $this->dqlName(),
            'importObjectName'=> $this->importObjectName(),
        );
    }

    //TODO change
    public function __toString()
    {
        return $this->name;
    }


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return ImportKeyColumn
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set dqlName
     *
     * @param string $dqlName
     *
     * @return ImportKeyColumn
     */
    public function setDqlName($dqlName)
    {
        $this->dqlName = $dqlName;

        return $this;
    }

    /**
     * Get dqlName
     *
     * @return string
     */
    public function getDqlName()
    {
        return $this->dqlName;
    }

    /**
     * Set importObjectName
     *
     * @param string $importObjectName
     *
     * @return ImportKeyColumn
     */
    public function setImportObjectName($importObjectName)
    {
        $this->importObjectName = $importObjectName;

        return $this;
    }

    /**
     * Get importObjectName
     *
     * @return string
     */
    public function getImportObjectName()
    {
        return $this->importObjectName;
    }
}
