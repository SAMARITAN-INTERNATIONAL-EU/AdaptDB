<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\Groups;

//define("DATA_GROUP", "dataAuthToken", true);

/**
 * Entity AuthToken
 * @package AppBundle\Entity
 * @ORM\Entity()
 */
class AuthToken implements \JsonSerializable
{
    const DATA_GROUP = "dataAuthToken";


    const ID_GROUP = "idAuthToken";

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({AuthToken::ID_GROUP})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\ApiKey",  inversedBy="authTokens")
     * @ORM\JoinColumn(name="api_key_id", referencedColumnName="id")
     */
    private $apiKey;

    /**
     * @ORM\Column(type="string", length=32, nullable=false)
     * @Assert\Length(max=32)
     * @Assert\NotBlank(message="This field cannot be empty.")
     * @Groups({AuthToken::DATA_GROUP})
     */
    private $token;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     * @Groups({AuthToken::DATA_GROUP})
     */
    private $generated;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $lastUsage;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     * @Groups({AuthToken::DATA_GROUP})
     */
    private $exceeds;

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return array(
            'id' => $this->getId(),
            'token' => $this->getToken(),
            'generated' => $this->getGenerated(),
            'exceeds' => $this->getExceeds()
        );
    }

    public function __toString()
    {
        return $this->getId() . ' ' .  $this->getToken();
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
     * Set token
     *
     * @param string $token
     *
     * @return AuthToken
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set generated
     *
     * @param \DateTime $generated
     *
     * @return AuthToken
     */
    public function setGenerated($generated)
    {
        $this->generated = $generated;

        return $this;
    }

    /**
     * Get generated
     *
     * @return \DateTime
     */
    public function getGenerated()
    {
        return $this->generated;
    }

    /**
     * Set lastUsage
     *
     * @param \DateTime $lastUsage
     *
     * @return AuthToken
     */
    public function setLastUsage($lastUsage)
    {
        $this->lastUsage = $lastUsage;

        return $this;
    }

    /**
     * Get lastUsage
     *
     * @return \DateTime
     */
    public function getLastUsage()
    {
        return $this->lastUsage;
    }

    /**
     * Set exceeds
     *
     * @param \DateTime $exceeds
     *
     * @return AuthToken
     */
    public function setExceeds($exceeds)
    {
        $this->exceeds = $exceeds;

        return $this;
    }

    /**
     * Get exceeds
     *
     * @return \DateTime
     */
    public function getExceeds()
    {
        return $this->exceeds;
    }

    /**
     * Set apiKey
     *
     * @param \AppBundle\Entity\ApiKey $apiKey
     *
     * @return AuthToken
     */
    public function setApiKey(\AppBundle\Entity\ApiKey $apiKey = null)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Get apiKey
     *
     * @return \AppBundle\Entity\ApiKey
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }
}
