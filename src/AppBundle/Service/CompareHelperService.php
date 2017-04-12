<?php
namespace AppBundle\Service;

use AppBundle\Entity\PersonAddress;
use AppBundle\Entity\ContactPerson;
use AppBundle\Entity\Address;

/**
 * Class CompareHelperService
 * @package AppBundle\Service
 */
class CompareHelperService
{

    public function getComparisonStringForContactPersonFromDatabase(ContactPerson $contactPerson) {
        return $contactPerson->getFirstName().
            $contactPerson->getLastName() .
            $contactPerson->getPhone() .
            $contactPerson->getRemarks();
    }

    public function getComparisonStringForContactPersonFromImportObject($importObject) {
        return $importObject[ImportObject::cpFirstName].
            $importObject[ImportObject::cpLastName].
            $importObject[ImportObject::cellPhone].
            $importObject[ImportObject::cpRemarks];
    }

    //Can be used for arrays with MedicalRequirements or TransportRequirements
    public function getComparisonStringForRequirementsArray($requirementsArray) {
        $idsArray = array();
        foreach ($requirementsArray as $requirement) {
            $idsArray[] = $requirement->getId();
        }

        //Sortierung ist wichtig, damit die Reihenfolge gleich ist
        sort($idsArray, SORT_NUMERIC);

        return implode(",", $idsArray);
    }

    public function getAddressStringForComparisonFromImportObject($importObject)
    {
        return $importObject[ImportObject::streetName] .
            $importObject[ImportObject::streetNo] .
            $importObject[ImportObject::zipcode] .
            $importObject[ImportObject::city];
    }

    public function getAddressStringForComparisonFromAddress(Address $address)
    {
        return $address->getStreet()->getName() .
            $address->getHouseNr() .
            $address->getStreet()->getZipcode()->getZipcode() .
            $address->getStreet()->getZipcode()->getCity();
    }

    public function getComparisonStringForPersonAddressFromDatabase(PersonAddress $personAddress) {
            return $personAddress->getAddressDumpForComparison();
    }
}
