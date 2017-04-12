<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping\MappedSuperclass;

/**
 * Entity DataChangeHistory
 * @package AppBundle\Entity
 * @ORM\Entity(repositoryClass="AppBundle\Repository\DataChangeHistoryRepository")
 */
class DataChangeHistory implements \JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $changedByUser;

    /**
     * @ORM\Column(type="string", length=60, nullable=false)
     */
    protected $property;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $oldValue;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $newValue;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $timestamp;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Person")
     * @ORM\JoinColumn(name="person_id", referencedColumnName="id")
     */
    private $person;

    /**
     * @ORM\Column(type="boolean")
     */
    private $sendEmailCronjobDone = 0;

    public function __toString()
    {
        return $this->getProperty();
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return array(
            'name' => $this->getName(),

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
     * Set property
     *
     * @param string $property
     *
     * @return DataChangeHistory
     */
    public function setProperty($property)
    {
        $this->property = $property;

        return $this;
    }

    /**
     * Get property
     *
     * @return string
     */
    public function getProperty()
    {
        return $this->property;
    }

    /**
     * Set oldValue
     *
     * @param string $oldValue
     *
     * @return DataChangeHistory
     */
    public function setOldValue($oldValue)
    {
        $this->oldValue = $oldValue;

        return $this;
    }

    /**
     * Get oldValue
     *
     * @return string
     */
    public function getOldValue()
    {
        return $this->oldValue;
    }

    /**
     * Set newValue
     *
     * @param string $newValue
     *
     * @return DataChangeHistory
     */
    public function setNewValue($newValue)
    {
        $this->newValue = $newValue;

        return $this;
    }

    /**
     * Get newValue
     *
     * @return string
     */
    public function getNewValue()
    {
        return $this->newValue;
    }

    /**
     * Set timestamp
     *
     * @param \DateTime $timestamp
     *
     * @return DataChangeHistory
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
     * Set sendEmailCronjobDone
     *
     * @param boolean $sendEmailCronjobDone
     *
     * @return DataChangeHistory
     */
    public function setSendEmailCronjobDone($sendEmailCronjobDone)
    {
        $this->sendEmailCronjobDone = $sendEmailCronjobDone;

        return $this;
    }

    /**
     * Get sendEmailCronjobDone
     *
     * @return boolean
     */
    public function getSendEmailCronjobDone()
    {
        return $this->sendEmailCronjobDone;
    }

    /**
     * Set changedByUser
     *
     * @param \AppBundle\Entity\User $changedByUser
     *
     * @return DataChangeHistory
     */
    public function setChangedByUser(\AppBundle\Entity\User $changedByUser = null)
    {
        $this->changedByUser = $changedByUser;

        return $this;
    }

    /**
     * Get changedByUser
     *
     * @return \AppBundle\Entity\User
     */
    public function getChangedByUser()
    {
        return $this->changedByUser;
    }

    /**
     * Set person
     *
     * @param \AppBundle\Entity\Person $person
     *
     * @return DataChangeHistory
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
