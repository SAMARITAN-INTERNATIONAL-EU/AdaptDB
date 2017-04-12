<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\Groups;

/**
 * Entity ImportWarning
 * @package AppBundle\Entity
 * @ORM\Entity()
 */
class ImportWarning implements JsonSerializable
{
    const DATA_GROUP = "dataImportWarning";
    const ID_GROUP = "idImportWarning";

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({ImportWarning::ID_GROUP})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=1000, nullable=true)
     * @Assert\Length(max=1000)
     * @Groups({ImportWarning::DATA_GROUP})
     */
    private $message;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Import")
     * @ORM\JoinColumn(name="import_id", referencedColumnName="id")
     * @Assert\Valid
     * @Groups({ImportWarning::DATA_GROUP})
     */
    private $import;

    /**
     * @ORM\Column(type="boolean")
     * @Groups({ImportWarning::DATA_GROUP})
     */
    private $markedAsDone = false;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Person")
     * @ORM\JoinColumn(name="person_id", referencedColumnName="id")
     * @Assert\Valid
     * @Groups({ImportWarning::DATA_GROUP})
     */
    private $person;

    public function __construct($import, $message, $person) {
        $this->setImport($import);
        $this->setMessage($message);
        $this->setPerson($person);
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return array(
            'message' => $this->message,
        );
    }

    //TODO change
    public function __toString()
    {
        return "ImportWarning:" . $this->message;
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
     * Set message
     *
     * @param string $message
     *
     * @return ImportWarning
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set import
     *
     * @param \AppBundle\Entity\Import $import
     *
     * @return ImportWarning
     */
    public function setImport(\AppBundle\Entity\Import $import = null)
    {
        $this->import = $import;

        return $this;
    }

    /**
     * Get import
     *
     * @return \AppBundle\Entity\Import
     */
    public function getImport()
    {
        return $this->import;
    }

    /**
     * Set person
     *
     * @param \AppBundle\Entity\Person $person
     *
     * @return ImportWarning
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

    /**
     * Set markedAsDone
     *
     * @param boolean $markedAsDone
     *
     * @return ImportWarning
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
}
