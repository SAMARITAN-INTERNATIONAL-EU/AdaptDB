<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\Groups;

/**
 * Entity DataSource
 * @package AppBundle\Entity
 * @ORM\Entity()
 */
class DataSource implements \JsonSerializable
{
    const DATA_GROUP = "dataDataSource";
    const ID_GROUP = "idDataSource";

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({DataSource::ID_GROUP})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Assert\NotBlank(message="This field cannot be empty.")
     * @Groups({DataSource::DATA_GROUP})
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=3, nullable=false)
     * @Assert\NotBlank(message="This field cannot be empty.")
     * @Groups({DataSource::DATA_GROUP})
     */
    private $nameShort;

    /**
     * @ORM\Column(type="boolean")
     * @Groups({DataSource::DATA_GROUP})
     */
    private $isOfficial;

    /**
     * @ORM\Column(type="boolean")
     * @Groups({DataSource::DATA_GROUP})
     */
    private $defaultForAutomaticUpdateForClearlyIdentifiedAddresses;

    /**
     * @ORM\Column(type="boolean")
     * @Groups({DataSource::DATA_GROUP})
     */
    private $defaultForDetectMissingPersons;

    /**
     * @ORM\Column(type="boolean")
     * @Groups({DataSource::DATA_GROUP})
     */
    private $defaultForEnableGeocoding;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\ImportKeyColumn")
     * @ORM\JoinColumn(name="importKeyColumn", referencedColumnName="id")
     * @Assert\Count(
     *      min = "1",
     *      minMessage = "You must specify at least one key column"
     * )
     */
    private $importKeyColumns;

    /**
     * @return array
     */
    public function jsonSerialize()
    {

        //TODO change
        return array(
            'name' => $this->getName(),
        );
    }

    public function __toString()
    {
        return $this->name;
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
     * @return DataSource
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
     * Constructor
     */
    public function __construct()
    {
        $this->importKeyColumns = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add importKeyColum
     *
     * @param \AppBundle\Entity\ImportKeyColumn $importKeyColum
     *
     * @return DataSource
     */
    public function addImportKeyColum(\AppBundle\Entity\ImportKeyColumn $importKeyColum)
    {
        $this->importKeyColumns[] = $importKeyColum;

        return $this;
    }

    /**
     * Remove importKeyColum
     *
     * @param \AppBundle\Entity\ImportKeyColumn $importKeyColum
     */
    public function removeImportKeyColum(\AppBundle\Entity\ImportKeyColumn $importKeyColum)
    {
        $this->importKeyColumns->removeElement($importKeyColum);
    }

    /**
     * Get importKeyColumns
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getImportKeyColumns()
    {
        return $this->importKeyColumns;
    }


    /**
     * Set defaultForAutomaticUpdateForClearlyIdentifiedAddresses
     *
     * @param boolean $defaultForAutomaticUpdateForClearlyIdentifiedAddresses
     *
     * @return DataSource
     */
    public function setDefaultForAutomaticUpdateForClearlyIdentifiedAddresses($defaultForAutomaticUpdateForClearlyIdentifiedAddresses)
    {
        $this->defaultForAutomaticUpdateForClearlyIdentifiedAddresses = $defaultForAutomaticUpdateForClearlyIdentifiedAddresses;

        return $this;
    }

    /**
     * Get defaultForAutomaticUpdateForClearlyIdentifiedAddresses
     *
     * @return boolean
     */
    public function getDefaultForAutomaticUpdateForClearlyIdentifiedAddresses()
    {
        return $this->defaultForAutomaticUpdateForClearlyIdentifiedAddresses;
    }

    /**
     * Set defaultForEnableGeocoding
     *
     * @param boolean $defaultForEnableGeocoding
     *
     * @return DataSource
     */
    public function setDefaultForEnableGeocoding($defaultForEnableGeocoding)
    {
        $this->defaultForEnableGeocoding = $defaultForEnableGeocoding;

        return $this;
    }

    /**
     * Get defaultForEnableGeocoding
     *
     * @return boolean
     */
    public function getDefaultForEnableGeocoding()
    {
        return $this->defaultForEnableGeocoding;
    }

    /**
     * Add importKeyColumn
     *
     * @param \AppBundle\Entity\ImportKeyColumn $importKeyColumn
     *
     * @return DataSource
     */
    public function addImportKeyColumn(\AppBundle\Entity\ImportKeyColumn $importKeyColumn)
    {
        $this->importKeyColumns[] = $importKeyColumn;

        return $this;
    }

    /**
     * Remove importKeyColumn
     *
     * @param \AppBundle\Entity\ImportKeyColumn $importKeyColumn
     */
    public function removeImportKeyColumn(\AppBundle\Entity\ImportKeyColumn $importKeyColumn)
    {
        $this->importKeyColumns->removeElement($importKeyColumn);
    }

    /**
     * Set defaultForDetectMissingPersons
     *
     * @param boolean $defaultForDetectMissingPersons
     *
     * @return DataSource
     */
    public function setDefaultForDetectMissingPersons($defaultForDetectMissingPersons)
    {
        $this->defaultForDetectMissingPersons = $defaultForDetectMissingPersons;

        return $this;
    }

    /**
     * Get defaultForDetectMissingPersons
     *
     * @return boolean
     */
    public function getDefaultForDetectMissingPersons()
    {
        return $this->defaultForDetectMissingPersons;
    }

    /**
     * Set nameShort
     *
     * @param string $nameShort
     *
     * @return DataSource
     */
    public function setNameShort($nameShort)
    {
        $this->nameShort = $nameShort;

        return $this;
    }

    /**
     * Get nameShort
     *
     * @return string
     */
    public function getNameShort()
    {
        return $this->nameShort;
    }

    /**
     * Set isOfficial
     *
     * @param boolean $isOfficial
     *
     * @return DataSource
     */
    public function setIsOfficial($isOfficial)
    {
        $this->isOfficial = $isOfficial;

        return $this;
    }

    /**
     * Get isOfficial
     *
     * @return boolean
     */
    public function getIsOfficial()
    {
        return $this->isOfficial;
    }
}
