<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\Groups;

/**
 * Entity GeoPoint
 * @package AppBundle\Entity
 * @ORM\Entity()
 */
class GeoPoint implements JsonSerializable
{
    const DATA_GROUP = "dataGeoPoint";
    const ID_GROUP = "idGeoPoint";

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({GeoPoint::ID_GROUP})
     */
    private $id;

    /**
     * @ORM\Column(type="float", nullable=false)
     * @Groups({GeoPoint::DATA_GROUP})
     */
    private $lat;

    /**
     * @ORM\Column(type="float", nullable=false)
     * @Groups({GeoPoint::DATA_GROUP})
     */
    private $lng;

    /**
     * @ORM\Column(type="point", nullable=false)
     */
    private $point;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({GeoPoint::DATA_GROUP})
     */
    private $position;

    /**
    * @ORM\ManyToOne(targetEntity="AppBundle\Entity\GeoArea",  inversedBy="geoPoints")
    * @ORM\JoinColumn(name="geo_area_id", referencedColumnName="id")
    */
    private $geoArea;


    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return array(
            'pos' => $this->getPosition(),
            'lat' => $this->getLat(),
            'lng'=> $this->getLng(),
        );
    }
    
    //TODO change
    public function __toString()
    {
        return "GeoPoint Id" . $this->getId();
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
     * Set lat
     *
     * @param float $lat
     *
     * @return GeoPoint
     */
    public function setLat($lat)
    {
        $this->lat = $lat;

        return $this;
    }

    /**
     * Get lat
     *
     * @return float
     */
    public function getLat()
    {
        return $this->lat;
    }

    /**
     * Set position
     *
     * @param integer $position
     *
     * @return GeoPoint
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position
     *
     * @return integer
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Set geoArea
     *
     * @param \AppBundle\Entity\GeoArea $geoArea
     *
     * @return GeoPoint
     */
    public function setGeoArea(\AppBundle\Entity\GeoArea $geoArea = null)
    {
        $this->geoArea = $geoArea;

        return $this;
    }

    /**
     * Get geoArea
     *
     * @return \AppBundle\Entity\GeoArea
     */
    public function getGeoArea()
    {
        return $this->geoArea;
    }

    /**
     * Set lng
     *
     * @param float $lng
     *
     * @return GeoPoint
     */
    public function setLng($lng)
    {
        $this->lng = $lng;

        return $this;
    }

    /**
     * Get lng
     *
     * @return float
     */
    public function getLng()
    {
        return $this->lng;
    }

    /**
     * Set point
     *
     * @param point $point
     *
     * @return GeoPoint
     */
    public function setPoint($point)
    {
        $this->point = $point;

        return $this;
    }

    /**
     * Get point
     *
     * @return point
     */
    public function getPoint()
    {
        return $this->point;
    }
}
