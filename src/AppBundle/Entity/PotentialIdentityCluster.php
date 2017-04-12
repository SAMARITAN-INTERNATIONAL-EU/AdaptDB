<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Entity PotentialIdentityCluster
 * @package AppBundle\Entity
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PotentialIdentityClusterRepository")
 */
class PotentialIdentityCluster implements \JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Person")
     * @ORM\OrderBy({"id" = "ASC"})
     * @ORM\JoinColumn(name="person", referencedColumnName="id")
     */
    private $persons;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $source;

    /**
     * @ORM\Column(type="boolean")
     */
    private $wasCreated;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $timestampModified;

    /**
     * @return array
     */
    public function jsonSerialize()
    {

        return array(
            'PotentialIdentityClusterId' => $this->getId(),
        );
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->persons = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set source
     *
     * @param string $source
     *
     * @return PotentialIdentityCluster
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set wasCreated
     *
     * @param boolean $wasCreated
     *
     * @return PotentialIdentityCluster
     */
    public function setWasCreated($wasCreated)
    {
        $this->wasCreated = $wasCreated;

        return $this;
    }

    /**
     * Get wasCreated
     *
     * @return boolean
     */
    public function getWasCreated()
    {
        return $this->wasCreated;
    }

    /**
     * Set timestampModified
     *
     * @param \DateTime $timestampModified
     *
     * @return PotentialIdentityCluster
     */
    public function setTimestampModified($timestampModified)
    {
        $this->timestampModified = $timestampModified;

        return $this;
    }

    /**
     * Get timestampModified
     *
     * @return \DateTime
     */
    public function getTimestampModified()
    {
        return $this->timestampModified;
    }

    /**
     * Add person
     *
     * @param \AppBundle\Entity\Person $person
     *
     * @return PotentialIdentityCluster
     */
    public function addPerson(\AppBundle\Entity\Person $person)
    {
        $this->persons[] = $person;

        return $this;
    }

    /**
     * Remove person
     *
     * @param \AppBundle\Entity\Person $person
     */
    public function removePerson(\AppBundle\Entity\Person $person)
    {
        $this->persons->removeElement($person);
    }

    /**
     * Get persons
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPersons()
    {
        return $this->persons;
    }
}
