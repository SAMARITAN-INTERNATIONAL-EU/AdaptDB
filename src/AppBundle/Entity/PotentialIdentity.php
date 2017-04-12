<?php
namespace AppBundle\Entity;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OneToMany;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\Groups;

/**
 * Entity PotentialIdentity
 * @package AppBundle\Entity
 * @ORM\Entity()
 */
class PotentialIdentity implements \JsonSerializable
{
    const DATA_GROUP = "dataPotentialIdentity";
    const ID_GROUP = "idPotentialIdentity";

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({PotentialIdentity::ID_GROUP})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Groups({PotentialIdentity::DATA_GROUP})
     */
    private $name;

    /**
     * Will be used by show_potential_identity
     * @Groups({PotentialIdentity::DATA_GROUP})
     */
    private $isConfirmed = null;

    /**
     * @OneToMany(targetEntity="Person", mappedBy="potentialIdentity")
     * @Groups({PotentialIdentity::DATA_GROUP})
     */
    private $persons;

    public function __toString()
    {
       return $this->getName();
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {

        //TODO change
        return array(
            'name' => $this->getName(),

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
     * Set name
     *
     * @param string $name
     *
     * @return PotentialIdentity
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
     * Set isConfirmed
     *
     * @param boolean $isConfirmed
     *
     * @return PotentialIdentity
     */
    public function setIsConfirmed($isConfirmed)
    {
        $this->isConfirmed = $isConfirmed;

        return $this;
    }

    /**
     * Is Confirmed true if an entry in ManuallyChangedPotentialIdentity exists for this
     */
    public function getIsConfirmed() {
        return $this->isConfirmed;
    }

    /**
     * Add person
     *
     * @param \AppBundle\Entity\Person $person
     *
     * @return PotentialIdentity
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
