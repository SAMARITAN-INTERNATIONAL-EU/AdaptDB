<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Entity InconsistentPI
 * @package AppBundle\Entity
 * @ORM\Entity(repositoryClass="AppBundle\Repository\InconsistentPIRepository")
 * @UniqueEntity(fields="$potentialIdentity")
 */
class InconsistentPI implements JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\PotentialIdentity")
     * @ORM\JoinColumn(name="potential_identity_id", referencedColumnName="id")
     */
    private $potentialIdentity;

    /**
     * @ORM\Column(type="string", length=1000, nullable=false)
     * @Assert\Length(max=1000)
     */
    private $description;

    /**
     * THIS IS NOT USED JET!
     * @ORM\Column(type="boolean")
     */
    private $hidden;

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
            'potentialIdentity' => $this->getPotentialIdentitiy(),
            'description' => $this->getDescription(),
        );
    }

    //TODO change
    public function __toString()
    {
        return "potentialIdentity Id" . $this->getPotentialIdentity()->getId();
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
     * @return InconsistentPI
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
     * Set hidden
     *
     * @param boolean $hidden
     *
     * @return InconsistentPI
     */
    public function setHidden($hidden)
    {
        $this->hidden = $hidden;

        return $this;
    }

    /**
     * Get hidden
     *
     * @return boolean
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     *
     * @return InconsistentPI
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
     * Set potentialIdentity
     *
     * @param \AppBundle\Entity\PotentialIdentity $potentialIdentity
     *
     * @return InconsistentPI
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
