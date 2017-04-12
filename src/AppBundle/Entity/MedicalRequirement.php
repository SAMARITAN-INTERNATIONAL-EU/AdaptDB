<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use JMS\Serializer\Annotation\Groups;

/**
 * Entity MedicalRequirement
 * @package AppBundle\Entity
 * @ORM\Entity()
 * @UniqueEntity("name")
 */
class MedicalRequirement implements \JsonSerializable
{
    const DATA_GROUP = "dataMedicalRequirement";
    const ID_GROUP = "idMedicalRequirement";

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({MedicalRequirement::ID_GROUP})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=false, unique=true)
     * @Assert\Length(max=100)
     * @Assert\NotBlank(message="This field cannot be empty.")
     * @Groups({MedicalRequirement::DATA_GROUP})
     */
    private $name;


    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }

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
     * Set name
     *
     * @param string $name
     *
     * @return MedicalRequirement
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
}
