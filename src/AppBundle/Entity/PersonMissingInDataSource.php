<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entity PersonMissingInDataSource
 * @package AppBundle\Entity
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PersonMissingInDataSourceRepository")
 */
class PersonMissingInDataSource implements JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Person")
     * @ORM\JoinColumn(name="person_id", referencedColumnName="id", nullable=true)
     */
    private $person;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\PotentialIdentity")
     * @ORM\JoinColumn(name="potential_identity_id", referencedColumnName="id", nullable=true)
     */
    private $potentialIdentity;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\DataSource")
     * @ORM\JoinColumn(name="data_source_id", referencedColumnName="id", nullable=false)
     */
    private $dataSource;

    /**
     * @ORM\Column(type="string", length=1000, nullable=true)
     * @Assert\Length(max=1000)
     */
    private $description;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $hiddenTimestamp;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true)
     */
    private $hiddenByUser;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $created;

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return array(
            'person' => $this->getPerson(),
            'description' => $this->getDescription(),
        );
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
     * Set description
     *
     * @param string $description
     *
     * @return PersonMissingInDataSource
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set hiddenTimestamp
     *
     * @param \DateTime $hiddenTimestamp
     *
     * @return PersonMissingInDataSource
     */
    public function setHiddenTimestamp($hiddenTimestamp)
    {
        $this->hiddenTimestamp = $hiddenTimestamp;

        return $this;
    }

    /**
     * Get hiddenTimestamp
     *
     * @return \DateTime
     */
    public function getHiddenTimestamp()
    {
        return $this->hiddenTimestamp;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     *
     * @return PersonMissingInDataSource
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set person
     *
     * @param \AppBundle\Entity\Person $person
     *
     * @return PersonMissingInDataSource
     */
    public function setPerson(\AppBundle\Entity\Person $person)
    {
        $this->person = $person;

        return $this;
    }

    /**
     * Get person
     *
     * @return \AppBundle\Entity\Person
     */
    public function getPerson()
    {
        return $this->person;
    }

    /**
     * Set dataSource
     *
     * @param \AppBundle\Entity\DataSource $dataSource
     *
     * @return PersonMissingInDataSource
     */
    public function setDataSource(\AppBundle\Entity\DataSource $dataSource)
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
     * Set hiddenByUser
     *
     * @param \AppBundle\Entity\User $hiddenByUser
     *
     * @return PersonMissingInDataSource
     */
    public function setHiddenByUser(\AppBundle\Entity\User $hiddenByUser = null)
    {
        $this->hiddenByUser = $hiddenByUser;

        return $this;
    }

    /**
     * Get hiddenByUser
     *
     * @return \AppBundle\Entity\User
     */
    public function getHiddenByUser()
    {
        return $this->hiddenByUser;
    }

    /**
     * Set potentialIdentity
     *
     * @param \AppBundle\Entity\PotentialIdentity $potentialIdentity
     *
     * @return PersonMissingInDataSource
     */
    public function setPotentialIdentity(\AppBundle\Entity\PotentialIdentity $potentialIdentity = null)
    {
        $this->potentialIdentity = $potentialIdentity;

        return $this;
    }

    /**
     * Get potentialIdentity
     *
     * @return \AppBundle\Entity\PotentialIdentity
     */
    public function getPotentialIdentity()
    {
        return $this->potentialIdentity;
    }
}
