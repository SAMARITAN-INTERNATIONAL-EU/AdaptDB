<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\Groups;

/**
 * Entity ContactPerson
 * @package AppBundle\Entity
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PersonRepository")
 */
class ContactPerson implements \JsonSerializable
{

    const DATA_GROUP = "dataContactPerson";
    const ID_GROUP = "idContactPerson";

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({ContactPerson::ID_GROUP})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=150, nullable=false)
     * @Assert\Length(max=150)
     * @Assert\NotBlank(message="This field cannot be empty.")
     * @Groups({ContactPerson::DATA_GROUP})
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", length=150, nullable=false)
     * @Assert\Length(max=150)
     * @Assert\NotBlank(message="This field cannot be empty.")
     * @Groups({ContactPerson::DATA_GROUP})
     */
    private $lastName;

    /**
     * @ORM\Column(type="string", length=1000, nullable=true)
     * @Assert\Length(max=1000)
     * @Groups({ContactPerson::DATA_GROUP})
     */
    private $remarks;

    /**
     * @ORM\Column(type="string", length=150, nullable=true)
     * @Assert\Length(max=150)
     * @Groups({ContactPerson::DATA_GROUP})
     */
    private $phone;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Person", inversedBy="contactPersons")
     * @ORM\JoinColumn(name="person_id", referencedColumnName="id")
     */
    private $person;

    /**
     * @return array
     */
    public function jsonSerialize()
    {

        return array(
            'firstname' => $this->getFirstName(),
            'lastname' => $this->getLastName(),
            'remarks' => $this->getRemarks(),
            'phone' => $this->getPhone(),
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
     * Set firstName
     *
     * @param string $firstName
     *
     * @return ContactPerson
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get firstName
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

//    /**
//     * Get firstName
//     *
//     * @return string
//     */
//    public function getFullname()
//    {
//        return $this->firstName . ' ' . $this->lastName;
//    }

    /**
     * Set lastName
     *
     * @param string $lastName
     *
     * @return ContactPerson
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get lastName
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Set remarks
     *
     * @param string $remarks
     *
     * @return ContactPerson
     */
    public function setRemarks($remarks)
    {
        $this->remarks = $remarks;

        return $this;
    }

    /**
     * Get remarks
     *
     * @return string
     */
    public function getRemarks()
    {
        return $this->remarks;
    }
    
    /**
     * Set phone
     *
     * @param string $phone
     *
     * @return ContactPerson
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set person
     *
     * @param \AppBundle\Entity\Person $person
     *
     * @return ContactPerson
     */
    public function setPerson(\AppBundle\Entity\Person $person = null)
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
}
