<?php
namespace AppBundle\Service;

use AppBundle\Entity\Person;
use AppBundle\Entity\PotentialIdentity;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use AppBundle\Service\PotentialIdentityClusterHelperService;

/**
 * Class PotentialIdentityHelperService
 * @package AppBundle\Service
 */
class PotentialIdentityHelperService
{

    /**
     *
     * This function removes a potential identity and all assignments from persons to this PI
     * It is called from dissolvePotentialIdentityOfPersonAction and removePersonFromPotentialIdentityAction
     *
     * This function dissolves a given PI
     */
    public function dissolvePotentialIdentity(EntityManager $em, PotentialIdentityClusterHelperService $piClusterHelperService, User $user, PotentialIdentity $potentialIdentity) {

        /** @var PotentialIdentityCluster $newPotentialIdentityCluster */
        $newPotentialIdentityCluster = $piClusterHelperService->getNewPotentialIdentityCluster(0, $user);

        //Remove the connection between potential Identities and persons
        /** @var Person $person */
        foreach ($potentialIdentity->getPersons() as $person) {
            $person->setPotentialIdentity(null);
            $em->persist($person);

            $newPotentialIdentityCluster->addPerson($person);
        }

        //Check if related inconsitentPI-entities exist
        $inconsistentPIs = $em->getRepository("AppBundle:InconsistentPI")->findBy(array("potentialIdentity" => $potentialIdentity->getId()));

        foreach ($inconsistentPIs as $inconsistentPI) {
            $em->remove($inconsistentPI);
        }

        $em->remove($potentialIdentity);
        $em->persist($newPotentialIdentityCluster);

        $em->flush();
    }

    /**
     *
     * This function removes a person from an potential identity
     */
    public function removePersonFromPotentialIdentity(EntityManager $em, PotentialIdentityClusterHelperService $piClusterHelperService, $user, $personIdToRemove, $originPersonId) {

        $originPerson = $em->getRepository('AppBundle:Person')->find($originPersonId);
        $personToRemove = $em->getRepository('AppBundle:Person')->find($personIdToRemove);

        if ($personToRemove === null) {
            throw new \Doctrine\ORM\EntityNotFoundException();
        }

        $personsOfThisPotentialIdentity = $em->getRepository('AppBundle:Person')->findBy(array('potentialIdentity' => $originPerson->getPotentialIdentity()));

        if (count($personsOfThisPotentialIdentity) <= 2 ) {
            //If there are only 1 or 2 Persons in the dissolvePI function can be used
            $this->dissolvePotentialIdentity($em, $piClusterHelperService, $user, $personToRemove->getPotentialIdentity());
        } else {

            $personIdsArray = array();

            foreach ($personsOfThisPotentialIdentity as $person) {
                $personIdsArray[] = $person->getId();
            }

            //Get PICluster that belongs to the PI
            /** @var PotentialIdentityCluster $piCluster */
            $piCluster = $em->getRepository('AppBundle:PotentialIdentityCluster')->findCreatedForPersonIds($personIdsArray);
            $piCluster = isset($piCluster[0]) ? $piCluster[0] : null;

            //Update the "Created" (Confirmed)-Cluster
            if ($piCluster) {
                $piCluster->removePerson($personToRemove);
                $em->persist($piCluster);
            }

            //Add a cluster "Removed" Cluster to prevent that Cluster from being created again
            $newPotentialIdentityCluster = $piClusterHelperService->getNewPotentialIdentityCluster(0, $this->getUser());
            $piClusterHelperService->addPersonsToPotentialIdentityCluster($newPotentialIdentityCluster, $personsOfThisPotentialIdentity);

            $em->persist($newPotentialIdentityCluster);

            //If the PI has more than 2 Persons than the PI cannot be dissolved, because it is still needed
            $personToRemove->setPotentialIdentity(null);
            $em->persist($person);

            $em->flush();
        }
    }
}
