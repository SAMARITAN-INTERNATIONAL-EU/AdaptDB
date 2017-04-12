<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OneToMany;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;

/**
 * Entity Person
 * @package AppBundle\Entity
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PersonRepository")
 */
class Person implements \JsonSerializable
{
    const BASIC_DATA_GROUP = "basicDataPerson";
    const SAFETY_STATUS_GROUP = "safetyStatusPerson";
    const DATA_GROUP = "dataPerson";
    const ID_GROUP = "idPerson";

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({Person::ID_GROUP})
     */
    private $id;

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
     * @ORM\Column(type="date", nullable=true)
     * @Assert\Date()
     * @Type("DateTime<'Y-m-d'>")
     * @Groups({Person::DATA_GROUP})
     */
    private $validUntil;

    /**
     * @ORM\Column(type="string", length=100, nullable=false)
     * @Assert\Length(max=100)
     * @Assert\NotBlank(message="This field cannot be empty.")
     * @Groups({Person::DATA_GROUP, Person::BASIC_DATA_GROUP})
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", length=100, nullable=false)
     * @Assert\Length(max=100)
     */
    private $firstNameNormalized = "";

    /**
     * @ORM\Column(type="string", length=100, nullable=false)
     * @Assert\Length(max=100)
     * @Assert\NotBlank(message="This field cannot be empty.")
     * @Groups({Person::DATA_GROUP, Person::BASIC_DATA_GROUP})
     */
    private $lastName;

    /**
     * @ORM\Column(type="string", length=100, nullable=false)
     * @Assert\Length(max=100)
     */
    private $lastNameNormalized = "";

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Assert\Length(max=50)
     * @Groups({Person::DATA_GROUP, Person::BASIC_DATA_GROUP})
     */
    private $fiscalCode;

    /**
     * @ORM\Column(type="string", length=150, nullable=true)
     * @Assert\Length(max=150)
     * @Assert\Email
     * @Groups({Person::DATA_GROUP})
     */
    private $email;

    /**
     * Not persisted in the database - Calculated on the fly
     */
    private $age;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @Assert\Date()
     * @Type("DateTime<'Y-m-d'>")
     * @Groups({Person::DATA_GROUP, Person::BASIC_DATA_GROUP})
     */
    private $dateOfBirth;

    /**
     * @ORM\Column(type="string", length=80, nullable=true)
     * @Assert\Length(max=80)
     * @Groups({Person::DATA_GROUP})
     */
    private $landlinePhone;

    /**
     * @ORM\Column(type="string", length=80, nullable=true)
     * @Assert\Length(max=80)
     * @Groups({Person::DATA_GROUP})
     */
    private $cellPhone;

    /**
     * @ORM\Column(type="boolean")
     * @Groups({Person::DATA_GROUP})
     */
    private $genderMale;

    /**
     * @ORM\Column(type="string", length=1000, nullable=true)
     * @Assert\Length(max=1000)
     * @Groups({Person::DATA_GROUP})
     */
    private $remarks;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\MedicalRequirement")
     * @ORM\OrderBy({"name" = "ASC"})
     * @ORM\JoinColumn(name="medicalRequirement", referencedColumnName="id")
     * @Groups({Person::DATA_GROUP})
     */
    private $medicalRequirements;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\TransportRequirement")
     * @ORM\OrderBy({"name" = "ASC"})
     * @ORM\JoinColumn(name="transportRequirement", referencedColumnName="id")
     * @Groups({Person::DATA_GROUP})
     */
    private $transportRequirements;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\VulnerabilityLevel")
     * @ORM\JoinColumn(name="vulnerability_level_id", referencedColumnName="id", nullable=false)
     * @Groups({Person::DATA_GROUP})
     */
    private $vulnerabilityLevel;

    /**
     * @OneToMany(targetEntity="ContactPerson", mappedBy="person")
     * @Assert\Valid
     * @Groups({Person::DATA_GROUP})
     */
    private $contactPersons;

    /**
     * @OneToMany(targetEntity="PersonAddress", mappedBy="person")
     * @Groups({Person::DATA_GROUP})
     */
    private $personAddresses;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\PotentialIdentity", inversedBy="persons")
     * @ORM\JoinColumn(name="potential_identity_id", referencedColumnName="id")
     * @Groups({Person::DATA_GROUP})
     */
    private $potentialIdentity;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\DataSource")
     * @ORM\JoinColumn(name="datasource_id", referencedColumnName="id")
     * @Groups({Person::DATA_GROUP, Person::BASIC_DATA_GROUP})
     */
    private $dataSource;

    public function __toString() {
        return $this->firstName . ' ' .  $this->lastName;
    }

    /**
     * This variable puts out the first of $emergencySafetyStatuses
     * If a emergencyId was given this field is added to the json-output
     * @Groups({Person::SAFETY_STATUS_GROUP})
     * @VirtualProperty
     * @SerializedName("safety_status")
     */
    public function getSafetyStatus() {
        return $this->emergencySafetyStatuses[0]->getSafetyStatus();
    }

    /**
     * @OneToMany(targetEntity="AppBundle\Entity\EmergencyPersonSafetyStatus", mappedBy="person")
     */
    private $emergencySafetyStatuses;

    /**
     * @return array
     */
    public function jsonSerialize()
    {

        $medReqString = null;
        $medReqArray = $this->getMedicalRequirements();

        if (count($medReqArray) == 0) {
            $medReqString = "none";
        } else {

            foreach ($medReqArray as $medReq) {

                if ($medReqString == null) {
                    $medReqString = "";
                } else {
                    $medReqString = $medReqString  . ", ";
                }

                $medReqString = $medReqString . $medReq->getName();
            }
        }

        $transportReqString = null;
        $transportReqArray = $this->getTransportRequirements();

        if (count($transportReqArray) == 0) {
            $transportReqString = "none";
        } else {

            foreach ($transportReqArray as $transportReq) {

                if ($transportReqString == null) {
                    $transportReqString = "";
                } else {
                    $transportReqString = $transportReqString  . ", ";
                }

                $transportReqString = $transportReqString . $transportReq->getName();
            }
        }

        if ($this->getVulnerabilityLevel() != null) {
            $vulnerabilityLevelString = $this->getVulnerabilityLevel()->getName();
        } else {
            $vulnerabilityLevelString = "";
        }


        $dateOfBirthString = "";
        if ($this->getDateOfBirth()) {
            $dateOfBirthString = $this->getDateOfBirth()->format("d-m-Y");
        }

        return array(
            'id' => $this->getId(),
            'firstName' => $this->getFirstName(),
            'lastName' => $this->getLastName(),
            'fiscalCode' => $this->getFiscalCode(),
            'age' => $this->getAge(),
            'dateOfBirth' => $dateOfBirthString,
            'medicalRequirements' => $medReqString,
            'transportRequirements' => $transportReqString,
            'vulnerabilityLevel' => $vulnerabilityLevelString,
        );
    }


    /**
     * Set firstName
     *
     * @param string $firstName
     *
     * @return Person
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

    /**
     * Set lastName
     *
     * @param string $lastName
     *
     * @return Person
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
     * Set age
     *
     * @param integer $age
     *
     * @return Person
     */
    public function setAge($age)
    {
        //to nothing
    }

    /**
     * Get age
     *
     * @return integer
     */
    public function getAge()
    {
        if ($this->getDateOfBirth() != null) {

            $birthDate = explode("/", $this->dateOfBirth->format('m/d/Y'));
            //Format 12/17/1983

            if ($birthDate != null) {

                //get age from date or birthdate
                return (date("md", date("U", mktime(0, 0, 0, $birthDate[0], $birthDate[1], $birthDate[2]))) > date("md")
                    ? ((date("Y") - $birthDate[2]) - 1)
                    : (date("Y") - $birthDate[2]));
            }
        }
        return null;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->medicalRequirements = new \Doctrine\Common\Collections\ArrayCollection();
        $this->transportRequirements = new \Doctrine\Common\Collections\ArrayCollection();
        $this->contactPersons = new \Doctrine\Common\Collections\ArrayCollection();
        $this->personAddresses = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set validUntil
     *
     * @param \DateTime $validUntil
     *
     * @return Person
     */
    public function setValidUntil($validUntil)
    {
        $this->validUntil = $validUntil;

        return $this;
    }

    /**
     * Get validUntil
     *
     * @return \DateTime
     */
    public function getValidUntil()
    {
        return $this->validUntil;
    }

    /**
     * Set fiscalCode
     *
     * @param string $fiscalCode
     *
     * @return Person
     */
    public function setFiscalCode($fiscalCode)
    {
        $this->fiscalCode = $fiscalCode;

        return $this;
    }

    /**
     * Get fiscalCode
     *
     * @return string
     */
    public function getFiscalCode()
    {
        return $this->fiscalCode;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return Person
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set dateOfBirth
     *
     * @param \DateTime $dateOfBirth
     *
     * @return Person
     */
    public function setDateOfBirth($dateOfBirth)
    {
        $this->dateOfBirth = $dateOfBirth;

        return $this;
    }

    /**
     * Get dateOfBirth
     *
     * @return \DateTime
     */
    public function getDateOfBirth()
    {
        return $this->dateOfBirth;
    }

    /**
     * Set cellPhone
     *
     * @param string $cellPhone
     *
     * @return Person
     */
    public function setCellPhone($cellPhone)
    {
        $this->cellPhone = $cellPhone;

        return $this;
    }

    /**
     * Get cellPhone
     *
     * @return string
     */
    public function getCellPhone()
    {
        return $this->cellPhone;
    }

    /**
     * Set genderMale
     *
     * @param boolean $genderMale
     *
     * @return Person
     */
    public function setGenderMale($genderMale)
    {
        $this->genderMale = $genderMale;

        return $this;
    }

    /**
     * Get genderMale
     *
     * @return boolean
     */
    public function getGenderMale()
    {
        return $this->genderMale;
    }

    /**
     * Set remarks
     *
     * @param string $remarks
     *
     * @return Person
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
     * Set vulnerabilityLevel
     *
     * @param \AppBundle\Entity\VulnerabilityLevel $vulnerabilityLevel
     *
     * @return Person
     */
    public function setVulnerabilityLevel(\AppBundle\Entity\VulnerabilityLevel $vulnerabilityLevel = null)
    {
        $this->vulnerabilityLevel = $vulnerabilityLevel;

        return $this;
    }

    /**
     * Get vulnerabilityLevel
     *
     * @return \AppBundle\Entity\VulnerabilityLevel
     */
    public function getVulnerabilityLevel()
    {
        return $this->vulnerabilityLevel;
    }

    /**
     * Add contactPerson
     *
     * @param \AppBundle\Entity\ContactPerson $contactPerson
     *
     * @return Person
     */
    public function addContactPerson(\AppBundle\Entity\ContactPerson $contactPerson)
    {
        $this->contactPersons[] = $contactPerson;

        return $this;
    }

    /**
     * Remove contactPerson
     *
     * @param \AppBundle\Entity\ContactPerson $contactPerson
     */
    public function removeContactPerson(\AppBundle\Entity\ContactPerson $contactPerson)
    {
        $this->contactPersons->removeElement($contactPerson);
    }

    /**
     * Get contactPersons
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getContactPersons()
    {
        return $this->contactPersons;
    }

    /**
     * Add personAddress
     *
     * @param \AppBundle\Entity\PersonAddress $personAddress
     *
     * @return Person
     */
    public function addPersonAddress(\AppBundle\Entity\PersonAddress $personAddress)
    {
        $this->personAddresses[] = $personAddress;

        return $this;
    }

    /**
     * Remove personAddress
     *
     * @param \AppBundle\Entity\PersonAddress $personAddress
     */
    public function removePersonAddress(\AppBundle\Entity\PersonAddress $personAddress)
    {
        $this->personAddresses->removeElement($personAddress);
    }

    /**
     * Get personAddresses
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPersonAddresses()
    {
        return $this->personAddresses;
    }

    /**
     * Set potentialIdentity
     *
     * @param \AppBundle\Entity\PotentialIdentity $potentialIdentity
     *
     * @return Person
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

    /**
     * Set dataSource
     *
     * @param \AppBundle\Entity\DataSource $dataSource
     *
     * @return Person
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
     * Set landlinePhone
     *
     * @param string $landlinePhone
     *
     * @return Person
     */
    public function setLandlinePhone($landlinePhone)
    {
        $this->landlinePhone = $landlinePhone;

        return $this;
    }

    /**
     * Get landlinePhone
     *
     * @return string
     */
    public function getLandlinePhone()
    {
        return $this->landlinePhone;
    }

    /**
     * Add emergencySafetyStatus
     *
     * @param \AppBundle\Entity\EmergencyPersonSafetyStatus $emergencySafetyStatus
     *
     * @return Person
     */
    public function addEmergencySafetyStatus(\AppBundle\Entity\EmergencyPersonSafetyStatus $emergencySafetyStatus)
    {
        $this->emergencySafetyStatuses[] = $emergencySafetyStatus;

        return $this;
    }

    /**
     * Remove emergencySafetyStatus
     *
     * @param \AppBundle\Entity\EmergencyPersonSafetyStatus $emergencySafetyStatus
     */
    public function removeEmergencySafetyStatus(\AppBundle\Entity\EmergencyPersonSafetyStatus $emergencySafetyStatus)
    {
        $this->emergencySafetyStatuses->removeElement($emergencySafetyStatus);
    }

    /**
     * Get emergencySafetyStatuses
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEmergencySafetyStatuses()
    {
        return $this->emergencySafetyStatuses;
    }

    /**
     * Set firstNameNormalized
     *
     * @param string $firstNameNormalized
     *
     * @return Person
     */
    public function setFirstNameNormalized($firstNameNormalized)
    {
        $this->firstNameNormalized = $firstNameNormalized;

        return $this;
    }

    /**
     * Get firstNameNormalized
     *
     * @return string
     */
    public function getFirstNameNormalized()
    {
        return $this->firstNameNormalized;
    }

    /**
     * Set lastNameNormalized
     *
     * @param string $lastNameNormalized
     *
     * @return Person
     */
    public function setLastNameNormalized($lastNameNormalized)
    {
        $this->lastNameNormalized = $lastNameNormalized;

        return $this;
    }

    /**
     * Get lastNameNormalized
     *
     * @return string
     */
    public function getLastNameNormalized()
    {
        return $this->lastNameNormalized;
    }

    /**
     * Add medicalRequirement
     *
     * @param \AppBundle\Entity\MedicalRequirement $medicalRequirement
     *
     * @return Person
     */
    public function addMedicalRequirement(\AppBundle\Entity\MedicalRequirement $medicalRequirement)
    {
        $this->medicalRequirements[] = $medicalRequirement;

        return $this;
    }

    /**
     * Remove medicalRequirement
     *
     * @param \AppBundle\Entity\MedicalRequirement $medicalRequirement
     */
    public function removeMedicalRequirement(\AppBundle\Entity\MedicalRequirement $medicalRequirement)
    {
        $this->medicalRequirements->removeElement($medicalRequirement);
    }

    /**
     * Get medicalRequirements
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMedicalRequirements()
    {
        return $this->medicalRequirements;
    }

    /**
     * Add transportRequirement
     *
     * @param \AppBundle\Entity\TransportRequirement $transportRequirement
     *
     * @return Person
     */
    public function addTransportRequirement(\AppBundle\Entity\TransportRequirement $transportRequirement)
    {
        $this->transportRequirements[] = $transportRequirement;

        return $this;
    }

    /**
     * Remove transportRequirement
     *
     * @param \AppBundle\Entity\TransportRequirement $transportRequirement
     */
    public function removeTransportRequirement(\AppBundle\Entity\TransportRequirement $transportRequirement)
    {
        $this->transportRequirements->removeElement($transportRequirement);
    }

    /**
     * Get transportRequirements
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTransportRequirements()
    {
        return $this->transportRequirements;
    }
}
