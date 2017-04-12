<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\Groups;

/**
 * Entity GeoArea
 * @package AppBundle\Entity
 * @ORM\Entity(repositoryClass="AppBundle\Repository\GeoAreaRepository")
 */
class GeoArea implements \JsonSerializable
{
    const DATA_GROUP = "dataGeoArea";
    const ID_GROUP = "idGeoArea";

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({GeoArea::ID_GROUP})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Assert\Length(max=255)
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Emergency", inversedBy="geoAreas")
     * @ORM\JoinColumn(name="emergency_id", referencedColumnName="id")
     */
    private $emergency;

    /**
     * @ORM\OneToMany(targetEntity="GeoPoint", mappedBy="geoArea")
     * @Groups({GeoArea::DATA_GROUP})
     */
    private $geoPoints;

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        //TODO change
        return array(
            'name' => $this->getName(),
            'geoPoints' => $this->getGeoPoints()->toArray(),
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
     * @return GeoArea
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
     * Set emergency
     *
     * @param \AppBundle\Entity\Emergency $emergency
     *
     * @return GeoArea
     */
    public function setEmergency(\AppBundle\Entity\Emergency $emergency = null)
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
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->geoPoints = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add geoPoint
     *
     * @param \AppBundle\Entity\GeoPoint $geoPoint
     *
     * @return GeoArea
     */
    public function addGeoPoint(\AppBundle\Entity\GeoPoint $geoPoint)
    {
        $this->geoPoints[] = $geoPoint;

        return $this;
    }

    /**
     * Remove geoPoint
     *
     * @param \AppBundle\Entity\GeoPoint $geoPoint
     */
    public function removeGeoPoint(\AppBundle\Entity\GeoPoint $geoPoint)
    {
        $this->geoPoints->removeElement($geoPoint);
    }

    /**
     * Get geoPoints
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGeoPoints()
    {
        return $this->geoPoints;
    }
}
