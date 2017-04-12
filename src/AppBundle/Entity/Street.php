<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ManyToMany;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\Groups;

/**
 * Entity Street
 * @package AppBundle\Entity
 * @ORM\Entity(repositoryClass="AppBundle\Repository\StreetRepository")

 */
class Street implements \JsonSerializable
{
    const DATA_GROUP = "dataStreet";
    const ID_GROUP = "idStreet";
    const ZIPCODE_ONLY_GROUP = "zipcodeStreet";

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({Street::ID_GROUP})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Assert\Length(max=255)
     * @Assert\NotBlank(message="This field cannot be empty.")
     * @Groups({Street::DATA_GROUP})
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Assert\Length(max=255)
     */
    private $nameNormalized = "";

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Zipcode")
     * @ORM\JoinColumn(name="zipcode_id", referencedColumnName="id")
     * @Assert\Valid
     * @Groups({Street::ZIPCODE_ONLY_GROUP})
     */
    private $zipcode;
    
    /**
     * @ManyToMany(targetEntity="AppBundle\Entity\Emergency", mappedBy="streets")
     */
    private $emergencies;


    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return array(
            'name' => $this->getName(),
            'zipcode' => $this->getZipcode(),
        );
    }

    public function __toString()
    {
        return $this->getName();
    }
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->emergencies = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return Street
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
     * Set nameNormalized
     *
     * @param string $nameNormalized
     *
     * @return Street
     */
    public function setNameNormalized($nameNormalized)
    {
        $this->nameNormalized = $nameNormalized;

        return $this;
    }

    /**
     * Get nameNormalized
     *
     * @return string
     */
    public function getNameNormalized()
    {
        return $this->nameNormalized;
    }

    /**
     * Set zipcode
     *
     * @param \AppBundle\Entity\Zipcode $zipcode
     *
     * @return Street
     */
    public function setZipcode(\AppBundle\Entity\Zipcode $zipcode = null)
    {
        $this->zipcode = $zipcode;

        return $this;
    }

    /**
     * Get zipcode
     *
     * @return \AppBundle\Entity\Zipcode
     */
    public function getZipcode()
    {
        return $this->zipcode;
    }

    /**
     * Add emergency
     *
     * @param \AppBundle\Entity\Emergency $emergency
     *
     * @return Street
     */
    public function addEmergency(\AppBundle\Entity\Emergency $emergency)
    {
        $this->emergencies[] = $emergency;

        return $this;
    }

    /**
     * Remove emergency
     *
     * @param \AppBundle\Entity\Emergency $emergency
     */
    public function removeEmergency(\AppBundle\Entity\Emergency $emergency)
    {
        $this->emergencies->removeElement($emergency);
    }

    /**
     * Get emergencies
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEmergencies()
    {
        return $this->emergencies;
    }
}
