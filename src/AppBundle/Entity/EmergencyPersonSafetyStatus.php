<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Entity EmergencyPersonSafetyStatus
 * @package AppBundle\Entity
 * @ORM\Entity(repositoryClass="AppBundle\Repository\EmergencyPersonSafetyStatusRepository")
 * @UniqueEntity(fields={"emergency", "person"})
 */
class EmergencyPersonSafetyStatus implements \JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Person", inversedBy="emergencySafetyStatuses")
     * @ORM\JoinColumn(name="person_id", referencedColumnName="id")
     */
    private $person;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Emergency")
     * @ORM\JoinColumn(name="emergency_id", referencedColumnName="id")
     */
    private $emergency;

    /**
     * @ORM\Column(type="boolean")
     */
    private $safetyStatus;

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return array(
            'person' => $this->getPerson(),
            'emergency' => $this->getEmergency(),
        );
    }

    /**
     * Set safetyStatus
     *
     * @param boolean $safetyStatus
     *
     * @return EmergencyPersonSafetyStatus
     */
    public function setSafetyStatus($safetyStatus)
    {
        $this->safetyStatus = $safetyStatus;

        return $this;
    }

    /**
     * Get safetyStatus
     *
     * @return boolean
     */
    public function getSafetyStatus()
    {
        return $this->safetyStatus;
    }

    /**
     * Set person
     *
     * @param \AppBundle\Entity\Person $person
     *
     * @return EmergencyPersonSafetyStatus
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
     * Set emergency
     *
     * @param \AppBundle\Entity\Emergency $emergency
     *
     * @return EmergencyPersonSafetyStatus
     */
    public function setEmergency(\AppBundle\Entity\Emergency $emergency)
    {
        $this->emergency = $emergency;

        return $this;
    }

    /**
     * Get emergency
     *
     * @return \AppBundle\Entity\Emergency
     */
    public function getEmergency()
    {
        return $this->emergency;
    }
}
