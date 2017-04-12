<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\Groups;

/**
 * Entity Import
 * @package AppBundle\Entity
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ImportRepository")
 */
class Import implements JsonSerializable
{
    const DATA_GROUP = "dataImport";
    const ID_GROUP = "idImport";

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({Import::ID_GROUP})
     */
    private $id;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $timestamp;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({Import::DATA_GROUP})
     */
    private $numberOfWarnings;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max=255)
     * @Groups({Import::DATA_GROUP})
     */
    private $filename;

    /**
     * @ORM\Column(type="boolean")
     * @Groups({Import::DATA_GROUP})
     */
    private $markedAsDone = false;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\DataSource")
     * @ORM\JoinColumn(name="datasource_id", referencedColumnName="id")
     * @Assert\Valid
     * @Groups({Import::DATA_GROUP})
     */
    private $dataSource;

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return array(
            'pos' => $this->id,
        );
    }

    //TODO change
    public function __toString()
    {
        return "GeoPoint Id" . $this->getId();
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
     * Set timestamp
     *
     * @param \DateTime $timestamp
     *
     * @return Import
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * Get timestamp
     *
     * @return \DateTime
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Set dataSource
     *
     * @param \AppBundle\Entity\DataSource $dataSource
     *
     * @return Import
     */
    public function setDataSource(\AppBundle\Entity\DataSource $dataSource = null)
    {
        $this->dataSource = $dataSource;

        return $this;
    }

    /**
     * Get dataSource
     *
     * @return \AppBundle\Entity\DataSource
     */
    public function getDataSource()
    {
        return $this->dataSource;
    }

    /**
     * Set filename
     *
     * @param string $filename
     *
     * @return Import
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Get filename
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Set markedAsDone
     *
     * @param boolean $markedAsDone
     *
     * @return Import
     */
    public function setMarkedAsDone($markedAsDone)
    {
        $this->markedAsDone = $markedAsDone;

        return $this;
    }

    /**
     * Get markedAsDone
     *
     * @return boolean
     */
    public function getMarkedAsDone()
    {
        return $this->markedAsDone;
    }

    /**
     * Set numberOfWarnings
     *
     * @param integer $numberOfWarnings
     *
     * @return Import
     */
    public function setNumberOfWarnings($numberOfWarnings)
    {
        $this->numberOfWarnings = $numberOfWarnings;

        return $this;
    }

    /**
     * Get numberOfWarnings
     *
     * @return integer
     */
    public function getNumberOfWarnings()
    {
        return $this->numberOfWarnings;
    }
}
