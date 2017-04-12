<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ManyToMany;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\Groups;

/**
 * Entity Emergency
 * @package AppBundle\Entity
 * @ORM\Entity()

 */
class Emergency
{
    const DATA_GROUP = "dataEmergency";
    const ID_GROUP = "idEmergency";

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({Emergency::ID_GROUP})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Assert\Length(max=100)
     * @Groups({Emergency::DATA_GROUP})
     */
    private $title;

    /**
     * @ORM\OneToMany(targetEntity="GeoArea", mappedBy="emergency")
     * @Groups({Emergency::DATA_GROUP})
     */
    private $geoAreas;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     * @Groups({Emergency::DATA_GROUP})
     */
    private $isActive;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Street", inversedBy="emergencies")
     * @ORM\JoinColumn(name="emergency_id", referencedColumnName="id")
     */
    private $streets;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * @return string
     */
    public function __toString()
    {
        return "Emergency Id".$this->getId();
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->geoAreas = new \Doctrine\Common\Collections\ArrayCollection();
        $this->streets = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set title
     *
     * @param string $title
     *
     * @return Emergency
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     *
     * @return Emergency
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Emergency
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Add geoArea
     *
     * @param \AppBundle\Entity\GeoArea $geoArea
     *
     * @return Emergency
     */
    public function addGeoArea(\AppBundle\Entity\GeoArea $geoArea)
    {
        $this->geoAreas[] = $geoArea;

        return $this;
    }

    /**
     * Remove geoArea
     *
     * @param \AppBundle\Entity\GeoArea $geoArea
     */
    public function removeGeoArea(\AppBundle\Entity\GeoArea $geoArea)
    {
        $this->geoAreas->removeElement($geoArea);
    }

    /**
     * Get geoAreas
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGeoAreas()
    {
        return $this->geoAreas;
    }

    /**
     * Add street
     *
     * @param \AppBundle\Entity\Street $street
     *
     * @return Emergency
     */
    public function addStreet(\AppBundle\Entity\Street $street)
    {
        $this->streets[] = $street;

        return $this;
    }

    /**
     * Remove street
     *
     * @param \AppBundle\Entity\Street $street
     */
    public function removeStreet(\AppBundle\Entity\Street $street)
    {
        $this->streets->removeElement($street);
    }

    /**
     * Get streets
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getStreets()
    {
        return $this->streets;
    }
}
