<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\Groups;

/**
 * Entity Address
 * @package AppBundle\Entity
 * @ORM\Entity()
 */
class Address implements \JsonSerializable
{
    const DATA_GROUP = "dataAddress";
    const ID_GROUP = "idAddress";

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({Address::ID_GROUP})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50, nullable=false)
     * @Assert\Length(max=50)
     * @Assert\NotBlank(message="This field cannot be empty.")
     * @Groups({Address::DATA_GROUP})
     */
    private $houseNr;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Street")
     * @ORM\JoinColumn(name="street_id", referencedColumnName="id")
     * @Assert\Valid
     * @Groups({Address::DATA_GROUP})
     */
    private $street;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\GeoPoint")
     * @ORM\JoinColumn(name="geoPoint_id", referencedColumnName="id")
     * @Assert\Valid
     * @Groups({Address::DATA_GROUP})
     */
    private $geopoint;

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        //TODO change
        return array(
            'street' => $this->getStreet(),
            'geoPoint' => $this->getGeopoint(),
            'houseNr' => $this->getHouseNr(),
        );
    }

    public function __toString()
    {
        return $this->getStreet()->getName() . ' ' .  $this->getHouseNr();
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
     * Set houseNr
     *
     * @param string $houseNr
     *
     * @return Address
     */
    public function setHouseNr($houseNr)
    {
        $this->houseNr = $houseNr;

        return $this;
    }

    /**
     * Get houseNr
     *
     * @return string
     */
    public function getHouseNr()
    {
        return $this->houseNr;
    }

    /**
     * Set street
     *
     * @param \AppBundle\Entity\Street $street
     *
     * @return Address
     */
    public function setStreet(\AppBundle\Entity\Street $street = null)
    {
        $this->street = $street;

        return $this;
    }

    /**
     * Get street
     *
     * @return \AppBundle\Entity\Street
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * Set geopoint
     *
     * @param \AppBundle\Entity\GeoPoint $geopoint
     *
     * @return Address
     */
    public function setGeopoint(\AppBundle\Entity\GeoPoint $geopoint = null)
    {
        $this->geopoint = $geopoint;

        return $this;
    }

    /**
     * Get geopoint
     *
     * @return \AppBundle\Entity\GeoPoint
     */
    public function getGeopoint()
    {
        return $this->geopoint;
    }
}
