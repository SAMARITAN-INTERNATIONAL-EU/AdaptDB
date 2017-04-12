<?php

namespace AppBundle\EventListener;

use AppBundle\Entity\MedicalRequirement;
use AppBundle\Entity\Person;
use AppBundle\Entity\ContactPerson;
use AppBundle\Entity\PersonAddress;
use AppBundle\Entity\Street;
use AppBundle\Form\TransportRequirementType;
use AppBundle\Service\NameNormalizationService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\PostPersist;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use AppBundle\Entity\DataChangeHistory;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs ;
use Doctrine\ORM\Event\PreFlushEventArgs ;


/**
 * This EventListener creates DataChangeHistory items when entities are created, deleted or updated
 * @package AppBundle\EventListener
 *
 */
class DataChangeListener
{

    private $TRANSLATEABLE_PERSON_STRING = "Person";
    private $TRANSLATEABLE_CONTACTPERSON_STRING = "ContactPerson";
    private $TRANSLATEABLE_PERSONADDRESS_STRING = "PersonAddress";

    private $entitiesToPersist = array();

    //Go get the currently logged in user
    private $token_storage;

    /** @var NameNormalizationService $nameNormalizationService */
    private $nameNormalizationService;

    public function __construct(TokenStorageInterface $token_storage, NameNormalizationService $nameNormalizationService)
    {
        $this->token_storage = $token_storage;
        $this->nameNormalizationService = $nameNormalizationService;
    }

    private function getPropertiesArrayFromContactPerson(ContactPerson $contactPerson) {
        $propertiesArray = array();
        $propertiesArray['firstName'] = $contactPerson->getFirstName();
        $propertiesArray['lastName'] = $contactPerson->getLastName();
        $propertiesArray['phone'] = $contactPerson->getPhone();
        $propertiesArray['remarks'] = $contactPerson->getRemarks();
        return $propertiesArray;
    }

    private function getMedicalRequirementsString($medicalRequirementsArray) {
        $namesArray = [];
        foreach ($medicalRequirementsArray as $medReq) {
            $namesArray[] = $medReq->getName();
        }
        return implode(", ", $namesArray);
    }

    private function getTransportRequirementsString($transportRequirementsArray) {
        $namesArray = [];
        foreach ($transportRequirementsArray as $traReq) {
            $namesArray[] = $traReq->getName();
        }
        return implode(", ", $namesArray);
    }

    private function getPropertiesArrayFromPerson(Person $person) {
        $propertiesArray = array();
        $propertiesArray['firstName'] = $person->getFirstName();
        $propertiesArray['lastName'] = $person->getLastName();
        $propertiesArray['fiscalCode'] = $person->getFiscalCode();
        $propertiesArray['dateOfBirth'] = $person->getDateOfBirth();
        $propertiesArray['landlinePhone'] = $person->getLandlinePhone();
        $propertiesArray['cellPhone'] = $person->getCellPhone();
        $propertiesArray['email'] = $person->getEmail();
        $propertiesArray['remarks'] = $person->getRemarks();
        $propertiesArray['gender'] = $person->getGenderMale() == 1 ? "male" : "female";
        $propertiesArray['vulnerabilityLevel'] = $person->getVulnerabilityLevel()->getName();
        $propertiesArray['medicalRequirements'] = $this->getMedicalRequirementsString($person->getMedicalRequirements());
        $propertiesArray['transportRequirements'] = $this->getTransportRequirementsString($person->getTransportRequirements());
        return $propertiesArray;
    }

    private function getPropertiesArrayFromPersonAddress(PersonAddress $personAddress) {
        $propertiesArray = array();
        $propertiesArray['floor'] = $personAddress->getFloor();

        $propertiesArray['address'] = $personAddress->getCompleteAddressString();

        if ($personAddress->getAbsenceTo()) {
            $propertiesArray['absenceTo'] = $personAddress->getAbsenceFrom() ? $personAddress->getAbsenceFrom()->format('d/m/Y') : "[not set]";
        } else {
            $propertiesArray['absenceTo'] = "[not set]";
        }

        if ($personAddress->getAbsenceFrom()) {
            $propertiesArray['absenceFrom'] = $personAddress->getAbsenceTo() ? $personAddress->getAbsenceTo()->format('d/m/Y') : "[not set]";
        } else {
            $propertiesArray['absenceFrom'] = "[not set]";
        }

        $propertiesArray['address'] = $personAddress->getCompleteAddressString();

        return $propertiesArray;
    }

    /** Saves DataChangeChangeHistory-Entities for changed data and deleted entities
     * DataChangeHistory-Entities are temporary stored in $this->entitiesToPersist and persisted later in postFlush
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs  $args)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $args->getEntityManager();
        /* @var $uow \Doctrine\ORM\UnitOfWork */
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledCollectionUpdates() AS $collection) {

            if ($collection[0] instanceof MedicalRequirement || $collection[0] instanceof TransportRequirement) {
                /** @var Person $person */
                $person = $collection->getOwner();

                //Only generate DataChangeHistory-Item when Person-entity existed before
                //If entity is new the field are set in the postPersist function
                if ($person && $person->getId()) {
                    /** @var DataChangeHistory $newDataChangeHistory */
                    $newDataChangeHistory = new DataChangeHistory();
                    $newDataChangeHistory->setPerson($person);
                    $em->refresh($person);

                    $this->setChangedByUserForDataChangeHistory($em, $newDataChangeHistory);

                    if ($collection[0] instanceof MedicalRequirement) {
                        $newDataChangeHistory->setProperty("medicalRequirements");
                        $newDataChangeHistory->setOldValue($this->getMedicalRequirementsString($person->getMedicalRequirements()));
                    } else {
                        $newDataChangeHistory->setProperty("transportRequirements");
                        $newDataChangeHistory->setOldValue($this->getTransportRequirementsString($person->getMedicalRequirements()));
                    }

                    $newDataChangeHistory->setNewValue($this->getTransportRequirementsString($collection));

                    //Save the $newDataChangeHistory-item
                    $newDataChangeHistory->setTimestamp(new \DateTime());

                    $em->persist($newDataChangeHistory);
                    $classMetadata = $em->getClassMetadata(get_class($newDataChangeHistory));
                    $uow->computeChangeSet($classMetadata, $newDataChangeHistory);
                }
            }
        }

        //For Updates of entities like person.firstName
        foreach ($uow->getScheduledEntityUpdates() AS $entity) {

            if ($entity instanceof Person) {
                $entity->setFirstNameNormalized($this->nameNormalizationService->normalizeName($entity->getFirstName()));
                $entity->setLastNameNormalized($this->nameNormalizationService->normalizeName($entity->getLastName()));
            }

            if ($entity instanceof Street) {
                $entity->setNameNormalized($this->nameNormalizationService->normalizeStreetName($entity->getName()));
            }

            foreach ($uow->getEntityChangeSet($entity) AS $fieldName => $changeSet) {

                //Changes of addresses are persisted in AddressController->EditAction
                if ($entity instanceof Person && (!(($fieldName == "firstNameNormalized") || ($fieldName == "lastNameNormalized") || ($fieldName == "potentialIdentity"))) ||
                    $entity instanceof ContactPerson ||
                    ($entity instanceof PersonAddress && !($fieldName == "address"))
                ) {

                    if ($changeSet[0] != $changeSet[1]) {

                        /** @var DataChangeHistory $newDataChangeHistory */
                        $newDataChangeHistory = new DataChangeHistory();
                        $this->setChangedByUserForDataChangeHistory($em, $newDataChangeHistory);

                        if ($fieldName == 'dateOfBirth' || $fieldName == 'validUntil' || $fieldName == 'absenceFrom' || $fieldName == 'absenceTo') {
                            $newDataChangeHistory->setOldValue($changeSet[0] ? $changeSet[0]->format('d/m/Y') : "[not set]");
                            $newDataChangeHistory->setNewValue($changeSet[1] ? $changeSet[1]->format('d/m/Y') : "[not set]");
                        } else {
                            $newDataChangeHistory->setOldValue($changeSet[0]);
                            $newDataChangeHistory->setNewValue($changeSet[1]);
                        }

                        if ($entity instanceof Person) {
                            $newDataChangeHistory->setPerson($entity);
                            $newDataChangeHistory->setProperty($fieldName);

                            if ($fieldName == "genderMale") {
                                $newDataChangeHistory->setProperty("gender");
                                if ($changeSet[0] == "1") {
                                    $newDataChangeHistory->setOldValue("Male");
                                } else {
                                    $newDataChangeHistory->setOldValue("Female");
                                }
                                if ($changeSet[1] == "1") {
                                    $newDataChangeHistory->setNewValue("Male");
                                } else {
                                    $newDataChangeHistory->setNewValue("Female");
                                }
                            }

                            if ($fieldName == "safetyStatus") {
                                $newDataChangeHistory->setProperty("safetyStatus");
                                if ($changeSet[0] == "1") {
                                    $newDataChangeHistory->setOldValue("Safe");
                                } else {
                                    $newDataChangeHistory->setOldValue("Not Safe");
                                }
                                if ($changeSet[1] == "1") {
                                    $newDataChangeHistory->setNewValue("Safe");
                                } else {
                                    $newDataChangeHistory->setNewValue("Not Safe");
                                }
                            }
                        }

                        if ($entity instanceof PersonAddress) {
                            $newDataChangeHistory->setPerson($entity->getPerson());

                            $newDataChangeHistory->setProperty($this->TRANSLATEABLE_PERSONADDRESS_STRING .'.[' . $entity->getId() . '].' . $fieldName);
                        }

                        if ($entity instanceof ContactPerson) {
                            $newDataChangeHistory->setPerson($entity->getPerson());
                            $newDataChangeHistory->setProperty($this->TRANSLATEABLE_CONTACTPERSON_STRING .'.[' . $entity->getId() . '].' . $fieldName);
                        }

                        //Save the $newDataChangeHistory-item
                        $newDataChangeHistory->setTimestamp(new \DateTime());
                        $em->persist($newDataChangeHistory);
                        $classMetadata = $em->getClassMetadata(get_class($newDataChangeHistory));
                        $uow->computeChangeSet($classMetadata, $newDataChangeHistory);
                    }
                }
            }
        }

        //For Deletions
        foreach ($uow->getScheduledEntityDeletions() AS $entityToBeDeleted) {

            //If Entity is ContactPerson
            if ($entityToBeDeleted instanceof ContactPerson) {

                //Prepare an Array with the information that should be saved in DataChangeHistory-items
                $propertiesArray = $this->getPropertiesArrayFromContactPerson($entityToBeDeleted);

                foreach ($propertiesArray as $propertyKey => $propertyValue) {
                    $newDataChangeHistory = $this->getNewDataChangeHistory($em, $entityToBeDeleted->getPerson());
                    $newDataChangeHistory->setProperty($this->TRANSLATEABLE_CONTACTPERSON_STRING . '.[' . $entityToBeDeleted->getId() . '].' . $propertyKey);
                    $newDataChangeHistory->setOldValue($propertyValue);
                    //String is like this to be translatable
                    $newDataChangeHistory->setNewValue("_none_" . $this->TRANSLATEABLE_CONTACTPERSON_STRING . "_deleted");
                    $em->persist($newDataChangeHistory);

                    $classMetadata = $em->getClassMetadata(get_class($newDataChangeHistory));
                    $uow->computeChangeSet($classMetadata, $newDataChangeHistory);
                }
            }

            //If Entity is PersonAddress
            if ($entityToBeDeleted instanceof PersonAddress) {

                //Prepare an Array with the information that should be saved in DataChangeHistory-items
                $propertiesArray = $this->getPropertiesArrayFromPersonAddress($entityToBeDeleted);

                foreach ($propertiesArray as $propertyKey => $propertyValue) {
                    $newDataChangeHistory = $this->getNewDataChangeHistory($em, $entityToBeDeleted->getPerson());

                    $newDataChangeHistory->setProperty($this->TRANSLATEABLE_PERSONADDRESS_STRING . '.[' . $entityToBeDeleted->getId() . '].' . $propertyKey);
                    $newDataChangeHistory->setOldValue($propertyValue);
                    //String is like this to be translatable
                    $newDataChangeHistory->setNewValue("_none_" . $this->TRANSLATEABLE_PERSONADDRESS_STRING . "_deleted");
                    $em->persist($newDataChangeHistory);

                    $classMetadata = $em->getClassMetadata(get_class($newDataChangeHistory));
                    $uow->computeChangeSet($classMetadata, $newDataChangeHistory);
                }
            }
        }
    }

    /**
     * Saves DataChangeHistory-Entities for new entities
     * DataChangeHistory-Entities are temporary stored in $this->entitiesToPersist and persisted later in postFlush
     * @param LifecycleEventArgs $args
     */
    function postPersist(LifecycleEventArgs $args) {

        $entity = $args->getEntity();

        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $args->getEntityManager();

        if ($entity instanceof Person) {

            $person = $entity;
            $person->setFirstNameNormalized($this->nameNormalizationService->normalizeName($entity->getFirstName()));
            $person->setLastNameNormalized($this->nameNormalizationService->normalizeName($entity->getLastName()));
            $em->persist($person);

            //add DataChangeHistory-Items
            $personsPropertiesArray = $this->getPropertiesArrayFromPerson($person);
            foreach ($personsPropertiesArray as $propertyKey => $propertyValue) {
                $newDataChangeHistory = $this->getNewDataChangeHistory($em, $person);

                $newDataChangeHistory->setProperty($this->TRANSLATEABLE_PERSON_STRING . ".[" . $person->getId() . "]." . $propertyKey);
                if ($propertyKey == "dateOfBirth") {
                    $newDataChangeHistory->setNewValue($propertyValue ? $propertyValue->format('d/m/Y') : "[not set]");

                } else {
                    $newDataChangeHistory->setNewValue($propertyValue);
                }
                //String is like this to be translatable
                $newDataChangeHistory->setOldValue("_none_" . $this->TRANSLATEABLE_PERSON_STRING . "_created");

                $this->entitiesToPersist[] = $newDataChangeHistory;
            }

        } else if ($entity instanceof Street) {
            $street = $entity;
            $street->setNameNormalized($this->nameNormalizationService->normalizeStreetName($entity->getName()));
            $this->entitiesToPersist[] = $street;
        } else if ($entity instanceof ContactPerson) {
            $contactPerson = $entity;
            $contactPersonsPropertiesArray = $this->getPropertiesArrayFromContactPerson($contactPerson);

            foreach ($contactPersonsPropertiesArray as $propertyKey => $propertyValue) {

                $newDataChangeHistory = $this->getNewDataChangeHistory($em, $contactPerson->getPerson());

                $newDataChangeHistory->setProperty($this->TRANSLATEABLE_CONTACTPERSON_STRING . ".[" . $contactPerson->getId() . "]." . $propertyKey);
                $newDataChangeHistory->setNewValue($propertyValue);
                $newDataChangeHistory->setOldValue("_none_" . $this->TRANSLATEABLE_CONTACTPERSON_STRING . "_created");

                $this->entitiesToPersist[] = $newDataChangeHistory;
            }
        } else if ($entity instanceof PersonAddress) {
            $personAddress = $entity;
            $personAddressPropertiesArray = $this->getPropertiesArrayFromPersonAddress($personAddress);

            foreach ($personAddressPropertiesArray as $propertyKey => $propertyValue) {

                $newDataChangeHistory = $this->getNewDataChangeHistory($em, $personAddress->getPerson());
                $newDataChangeHistory->setProperty($this->TRANSLATEABLE_PERSONADDRESS_STRING . ".[" . $personAddress->getId() . "].". $propertyKey);
                $newDataChangeHistory->setNewValue($propertyValue);
                //String is like this to be translatable
                $newDataChangeHistory->setOldValue("_none_" . $this->TRANSLATEABLE_PERSONADDRESS_STRING . "_created");

                $this->entitiesToPersist[] = $newDataChangeHistory;
            }
        }
    }


    /**
     * Persists all DataChangeHistory-Items that were generated in the functions postPersist() and onFlush()
     * @param PostFlushEventArgs $eventArgs
     */
    public function postFlush(PostFlushEventArgs $eventArgs)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $eventArgs->getEntityManager();

        if (count($this->entitiesToPersist) >= 1) {

            foreach ($this->entitiesToPersist as $key => $entityToPersist) {
                $em->persist($entityToPersist);
                unset($this->entitiesToPersist[$key]);
            }

            $em->flush();
        }
    }

    /**
     * Sets the user-property for an DataChangeHistory-Entity
     * @param EntityManager $em
     * @param DataChangeHistory $dataChangeHistory
     */
    public function setChangedByUserForDataChangeHistory(EntityManager $em, DataChangeHistory &$dataChangeHistory) {

          if ($this->token_storage->getToken() != null) {
              $dataChangeHistory->setChangedByUser($this->token_storage->getToken()->getUser());
          } else {
              //PersonChangesListener could not set 'changedByUser'-property of DataChangeHistory-item
              //In most cases the change will be triggered by the a cron-command

              //Setting the ChangedByUser to cron-user
              $cronjobUser = $em->getRepository('AppBundle:User')->findOneBy(array('username' => 'cronjob-user'));
              $dataChangeHistory->setChangedByUser($cronjobUser);
          }

          return $dataChangeHistory;
    }

    /**
     * Simple function to get an DataChangeHistory-Entity with preset-values
     * @param EntityManager $em
     * @param Person $person
     */
    public function getNewDataChangeHistory(EntityManager $em, Person $person = null) {

        $newDataChangeHistory = new DataChangeHistory();
        $newDataChangeHistory->setPerson($person);
        $newDataChangeHistory->setTimestamp(new \DateTime());

        $this->setChangedByUserForDataChangeHistory($em, $newDataChangeHistory);

        return $newDataChangeHistory;

    }

    /**
     * Sets the user from the passed securityContext
     * @param SecurityContext $securityContext
     */
    public function setUserFromSecurityContext(SecurityContext $securityContext)
    {
        # notice, there are a cases when `getToken()` returns null, so improve this
        $this->user = $securityContext->getToken()->getUser();
    }
}
