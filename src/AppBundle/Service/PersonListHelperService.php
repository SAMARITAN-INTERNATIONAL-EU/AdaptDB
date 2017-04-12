<?php
namespace AppBundle\Service;
use AppBundle\Entity\Address;
use AppBundle\Entity\Emergency;
use AppBundle\Entity\Person;
use AppBundle\Entity\PersonAddress;
use AppBundle\Entity\Street;
use AppBundle\Entity\User;
use AppBundle\Entity\Zipcode;
use Doctrine\ORM\EntityManager;

/**
 * Class PersonListHelperService
 * @package AppBundle\Service
 */
class PersonListHelperService
{

    /**
     * Returns true if one of the personAddresses contains an GeoPoint
     *
     * @param $personOrPersonArray
     * @return bool
     */
    public function shouldShowMapInFrontend($personOrPersonArray) {

        if (!is_array($personOrPersonArray)) {
            $personsArray = [];
            $personsArray[] = $personOrPersonArray;
        } else {
            $personsArray = $personOrPersonArray;
        }

        $showMap = false;

        /** @var Person $person */
        foreach ($personsArray as $person) {
            /** @var PersonAddress $personAddress */
            foreach ($person->getPersonAddresses() as $personAddress) {
                /** @var Address $tmpAddress */
                $tmpAddress = $personAddress->getAddress();
                if ($tmpAddress && $tmpAddress->getGeopoint()) {
                    return true;
                }
            }
        }
        return $showMap;
    }

    /**
     *
     * Creates an array that is used for display on the index-template
     * To preserve the pagination every person from $personArray is enhanced with a list of possible other persons
     * belonging to the same potential identity
     *
     * @param $em
     * @param $personsArray
     * @param $emergencyId
     * @return array
     */
    public function buildArrayForDisplayPersonLists($personListMode, EntityManager $em, $personsArray, $emergencyId, User $user, $exactPolygonMatchMode) {

        //Grouping by Potential Identities

        //Getting an array of PotentialIdentities
        $piIdArray = $this->getUniquePotentialIdentityIdsFromPersonArray($personsArray);
        //Without clearing the EntityManager the query will potentially return cached entities
        //This leads to incomplete results (for example personAddresses are missing)
        $em->clear();

        // When user has only the role Rescue Worker
        if ($user->hasOnlyRoleRescueWorker()) {

            /** @var Emergency $emergency */
            $emergency = $em->getRepository("AppBundle:Emergency")->find($emergencyId);

            $emergencyGeoAreas = $emergency->getGeoAreas();

            //Array with persons of the given PI's
            $personsTmp = $em->getRepository('AppBundle:Person')->findPersonsWithPersonAddressesForPiIdArray($personListMode, $piIdArray, $emergencyId, $exactPolygonMatchMode, $emergencyGeoAreas);

            $personsGroupedByPI = array();

        } else { //hasOnlyRoleRescueWorker = false

            //Array with persons of the given PI's
            $personsTmp = $em->getRepository('AppBundle:Person')->findPersonsWithPersonAddressesForPiIdArray($personListMode, $piIdArray, $emergencyId);

            $personsGroupedByPI = array();
        }

        //build array with persons grouped by their potential identities
        /** @var Person $person */
        foreach ($personsTmp as $index => $person) {
            $piHelperId = $person["piHelperId"];
            if (!isset($personsGroupedByPI[$piHelperId])) {
                $personsGroupedByPI[$piHelperId] = array();
            }
            $personsGroupedByPI[$piHelperId][] = array(
                "person" => $person[0],
                "piHelperId" => $person["piHelperId"]
            );
        }

        $arrayForDisplay = array();

        foreach ($personsArray as $index => $person) {

            $piHelperId = $person["piHelperId"];

            //If piHelperId is negative, then this is the negative ID of the person
            //This is needed to distinguish the person-ids from the pi-ids
            if ($piHelperId <=-1) {
                //don't touch that person entity
                $arrayForDisplay[] =
                    array(
                        array (
                            "person" => $person[0],
                            "piHelperId" => $piHelperId
                        )
                    );
            } else {
                //remove the entity and replace with with the list in $personsGroupedByPI
                $arrayForDisplay[] = $personsGroupedByPI[$piHelperId];
            }
        }
        return $arrayForDisplay;
    }

    private function getUniquePotentialIdentityIdsFromPersonArray($personArray) {

        $piArray = array();

        foreach ($personArray as $person) {
            /** @var Person $person */
            $person = $person[0];

            if ($person->getPotentialIdentity()) {
                $piArray[] = $person->getPotentialIdentity()->getId();
            }
        }

        array_unique($piArray);
        return $piArray;

    }
}
