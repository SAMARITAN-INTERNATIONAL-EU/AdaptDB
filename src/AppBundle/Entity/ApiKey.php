<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\Groups;

/**
 * Entity ApiKey
 * @package AppBundle\Entity
 * @ORM\Entity()
 */
class ApiKey implements \JsonSerializable
{
    const DATA_GROUP = "dataApiKey";
    const ID_GROUP = "idApiKey";

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({ApiKey::ID_GROUP})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=32, nullable=false)
     * @Assert\Length(max=32)
     * @Assert\NotBlank(message="This field cannot be empty.")
     * @Groups({ApiKey::DATA_GROUP})
     */
    private $apiKey;

    /**
     * @ORM\OneToMany(targetEntity="AuthToken", mappedBy="apiKey")
     */
    private $authTokens;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * @Assert\Valid
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=1000, nullable=true)
     * @Assert\Length(max=1000)
     */
    private $remarks;

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return array(
            'id' => $this->getId(),
            'apiKey' => $this->getApiKey(),
        );
    }

    public function __toString()
    {
        return $this->getId() . ' ' .  $this->getApiKey();
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
     * Set apiKey
     *
     * @param string $apiKey
     *
     * @return ApiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Get apiKey
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * Set remarks
     *
     * @param string $remarks
     *
     * @return ApiKey
     */
    public function setRemarks($remarks)
    {
        $this->remarks = $remarks;

        return $this;
    }

    /**
     * Get remarks
     *
     * @return string
     */
    public function getRemarks()
    {
        return $this->remarks;
    }

    /**
     * Set authToken
     *
     * @param string $authToken
     *
     * @return ApiKey
     */
    public function setAuthToken($authToken)
    {
        $this->authToken = $authToken;

        return $this;
    }

    /**
     * Get authToken
     *
     * @return string
     */
    public function getAuthToken()
    {
        return $this->authToken;
    }

    /**
     * Set authTokenValidUntil
     *
     * @param \DateTime $authTokenValidUntil
     *
     * @return ApiKey
     */
    public function setAuthTokenValidUntil($authTokenValidUntil)
    {
        $this->authTokenValidUntil = $authTokenValidUntil;

        return $this;
    }

    /**
     * Get authTokenValidUntil
     *
     * @return \DateTime
     */
    public function getAuthTokenValidUntil()
    {
        return $this->authTokenValidUntil;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->authTokens = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add authToken
     *
     * @param \AppBundle\Entity\AuthToken $authToken
     *
     * @return ApiKey
     */
    public function addAuthToken(\AppBundle\Entity\AuthToken $authToken)
    {
        $this->authTokens[] = $authToken;

        return $this;
    }

    /**
     * Remove authToken
     *
     * @param \AppBundle\Entity\AuthToken $authToken
     */
    public function removeAuthToken(\AppBundle\Entity\AuthToken $authToken)
    {
        $this->authTokens->removeElement($authToken);
    }

    /**
     * Get authTokens
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAuthTokens()
    {
        return $this->authTokens;
    }


    /**
     * Set user
     *
     * @param \AppBundle\Entity\User $user
     *
     * @return ApiKey
     */
    public function setUser(\AppBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \AppBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }
}
