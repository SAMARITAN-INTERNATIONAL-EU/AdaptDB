<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\Groups;

/**
 * Entity Zipcode
 * @package AppBundle\Entity
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ZipcodeRepository")
 * @ORM\Table(name="zipcode",uniqueConstraints={@UniqueConstraint(name="uniqueZipcodeConstraint", columns={"zipcode", "city", "country_id"}, options={"where": "(((id IS NOT NULL) AND (name IS NULL)) AND (email IS NULL))"})})
 */
class Zipcode implements \JsonSerializable
{
    const DATA_GROUP = "dataZipcode";
    const ID_GROUP = "idZipcode";

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({Zipcode::ID_GROUP})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100, nullable=false)
     * @Assert\Length(max=100)
     * @Assert\NotBlank(message="This field cannot be empty.")
     * @Groups({Zipcode::DATA_GROUP})
     */
    private $zipcode;

    /**
     * @ORM\Column(type="string", length=100, nullable=false)
     * @Assert\Length(max=100)
     * @Assert\NotBlank(message="This field cannot be empty.")
     * @Groups({Zipcode::DATA_GROUP})
     */
    private $city;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Country")
     * @ORM\JoinColumn(name="country_id", referencedColumnName="id")
     * @Assert\Valid
     * @Groups({Zipcode::DATA_GROUP})
     */
    private $country;

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return array(
            'zipcode' => $this->getZipCode(),
            'city' => $this->getCity(),
        );
    }

    public function __toString()
    {
        return 'Zipcode ' . $this->getZipcode() . '(ID: ' . $this->getId() . ')';
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
     * Set zipcode
     *
     * @param string $zipcode
     *
     * @return Zipcode
     */
    public function setZipcode($zipcode)
    {
        $this->zipcode = $zipcode;

        return $this;
    }

    /**
     * Get zipcode
     *
     * @return string
     */
    public function getZipcode()
    {
        return $this->zipcode;
    }

    /**
     * Set city
     *
     * @param string $city
     *
     * @return Zipcode
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set country
     *
     * @param \AppBundle\Entity\Country $country
     *
     * @return Zipcode
     */
    public function setCountry(\AppBundle\Entity\Country $country = null)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return \AppBundle\Entity\Country
     */
    public function getCountry()
    {
        return $this->country;
    }
}
