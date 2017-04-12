<?php
namespace AppBundle\Service;

use AppBundle\Entity\Person;
use AppBundle\Entity\PotentialIdentityCluster;
use AppBundle\Entity\User;

/**
 * Class NameNormalizationService
 * @package AppBundle\Service
 */
class PotentialIdentityClusterHelperService
{

    /**
     * @param $personArray
     * @return array
     */
    public function getPersonIdsArrayFromPersonArray($personArray) {

        $personIdArray = array();

        foreach ($personArray as $person) {
            $personIdArray[] = $person->getId();
        }

        sort($personIdArray);

        return $personIdArray;
    }


    /**
     * @param $created
     * @param User $user
     * @return PotentialIdentityCluster
     */
    public function getNewPotentialIdentityCluster($created, User $user) {
        $newPotentialIdentityCluster = new PotentialIdentityCluster();
        $newPotentialIdentityCluster->setSource("User:" . $user->getUsername());
        $newPotentialIdentityCluster->setTimestampModified(new \DateTime());
        $newPotentialIdentityCluster->setWasCreated($created);
        return $newPotentialIdentityCluster;
    }

    /**
     * @param $piCluster
     * @param $personArray
     */
    public function addPersonsToPotentialIdentityCluster($piCluster, $personArray) {

        foreach ($personArray as $person) {
            $piCluster->addPerson($person);
        }
    }
}

