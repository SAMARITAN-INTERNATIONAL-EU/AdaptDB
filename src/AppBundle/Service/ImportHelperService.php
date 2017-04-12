<?php
namespace AppBundle\Service;

use AppBundle\Entity\Emergency;
use AppBundle\Entity\EmergencyPersonSafetyStatus;
use AppBundle\Entity\GeoPoint;
use Doctrine\ORM\EntityManager;
use AppBundle\Entity\DataSource;
use AppBundle\Entity\Person;
use AppBundle\Entity\ContactPerson;
use AppBundle\Entity\Address;
use AppBundle\Entity\Import;
use AppBundle\Entity\ImportWarning;
use CrEOF\Spatial\PHP\Types\Geometry\Point;

/**
 * Class ImportHelperService
 * @package AppBundle\Service
 */
class ImportHelperService
{
    private $chs;
    private $em;

    /**
     * Creates EmergencySaftetyStatus-entities for a (created) person
     *
     * @param Person $person
     * @param $allEmergencies
     */
    public function addEmergencySaftetyStatusForNewPerson(Person $person, $allEmergencies)
    {

        /** @var Emergency $emergency */
        foreach ($allEmergencies as $emergency) {

            /** @var EmergencyPersonSafetyStatus $newEmergencyPersonSafetyStatus */
            $newEmergencyPersonSafetyStatus = new EmergencyPersonSafetyStatus();
            $newEmergencyPersonSafetyStatus->setEmergency($emergency);
            $newEmergencyPersonSafetyStatus->setPerson($person);
            $newEmergencyPersonSafetyStatus->setSafetyStatus(false);
            $this->em->persist($newEmergencyPersonSafetyStatus);
        }
    }


    /**
     * Constructor
     */
    public function __construct(EntityManager $em, CompareHelperService $compareHelperService)
    {
        $this->em = $em;
        $this->chs = $compareHelperService;
    }

    public function validateDate($date, $format = 'd.m.Y')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }

    private function setHasImportablePerson(&$importObject, &$errorRow, $requiredPersonFields) {

        $emptyFields = [];

        //Checks all array Keys in importObject is they are empty
        $hasImportablePerson = true;
        foreach ($requiredPersonFields as $requiredPersonField) {
            if (empty($importObject[$requiredPersonField])) {
                //Add name of empty colums to array to show it in the message later
                $emptyFields[] = $requiredPersonField;
                $hasImportablePerson = false;
            }
        }

        switch (count($emptyFields)) {
            case 0:
                //do nothing
                break;
            case 1:
                $errorRow[] = "Person could not be imported because the value for this field was missing: " . $emptyFields[0] . ".";
                break;
            default:
                $errorRow[] = "Person could not be imported because the values for this fields were missing: " . implode(", ", $emptyFields). ".";
                break;
        }

        $importObject[ImportObject::hasImportablePerson] = $hasImportablePerson;
    }

    public function getPersonIdentificationString($importObject) {

        $tmpArray = [];

        if (!empty($importObject[ImportObject::firstName])) {
            $tmpArray[] = $importObject[ImportObject::firstName];
        }

        if (!empty($importObject[ImportObject::lastName])) {
            $tmpArray[] = $importObject[ImportObject::lastName];
        }

        return implode(", ", $tmpArray);

    }

    private function setHasImportableGeoCoordinates(&$importObject, &$errorRow) {

        $latSet = !empty($importObject[ImportObject::latitude]);
        $lngSet = !empty($importObject[ImportObject::longitude]);

        if ($latSet && $lngSet) {
            $importObject[ImportObject::hasImportableGeoCoordinates] =
                ($this->isLegalLatitude($importObject[ImportObject::latitude]) && $this->isLegalLongitude($importObject[ImportObject::longitude]));

            if (!$importObject[ImportObject::hasImportableGeoCoordinates]) {
                $errorRow[] = "GeoCoordinate is not valid. Values with more than 6 digits after the dot are not accepted.";
            }
        } else { //If longitude and latitude (or one of them) is empty

            $importObject[ImportObject::hasImportableGeoCoordinates] = false;

            if ($latSet || $lngSet) {
                $errorRow[] = "GeoCoordinate is not valid because only one value is set.";
            }
        }
    }

    private function isLegalLongitude($corrdinateString) {
        //Pattern is not perfect because it allows 199 and -199
        $longitudePattern = '/^-?[10]?\d{1,2}.\d{1,6}$/'; //-180 to 180
        return preg_match($longitudePattern, $corrdinateString);
    }

    private function isLegalLatitude($corrdinateString) {
        //Pattern is not perfect because it allows 99 and -99
        $latitudePattern = '/^-?[1-9]?\d.\d{1,6}$/'; // -90 to 90
        return preg_match($latitudePattern, $corrdinateString);
    }

    private function setHasImportableAddress(&$importObject, &$errorRow) {
        $requiredAddressFields = [
            ImportObject::streetName, ImportObject::streetNo, ImportObject::zipcode, ImportObject::city, ImportObject::countryName
        ];

        $emptyFields = [];

        //Checks all array Keys in importObject is they are empty
        $hasImportableAddress = true;
        foreach ($requiredAddressFields as $requiredAddressField) {
            if (empty($importObject[$requiredAddressField])) {
                //Add name of empty colums to array to show it in the message later
                $emptyFields[] = $requiredAddressField;
                $hasImportableAddress = false;
            }
        }

        switch (count($emptyFields)) {
            case 0:
                //do nothing
                break;
            case 1:
                $errorRow[] = "Address could not be imported because the values for this field is missing: " . implode(", ", $emptyFields). ".";
                break;
            default:
                $errorRow[] = "Address could not be imported because the values for this fields were missing: " . implode(", ", $emptyFields). ".";
                break;
        }

        $importObject[ImportObject::hasImportableAddress] = $hasImportableAddress;
    }

    private function setHasImportableContactPerson(&$importObject, &$errorRow) {
        $requiredAddressFields = [ImportObject::cpFirstName, ImportObject::cpLastName];

        $emptyFields = [];

        //Checks all array Keys in importObject is they are empty
        $hasImportableContactPerson = true;
        foreach ($requiredAddressFields as $requiredContactPersonField) {
            if (empty($importObject[$requiredContactPersonField])) {
                //Add name of empty colums to array to show it in the message later
                $emptyFields[] = $requiredContactPersonField;
                $hasImportableContactPerson = false;
            }
        }

        switch (count($emptyFields)) {
            case 0:
                //do nothing
                break;
            case 1:
                $errorRow[] = "Contact Person could not be imported because the values for this field is missing: " . implode(", ", $emptyFields). ".";
                break;
            default:
                $errorRow[] = "Contact Person could not be imported because the values for this fields were missing: " . implode(", ", $emptyFields). ".";
                break;
        }

        $importObject[ImportObject::hasImportableContactPerson] = $hasImportableContactPerson;
    }

    /**
     * Method to check the csv-file before importing
     * @return array with keys "importObjects" and "errors"
     */
    public function checkCsvFileToImport($csvFilename, $requiredFieldsForPersonImport) {

        $validRequiredFields = [
            ImportObject::firstName, ImportObject::lastName, ImportObject::fiscalCode, ImportObject::dateOfBirth,
            ImportObject::email, ImportObject::gender
        ];

        //Checks if the array $requiredFieldsForPersonImport contains legal values
        $diffArray = array_diff($requiredFieldsForPersonImport, $validRequiredFields);
        if (count($diffArray) >= 1 ) {
            echo('Invalid fields in parameter "required_fields_for_person_import. -"');
            echo("Allowed fields are: " . implode(",", $validRequiredFields));
            die();
        }

        $importObjects = array();

        $countriesTmp = $this->em->getRepository('AppBundle:Country')->findAll();
        $countries = array();

        foreach ($countriesTmp as $country) {
            $countries[$country->getName()] = $country;
        }

        $transportRequirementsTmp = $this->em->getRepository('AppBundle:TransportRequirement')->findAll();
        $transportRequirements = array();

        /** @var TransportRequirement $transportRequirement */
        foreach ($transportRequirementsTmp as $transportRequirement) {
            $transportRequirements[$transportRequirement->getId()] = $transportRequirement;
        }

        $medicalRequirementsTmp = $this->em->getRepository('AppBundle:MedicalRequirement')->findAll();
        $medicalRequirements = array();

        /** @var MedicalRequirement $medicalRequirement */
        foreach ($medicalRequirementsTmp as $medicalRequirement) {
            $medicalRequirements[$medicalRequirement->getId()] = $medicalRequirement;
        }

        $vulnerabilityLevelsTmp = $this->em->getRepository('AppBundle:VulnerabilityLevel')->findAll();
        $vulnerabilityLevels = array();

        /** @var VulnerabilityLevel $vulnerabilityLevel */
        foreach ($vulnerabilityLevelsTmp as $vulnerabilityLevel) {
            $vulnerabilityLevels[$vulnerabilityLevel->getId()] = $vulnerabilityLevel;
        }

        $errorsArray = array();

        $row = 1;
        $numberOfImportablePersonsInCSVFile = 0;
        $numberOfNotImportablePersonsInCSVFile = 0;

        if (($handle = fopen($csvFilename, "r", ",")) !== FALSE) {
            while (($csvRow = fgetcsv($handle, 0, ",")) !== FALSE) {

                //The first row will always be skipped, because it contains the headers
                if ($row >= 2) {
                    $errorsArray[$row] = array();

                    //Fill the fields of an temporary importObject
                    $importObject = null;
                    $importObject[ImportObject::errorString] = "";
                    $importObject[ImportObject::row] = $row;

                    $csvRowNumberOfKeys = 25;

                    if (count($csvRow) < $csvRowNumberOfKeys) {
                        $errorsArray[$row][] = "Skipping this row because it doesn't contain the required " . $csvRowNumberOfKeys . " columns.";
                        $importObject[ImportObject::hasImportablePerson] = false;
                    } else {
                        $importObject[ImportObject::firstName] = $csvRow[0];
                        $importObject[ImportObject::lastName] = $csvRow[1];
                        $importObject[ImportObject::fiscalCode] = $csvRow[2];
                        $importObject[ImportObject::dateOfBirth] = $csvRow[3];
                        $importObject[ImportObject::landlinePhone] = $csvRow[4];
                        $importObject[ImportObject::cellPhone] = $csvRow[5];
                        $importObject[ImportObject::gender] = $csvRow[6];
                        $importObject[ImportObject::email] = $csvRow[7];
                        $importObject[ImportObject::remarks] = $csvRow[8];
                        $importObject[ImportObject::transportRequirementIds] = explode("||", $csvRow[9]);
                        $importObject[ImportObject::medicalRequirementIds] = explode("||", $csvRow[10]);
                        $importObject[ImportObject::vulnerabilityLevelId] = $csvRow[11];
                        $importObject[ImportObject::streetName] = $csvRow[12];
                        $importObject[ImportObject::streetNo] = $csvRow[13];
                        $importObject[ImportObject::zipcode] = $csvRow[14];
                        $importObject[ImportObject::city] = $csvRow[15];
                        $importObject[ImportObject::countryName] = $csvRow[16];
                        $importObject[ImportObject::adRemarks] = $csvRow[17];
                        $importObject[ImportObject::floor] = $csvRow[18];
                        $importObject[ImportObject::latitude] = $csvRow[19];
                        $importObject[ImportObject::longitude] = $csvRow[20];

                        $importObject[ImportObject::cpFirstName] = $csvRow[21];
                        $importObject[ImportObject::cpLastName] = $csvRow[22];
                        $importObject[ImportObject::cpPhone] = $csvRow[23];
                        $importObject[ImportObject::cpRemarks] = $csvRow[24];

                        $this->setHasImportableGeoCoordinates($importObject, $errorsArray[$row]);

                        $this->setHasImportablePerson($importObject, $errorsArray[$row], $requiredFieldsForPersonImport);

                        $this->setHasImportableAddress($importObject, $errorsArray[$row]);

                        $this->setHasImportableContactPerson($importObject, $errorsArray[$row]);

                        //check email
                        $email = $importObject[ImportObject::email];
                        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $this->addToErrorsArray($errorsArray[$row], $importObject[ImportObject::email], "Email", "is no valid email-address");
                        }

                        if (!empty($importObject[ImportObject::dateOfBirth]) && !$this->validateDate($importObject[ImportObject::dateOfBirth])) {
                            $this->addToErrorsArray($errorsArray[$row], $importObject[ImportObject::dateOfBirth], "Date Of Birth", "is no valid date.");
                        }

                        if (!($importObject[ImportObject::gender] == "male" || $importObject[ImportObject::gender] == "female")) {
                            $this->addToErrorsArray($errorsArray[$row], $importObject[ImportObject::gender], "Gender", "is no valid gender value");
                        }

                        if (!empty($importObject[ImportObject::countryName])) {
                            if (!isset($countries[$importObject[ImportObject::countryName]])) {
                                $this->addToErrorsArray($errorsArray[$row], $importObject[ImportObject::countryName], "Country", "could not be matched with entry in the database");
                            } else {
                                $importObject['countryId'] = $countries[$importObject[ImportObject::countryName]]->getId();
                            }
                        }

                        $importObject[ImportObject::transportRequirements] = array();
                        foreach ($importObject[ImportObject::transportRequirementIds] as $transportRequirement) {
                            if (!empty($transportRequirement)) {

                                if (!isset($transportRequirements[$transportRequirement])) {
                                    $this->addToErrorsArray($errorsArray[$row], $transportRequirement, "Transport Requirements", "could not be matched with entry in the database");
                                } else {
                                    $importObject[ImportObject::transportRequirements][] = $transportRequirements[$transportRequirement];
                                }
                            }
                        }

                        $importObject[ImportObject::medicalRequirements] = array();
                        foreach ($importObject[ImportObject::medicalRequirementIds] as $medicalRequirement) {
                            if (!empty($medicalRequirement)) {
                                if (!isset($medicalRequirements[$medicalRequirement])) {
                                    $this->addToErrorsArray($errorsArray[$row], $medicalRequirement, "Medical Requirements", "could not be matched with entry in the database");
                                } else {
                                    $importObject[ImportObject::medicalRequirements][] = $medicalRequirements[$medicalRequirement];
                                }
                            }
                        }

//                        $importObject[ImportObject::vulnerabilityLevel] = array();
                        if (!empty($importObject[ImportObject::vulnerabilityLevelId])) {
                            if (!isset($vulnerabilityLevels[$importObject[ImportObject::vulnerabilityLevelId]])) {
                                $this->addToErrorsArray($errorsArray[$row], $importObject[ImportObject::vulnerabilityLevelId], "Vulnerability Level", "could not be matched with entry in the database", $row);
                            } else {
                                $importObject[ImportObject::vulnerabilityLevel] = $vulnerabilityLevels[$importObject[ImportObject::vulnerabilityLevelId]];
                            }
                        }
                    }

                    $importObject[ImportObject::errorString] = implode(", ", $errorsArray[$row]);
                    $importObjects[] = $importObject;

                    //Removes the array-key $row again from errorsArray when there was no error
                    //This allows the frontend to decide if there was an error by checking
                    // errors | length == 0
                    if (count($errorsArray[$row]) == 0) {
                        unset($errorsArray[$row]);
                        $numberOfImportablePersonsInCSVFile++;
                    } else {
                        $numberOfNotImportablePersonsInCSVFile++;
                    }
                }
                $row++;
            }
        }

        return [
            "importObjects" => $importObjects,
            "errors" => $errorsArray,
            "numberOfImportablePersonsInCSVFile" => $numberOfImportablePersonsInCSVFile,
            "numberOfNotImportablePersonsInCSVFile" => $numberOfNotImportablePersonsInCSVFile
        ];
    }

    /**
     * Adds entry to errorsArray with default text, and it adds an string to $importObject["errorString"]
     *
     * @param $errorsArray
     */
    public function addToErrorsArray(&$errorsArrayOfRow, $entityString, $entityName, $reasonString) {
        $errorsArrayOfRow[] = sprintf('%s - "%s" %s', $entityName, $entityString, $reasonString);
    }

    public function buildAddressArrayFromImportObject($importObject)
    {
        $addressArray = array();
        $addressArray["street"] = array();
        $addressArray["street"]["name"] = $importObject[ImportObject::streetName];
        $addressArray["street"]["zipcode"] = array();
        $addressArray["street"]["zipcode"]["zipcode"] = $importObject[ImportObject::zipcode];
        $addressArray["street"]["zipcode"]["city"] = $importObject[ImportObject::city];
        $addressArray["street"]["zipcode"]["country"] = $importObject[ImportObject::countryId];
        $addressArray["houseNr"] = $importObject[ImportObject::streetNo];
        return $addressArray;
    }

    public function addressOfImportObjectCanBeImported($importObject) {
        return (
            !empty($importObject[ImportObject::streetName]) &&
            !empty($importObject[ImportObject::zipcode]) &&
            !empty($importObject[ImportObject::city]) &&
            !empty($importObject[ImportObject::countryId]) &&
            !empty($importObject[ImportObject::streetNo])
        );
    }

    public function persistImportWarning(&$importWarnings, Import $import, $message, Person $person = null) {

        $newImportWarning = new ImportWarning($import, $message, $person);
        $this->em->persist($newImportWarning);
        $importWarnings[] = $newImportWarning;

    }

    public function setCoordinatesForAddress (Address $address, $importObject) {

        $newGeoPoint = new GeoPoint();
        $newGeoPoint->setLat($importObject[ImportObject::latitude]);
        $newGeoPoint->setLng($importObject[ImportObject::longitude]);
        $newGeoPoint->setPoint(new Point($importObject[ImportObject::latitude], $importObject[ImportObject::latitude]));
        $this->em->persist($newGeoPoint);
        $address->setGeopoint($newGeoPoint);
    
    }

    public function createFindPersonArray(DataSource $dataSource, $importKeyColumns, $importObject) {
        //Create findArray from the KeyColumns of the selected DataSource
        $findArray = ["dataSource" => $dataSource];

        /** @var ImportKeyColumn $keyColumn */
        foreach ($importKeyColumns as $keyColumn) {

            if ($importObject[$keyColumn->getImportObjectName()] != "") {

                switch ($keyColumn->getName()) {
                    case "Date of Birth":
                        $findArray[$keyColumn->getDqlName()] = new \DateTime($importObject[$keyColumn->getImportObjectName()]);
                        break;
                    case "Gender":
                        $genderString = $importObject[$keyColumn->getImportObjectName()];
                        if ($genderString == "male") {
                            $findArray[$keyColumn->getDqlName()] = 1;
                        } else {
                            $findArray[$keyColumn->getDqlName()] = 0;
                        }
                        break;
                    default:
                        $findArray[$keyColumn->getDqlName()] = $importObject[$keyColumn->getImportObjectName()];
                        break;
                }
            }
        }

        return $findArray;
    }

    public function setContactPersonProperties(ContactPerson $contactPerson, $importObject, $person) {
        $contactPerson->setFirstName($importObject[ImportObject::cpFirstName]);
        $contactPerson->setLastName($importObject[ImportObject::cpLastName]);
        $contactPerson->setPhone($importObject[ImportObject::cpPhone]);
        $contactPerson->setRemarks($importObject[ImportObject::cpRemarks]);
        $contactPerson->setPerson($person);
        return $contactPerson;
    }

    public function setPersonProperties(Person $person, $importObject, DataSource $dataSource) {

        $person->setFirstName($importObject[ImportObject::firstName]);
        $person->setLastName($importObject[ImportObject::lastName]);
        $person->setFiscalCode($importObject[ImportObject::fiscalCode]);
        $person->setLandlinePhone($importObject[ImportObject::landlinePhone]);
        $person->setCellPhone($importObject[ImportObject::cellPhone]);

        if (!empty($importObject[ImportObject::dateOfBirth])) {
            $person->setDateOfBirth(new \DateTime($importObject[ImportObject::dateOfBirth]));
        } else {
            $person->setDateOfBirth(null);
        }

        $person->setGenderMale($importObject[ImportObject::gender] == "male");
        $person->setEmail($importObject[ImportObject::email]);
        $person->setRemarks($importObject[ImportObject::remarks]);
        //This fetches an entity known to the entityMananger
        //The object that was in $importObject[ImportObject::vulnerabilityLevel] may be unknown
        $importObject[ImportObject::vulnerabilityLevel] = $this->em->merge($importObject[ImportObject::vulnerabilityLevel]);
        $person->setVulnerabilityLevel($importObject[ImportObject::vulnerabilityLevel]);

        //Person is already in database
        if ($person->getId() >= 1) {

            if ($this->chs->getComparisonStringForRequirementsArray($importObject[ImportObject::transportRequirements]) !=
                $this->chs->getComparisonStringForRequirementsArray($person->getTransportRequirements())) {
                //remove all TransportRequirements and insert the new ones
                foreach ($person->getTransportRequirements() as $existingTransportRequirement) {
                    $person->removeTransportRequirement($existingTransportRequirement);
                }

                foreach ($importObject[ImportObject::transportRequirements] as $newTransportRequirement) {
                    $newTransportRequirement =  $this->em->merge($newTransportRequirement);
                    $person->addTransportRequirement($newTransportRequirement);
                }
            }

            if ($this->chs->getComparisonStringForRequirementsArray($importObject[ImportObject::medicalRequirements]) !=
                $this->chs->getComparisonStringForRequirementsArray($person->getMedicalRequirements())) {

                //remove all MedicalRequirements and insert the new ones
                foreach ($person->getMedicalRequirements() as $existingMedicalRequirement) {
                    $person->removeMedicalRequirement($existingMedicalRequirement);
                }

                foreach ($importObject[ImportObject::medicalRequirements] as $newMedicalRequirement) {
                    $newMedicalRequirement =  $this->em->merge($newMedicalRequirement);
                    $person->addMedicalRequirement($newMedicalRequirement);
                }
            }
        } else { //Person is not yet in database - no comparison with data in database is needed
            foreach ($importObject[ImportObject::transportRequirements] as $transportRequirement) {
                $transportRequirement = $this->em->merge($transportRequirement);
                $person->addTransportRequirement($transportRequirement);
            }

            foreach ($importObject[ImportObject::medicalRequirements] as $medicalRequirement) {
                $medicalRequirement = $this->em->merge($medicalRequirement);
                $person->addMedicalRequirement($medicalRequirement);
            }
        }

        $person->setDataSource($dataSource);

    }
}
