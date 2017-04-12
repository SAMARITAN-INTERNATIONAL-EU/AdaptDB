<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Doctrine\ORM\Mapping\Table;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\Type;

/**
 * Entity PersonAddress
 * @package AppBundle\Entity
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PersonAddressRepository")
 * @Table(name="person_address", uniqueConstraints={@UniqueConstraint(name="person_address_unique", columns={"person_id", "address_id"})}))
 */
class PersonAddress implements \JsonSerializable
{
    const DATA_GROUP = "dataPersonAddress";
    const ID_GROUP = "idPersonAddress";
    const PERSON_ONLY_GROUP = "personPersonAddress";


    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({PersonAddress::ID_GROUP})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Person", inversedBy="personAddresses")
     * @ORM\JoinColumn(name="person_id", referencedColumnName="id")
     * @Assert\Valid
     * @Groups({PersonAddress::PERSON_ONLY_GROUP})
     */
    private $person;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Address")
     * @ORM\JoinColumn(name="address_id", referencedColumnName="id")
     * @Assert\Valid
     * @Groups({PersonAddress::DATA_GROUP})
     */
    private $address;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\Length(max=5)
     * @Groups({PersonAddress::DATA_GROUP})
     */
    private $floor;

    /**
     * @ORM\Column(type="string", length=1000, nullable=true)
     * @Assert\Length(max=1000)
     * @Groups({PersonAddress::DATA_GROUP})
     */
    private $remarks;

    /**
     * @ORM\Column(type="boolean")
     * @Groups({PersonAddress::DATA_GROUP})
     */
    private $isActive;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @Assert\Date()
     * @Type("DateTime<'Y-m-d'>")
     * @Groups({PersonAddress::DATA_GROUP})
     */
    private $absenceFrom;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @Assert\Date()
     * @Type("DateTime<'Y-m-d'>")
     * @Groups({PersonAddress::DATA_GROUP})
     */
    private $absenceTo;

    /**
     * Not persisted in the database
     */
    private $isInPolygon;

    /**
     * @return array
     */
    public function jsonSerialize()
    {

        return array(
            'person' => $this->getPerson(),
            'address' => $this->getAddress(),
            'floor' => $this->getFloor(),
            'remarks' => $this->getRemarks(),
            'isActive' => $this->getIsActive(),
            'absenceFrom' => $this->getAbsenceFrom(),
            'absenceTo' => $this->getAbsenceTo()
        );
    }

    public function __toString()
    {
        return "PersonAddress Id " .$this->getId();
    }


    /**
     * This function returns a concatenated string of the significant properties of an address.
     * The string is used to compare two personAddresses in the DetectInconsistentDataCommand
     * NOTE: This string is not intended to be displayed to the user because its not made to be human-readable
     *
     * @return string
     */
    public function getAddressDumpForComparison() {

        $address = $this->getAddress();
        $street = $address ? $address->getStreet() : null;
        $zipcode = $street ? $street->getZipcode() : null;
        $country = $zipcode ? $zipcode->getCountry() : null;

        $addressString = "";

        if ($address != null) {
            $addressString .= $address->getHouseNr();
        }

        if ($street != null) {
            $addressString .= $street->getName();
        }

        if ($zipcode != null) {
            $addressString .= $zipcode->getZipcode()."".$zipcode->getCity();
        }

        if ($country != null) {
            $addressString .= $country->getName();
        }

        return $addressString;

    }

    /**
     * This function generates a address string of the address
     * It is used by The PersonChangelistener to generate DataChangeHistory-Items
     * "---" is used to trigger linebreaks in the frontend
     *
     * @return string
     */
    public function getCompleteAddressString() {

        $addressString = "";
        if ($this->getAddress()->getStreet() != null) {
            $addressString .= $this->getAddress()->getStreet()->getName() . ' ' . $this->getAddress()->getHouseNr();

            if ($this->getAddress()->getStreet()->getZipcode() != null) {
                $addressString .= ' --- ' . $this->getAddress()->getStreet()->getZipcode()->getZipcode() . ' ' . $this->getAddress()->getStreet()->getZipcode()->getCity() . ', ' . $this->getAddress()->getStreet()->getZipcode()->getCountry()->getName();
            }
        }

        return $addressString;
    }

    /**
     * Set floor
     *
     * @param integer $floor
     *
     * @return PersonAddress
     */
    public function setFloor($floor)
    {
        $this->floor = $floor;

        return $this;
    }

    /**
     * Get floor
     *
     * @return integer
     */
    public function getFloor()
    {
        return $this->floor;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     *
     * @return PersonAddress
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Set isInPolygon
     *
     * @param boolean $isInPolygon
     *
     * @return PersonAddress
     */
    public function setIsInPolygon($isInPolygon)
    {
        $this->isInPolygon = $isInPolygon;

        return $this;
    }

    /**
     * Get isInPolygon
     *
     * @return boolean
     */
    public function getIsInPolygon()
    {
        return $this->isInPolygon;
    }

    /**
     * Set person
     *
     * @param \AppBundle\Entity\Person $person
     *
     * @return PersonAddress
     */
    public function setPerson(\AppBundle\Entity\Person $person=null)
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
     * Set address
     *
     * @param \AppBundle\Entity\Address $address
     *
     * @return PersonAddress
     */
    public function setAddress(\AppBundle\Entity\Address $address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return \AppBundle\Entity\Address
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set remarks
     *
     * @param string $remarks
     *
     * @return PersonAddress
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
     * Set absenceFrom
     *
     * @param \DateTime $absenceFrom
     *
     * @return PersonAddress
     */
    public function setAbsenceFrom($absenceFrom)
    {
        $this->absenceFrom = $absenceFrom;

        return $this;
    }

    /**
     * Get absenceFrom
     *
     * @return \DateTime
     */
    public function getAbsenceFrom()
    {
        return $this->absenceFrom;
    }

    /**
     * Set absenceTo
     *
     * @param \DateTime $absenceTo
     *
     * @return PersonAddress
     */
    public function setAbsenceTo($absenceTo)
    {
        $this->absenceTo = $absenceTo;

        return $this;
    }

    /**
     * Get absenceTo
     *
     * @return \DateTime
     */
    public function getAbsenceTo()
    {
        return $this->absenceTo;
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
}
