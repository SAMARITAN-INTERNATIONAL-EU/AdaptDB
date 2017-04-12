<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Emergency;
use AppBundle\Entity\EmergencyPersonSafetyStatus;
use AppBundle\Service\CompareHelperService;
use AppBundle\Service\GeocoderService;
use AppBundle\Service\PersistAddressHelperService;
use Doctrine\ORM\EntityManager;

use AppBundle\Entity\ContactPerson;
use AppBundle\Entity\DataChangeHistory;
use AppBundle\Entity\DataSource;
use AppBundle\Entity\GeoPoint;
use AppBundle\Entity\Import;
use AppBundle\Entity\ImportKeyColumn;
use AppBundle\Entity\ImportWarning;
use AppBundle\Entity\MedicalRequirement;
use AppBundle\Entity\PersonAddress;
use AppBundle\Entity\PersonMissingInDataSource;
use AppBundle\Entity\TransportRequirement;
use AppBundle\Entity\VulnerabilityLevel;
use AppBundle\Entity\Zipcode;
use AppBundle\Entity\Address;
use AppBundle\Entity\Person;
use AppBundle\Service\ImportObject;

use AppBundle\Form\ImportStep1Type;
use AppBundle\Form\ImportStep2Type;

use AppBundle\Service\ImportHelperService;

use Nelmio\Alice\Instances\Processor\Methods\Faker;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * Import controller.
 *
 * @package AppBundle\Controller
 * @Route("/import")
 * @Security("has_role('ROLE_DATA_ADMIN')")
 */
class ImportController extends Controller
{

    /** @var  EntityManager $em */
    private $em;

    /** @var CompareHelperService $chs */
    private $chs;

    /** @var ImportHelperService $ihs */
    private $ihs;

    const CSV_ARRAY_SEPARATOR = "||";

    private $importWarnings = array();

    private function init() {
        $this->ihs = $this->get("app.import_helper_service");
        $this->chs = $this->get("app.compare_helper_service");
        $this->em = $this->getDoctrine()->getManager();
    }

    /**
     * Lists all Address entities.
     *
     * @Route("/generateTextCSVImportFile/{rows}", name="generateTextCSVImportFile")
     * @Method("GET")
     */
    public function generateTextCSVImportFileAction($rows =  10) {

        /** @var Faker faker */
        $this->faker = \Faker\Factory::create();

        $fakeDataArray = array(
            //name in header || quoted || value
            [ImportObject::firstName, true, function () {return $this->faker->optional($weight = 0.95)->firstName;}],
            [ImportObject::lastName, true, function () {return $this->faker->optional($weight = 0.95)->lastName;}],
            [ImportObject::fiscalCode, true, function () {return $this->faker->optional($weight = 0.95)->regexify('[A-Z0-9]{1,10}');}],
            [ImportObject::dateOfBirth, true, function () {return $this->faker->optional($weight = 0.7)->date($format = 'd.m.Y', $max = 'now');}],
            [ImportObject::landlinePhone, true, function () {return $this->faker->optional()->phoneNumber;}],
            [ImportObject::cellPhone, true, function () {return $this->faker->optional()->phoneNumber;}],
            [ImportObject::gender, true, function () {return $this->faker->optional($weight = 0.9)->randomElement($array = array ('male','female'));}],
            [ImportObject::email, true, function () {return $this->faker->optional()->safeEmail;}],
            [ImportObject::remarks, true, function () {return $this->faker->optional()->text($maxNbChars = 200);}],
            [ImportObject::transportRequirements, false, function () {return $this->faker->randomElements($array = array (1,2,3), $count = 2);}],
            [ImportObject::medicalRequirements, false, function () {return $this->faker->randomElements($array = array (1,2,3), $count = 2);}],
            [ImportObject::vulnerabilityLevel, false, function () {return $this->faker->randomElement($array = array (1,2,3));}],
            [ImportObject::streetName, true, function () {return $this->faker->optional($weight = 0.98)->streetName;}],
            [ImportObject::streetNo, true, function () {return $this->faker->optional($weight = 0.98)->numberBetween($min = 1, $max = 1500);}],
            [ImportObject::zipcode, true, function () {return $this->faker->optional($weight = 0.98)->postcode;}],
            [ImportObject::city, true, function () {return $this->faker->optional($weight = 0.98)->city;}],
            [ImportObject::countryName, true, function () {return $this->faker->randomElement($array = array ("Germany", "Italy", "Denmark"));}],
            [ImportObject::adRemarks, true, function () {return $this->faker->optional()->text($maxNbChars = 200);}],
            [ImportObject::floor, true, function () {return $this->faker->optional()->numberBetween($min = 1, $max = 15);}],
            [ImportObject::latitude, false, function () {return $this->faker->latitude($min = -90, $max = 90);}],
            [ImportObject::longitude, false, function () {return $this->faker->longitude($min = -180, $max = 180);}],
            [ImportObject::cpFirstName, true, function () {return $this->faker->firstName;}],
            [ImportObject::cpLastName, true, function () {return $this->faker->lastName;}],
            [ImportObject::cpPhone, true, function () {return $this->faker->optional()->phoneNumber;}],
            [ImportObject::cpRemarks, true, function () {return $this->faker->optional()->text($maxNbChars = 200);}],
        );

        for ($row = 0; $row<=$rows; $row++) {
            $csvRow = array();

            if ($row == 0) {
                //Write headline
                foreach ($fakeDataArray as $fakeDataItem) {
                    //For the header-row
                    $csvRow[$fakeDataItem[0]] = $fakeDataItem[0];
                }
            } else {
                foreach ($fakeDataArray as $fakeDataItem) {
                    if ($fakeDataItem[0] == ImportObject::medicalRequirements || $fakeDataItem[0] == ImportObject::transportRequirements) {
                        $dataToSet = implode(self::CSV_ARRAY_SEPARATOR, $fakeDataItem[2]());
                        $csvRow[$fakeDataItem[0]] = $dataToSet;
                    } else {
                        $dataToSet = $fakeDataItem[2]();
                        if ($fakeDataItem[1] == true) {
                            $csvRow[$fakeDataItem[0]] = $this->quoteString($dataToSet);
                        } else {
                            $csvRow[$fakeDataItem[0]] = $dataToSet;
                        }
                    }
                }
            }

            if (rand(1,$rows/2) == 1) {
                $this->removeOneField($csvRow);
            }

            if (rand(1,$rows/2) == 1) {
                $this->addRandomError($csvRow);
            }

            $csvArray[] = implode(",", $csvRow);
        }

        $csvFileContent = implode(PHP_EOL, $csvArray);

        return $this->render('import/generatedCSV.html.twig', array(
            'csvFileContent' => $csvFileContent
        ));
    }

    private function addRandomError(&$csvRow) {

        $rand = rand(1,10);

        switch ($rand) {
            case 1:
                $csvRow["date_of_birth"] = $this->faker->date($format = 'Y.m.d', $max = 'now');
                break;
            case 2:
                //(probably) entities that are missing in database
//                $csvRow["transport_requirements"] = "1||6||2";
                $csvRow[ImportObject::transportRequirements] = implode(self::CSV_ARRAY_SEPARATOR, array(1,6,2));
                break;
            case 3:
                //Illegal Separator and (probably) entities that are missing in database
                $csvRow[ImportObject::medicalRequirements] = implode(self::CSV_ARRAY_SEPARATOR."|", array(1,4,5,7));
                break;
            case 4:
                //(probably) entity that are missing in database
                $csvRow[ImportObject::vulnerabilityLevel] = "5";
                break;
            case 5:
                unset($csvRow[ImportObject::latitude]);
                break;
            case 6:
                unset($csvRow[ImportObject::latitude]);
                unset($csvRow[ImportObject::longitude]);
                break;
        }
    }

    private function removeOneField(&$csvRow) {

        $randomColumn = array_keys($csvRow)[rand(0, (count(array_keys($csvRow))-1))];
        unset($csvRow[$randomColumn]);
    }

    private function  quoteString($s) {
        return '"' . $s . '"';
    }

    /**
     * Sets markedAsDone for ImportWarning to true or false
     * $done is expected to be 0 or 1
     *
     * @Route("/setImportWarningDone/{importWarningId}/{done}", name="import_set_import_warning_done")
     * @Method("GET")
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function setImportWarningDoneAction($importWarningId, $done)
    {
        $this->em = $this->getDoctrine()->getManager();

        /** @var ImportWarning $importWarning */
        $importWarning = $this->em->getRepository('AppBundle:ImportWarning')->find($importWarningId);

        if ($importWarning === null ) {
            throw new \Doctrine\ORM\EntityNotFoundException();
        }

        $importWarning->setMarkedAsDone($done);
        $this->em->persist($importWarning);
        $this->em->flush();

        return $this->redirect($this->generateUrl("import_showWarnings", array('importId' => $importWarning->getImport()->getId())));

    }

    /**
     * Sets markedAsDone for Import to true or false
     * $done is expected to be 0 or 1
     *
     * @Route("/setImportDone/{importId}/{done}", name="import_set_import_done")
     * @Method("GET")
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function setImportDoneAction($importId, $done)
    {
        $this->em = $this->getDoctrine()->getManager();

        /** @var Import $import */
        $import = $this->em->getRepository('AppBundle:Import')->find($importId);

        if ($import === null ) {
            throw new \Doctrine\ORM\EntityNotFoundException();
        }

        $import->setMarkedAsDone($done);
        $this->em->persist($import);
        $this->em->flush();

        return $this->redirect($this->generateUrl("import_importsofdatasource", array('dataSourceId' => $import->getDataSource()->getId())));

    }

    /**
     *
     *
     * @Route("/showWarnings/{importId}", name="import_showWarnings")
     * @Method("GET")
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function showWarningsAction($importId)
    {

        $this->init();

        $import = $this->em->getRepository('AppBundle:Import')->find($importId);
        $importWarnings = $this->em->getRepository('AppBundle:ImportWarning')->findBy(array("import" => $importId));

        return $this->render('import/showWarnings.html.twig', array(
                'importWarnings' => $importWarnings,
                'import' => $import
            )
        );
    }

    /**
     *
     *
     * @Route("/ImportsOfDataSource/{dataSourceId}", name="import_importsofdatasource")
     * @Method("GET")
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function indexAction($dataSourceId)
    {
        $this->init();

        $dataSource = $this->em->getRepository('AppBundle:DataSource')->find($dataSourceId);
        $imports = $this->em->getRepository('AppBundle:Import')->findBy(array("dataSource" => $dataSourceId), array("timestamp" => "DESC"));

        return $this->render('import/importsOfDataSource.html.twig', array(
                'imports' => $imports,
                'dataSource' => $dataSource)
        );
    }

    /**
     * This function is used to import a CSV file into the database
     *
     * @Route("/step1", name="import_step1")
     * @Method("GET|POST")
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function importStep1Action(Request $request)
    {
        $importStep1Form = $this->createForm('AppBundle\Form\ImportStep1Type', null);

        $importStep1Form->handleRequest($request);

        return $this->render('import/step1.html.twig', array(
                'form' => $importStep1Form->createView())
        );
    }

    /**
     * If user tries to access step2 with GET-verb -> redirect to step1
     *
     * @Route("/step2")
     * @Method("GET")
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function importStep2RedirectOnlyAction(Request $request)
    {
        return $this->redirect($this->generateUrl("import_step1"));
    }

    private function getSessionKey($csvFilename) {
        return $csvFilename."".$this->getUser()->getUsername();
    }

    /**
     * This function is used to import a CSV file into the database
     *
     * @Route("/step2", name="import_step2")
     * @Method("POST")
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function importStep2Action(Request $request)
    {
        //To have prevent a timeout while importing
        set_time_limit (0);

        $this->init();

        $numberOfDetectMissingPersons = 0;

        /** @var PersistAddressHelperService $persistAddressHelperService */
        $persistAddressHelperService = $this->get('app.persist_address_helper_service');

        /** @var GeocoderService $geocoderService */
        $geocoderService = $this->get('app.geocoder_service');

        /** @var EntityManager em */
        $this->em = $this->getDoctrine()->getManager();
        $nominatimEmailAddress = $this->container->getParameter('nominatim_email_address');

        //Create Step1Form and handleRequest to get the data
        $importStep1Form = $this->createForm('AppBundle\Form\ImportStep1Type', null);
        $importStep1Form->handleRequest($request);

        //Create Step2Form
        $importStep2Form = $this->createForm('AppBundle\Form\ImportStep2Type', null);

        /** @var Session $session */
        $session = new Session();
        //If Step1Form was submitted set Data of Step1Form to Step2Form and set default values based on dataSource
        if ($importStep1Form->isSubmitted() && $importStep1Form->isValid()) {
            /** @var UploadedFile $csvFile */
            $csvFile = $importStep1Form->get('csvFile')->getData();

            $csvClientFileName = $csvFile->getClientOriginalName();
            $tmpFolder = "../tmp/csvFiles/";
            $filenameAtNewLocation = $tmpFolder . $csvClientFileName;
            $csvFile->move($tmpFolder, $csvClientFileName);

            $importStep2Form->get("csvFilePath")->setData($filenameAtNewLocation);
            $importStep2Form->get("dataSource")->setData($importStep1Form->get('dataSource')->getData());
            $importStep2Form->get("csvClientFileName")->setData($csvClientFileName);

            /** @var DataSource $dataSource */
            $dataSource = $importStep2Form->get("dataSource")->getData();

            //Unset the session key to prevent old data from being used
            $sessionKey = $this->getSessionKey($csvClientFileName);
            $session->set($sessionKey, null);

            //Set the default values based on the Data Source
            $importStep2Form->get("enableGeocoding")->setData($dataSource->getDefaultForEnableGeocoding());
            $importStep2Form->get("automaticUpdateForClearlyIdentifiedAddresses")->setData($dataSource->getDefaultForAutomaticUpdateForClearlyIdentifiedAddresses());
            $importStep2Form->get("detectMissingPersons")->setData($dataSource->getDefaultForDetectMissingPersons());
        }

        $importStep2Form->handleRequest($request);

        if ($importStep2Form->isSubmitted() && $importStep2Form->isValid()) {
            /** @var DataSource $dataSource */
            $dataSource = $importStep2Form->get("dataSource")->getData();
        }

        //Get Persons from that dataSource to compare them later and to count them
        $personsOfDataSource = $this->em->getRepository('AppBundle:Person')->findBy(array("dataSource" => $dataSource));

        $numberOfRecentImportsToShow = $this->getParameter("number_of_recent_imports_to_show");

        $mostRecentImports = $this->em->getRepository('AppBundle:Import')->findBy(
            array("dataSource" => $dataSource),
            array("timestamp" => "DESC"),
            $numberOfRecentImportsToShow
        );

        if (($importStep1Form->isSubmitted() && $importStep1Form->isValid()) ||
            ($importStep2Form->isSubmitted() && $importStep2Form->isValid())
        ) {

            $csvFilename = $importStep2Form->get("csvFilePath")->getData();

            $csvClientFileName = $importStep2Form->get("csvClientFileName")->getData();
            $sessionKey = $this->getSessionKey($csvClientFileName);

            $requiredFieldsForPersonImport = json_decode($this->container->getParameter("required_fields_for_person_import"));

            //Use a session to save the data of the $tmp array
            //The method is called twice: In step1 after upload and before importing in step2
            if ($session->get($sessionKey) != null) {
                $tmp = $session->get($sessionKey);
            } else {
                $tmp = $this->ihs->checkCsvFileToImport($csvFilename, $requiredFieldsForPersonImport);
                $session->set($sessionKey, $tmp);
            }

            $importObjects = $tmp['importObjects'];
            $errors = $tmp['errors'];
            $numberOfImportablePersonsInCSVFile = $tmp['numberOfImportablePersonsInCSVFile'];
            $numberOfNotImportablePersonsInCSVFile = $tmp['numberOfNotImportablePersonsInCSVFile'];
        }

        if ($importStep1Form->isSubmitted() && $importStep1Form->isValid()) {

            return $this->render('import/step2.html.twig', array(
                'import_form' => $importStep2Form->createView(),
                'selectedDataSource' => $dataSource,
                'personsOfDataSourceCount' => count($personsOfDataSource),
                'mostRecentImports' => $mostRecentImports,
                'csvClientFileName' => $csvClientFileName,
                'numberOfImportablePersonsInCSVFile' => $numberOfImportablePersonsInCSVFile,
                'numberOfNotImportablePersonsInCSVFile' => $numberOfNotImportablePersonsInCSVFile,
                'errors' => $errors
            ));
        }

        if ($importStep2Form->isSubmitted() && $importStep2Form->isValid()) {

            //Get values from the form
            $detectMissingPersons = $importStep2Form->get("detectMissingPersons")->getData();
            $automaticAddressUpdate = $importStep2Form->get("automaticUpdateForClearlyIdentifiedAddresses")->getData();
            $geocodingEnabled = $importStep2Form->get("enableGeocoding")->getData();
            $useGeoPointsWhenAvailable = $importStep2Form->get("useGeoPointsWhenAvailable")->getData();

            if ($detectMissingPersons) {
                //Get Persons from that dataSource to compare them later
                $personsOfDataSource = $this->em->getRepository('AppBundle:Person')->findBy(array("dataSource" => $dataSource));

                //Create a list with the personIds
                $personIdsOfDataSource = array();
                /** @var Person $person */
                foreach ($personsOfDataSource as $person) {
                    //PersonId needs to be the key, to be able to unset it later to find out those missing in import
                    $personIdsOfDataSource[$person->getId()] = "";
                }
            }

            //Get selected DataSource value from the form
            $importKeyColumns = $dataSource->getImportKeyColumns();

            $addedOrUpdatedPersonsArray = array();

            //Add Import-entity
            $newImport = new Import();
            $newImport->setTimestamp(new \DateTime());
            $newImport->setDataSource($dataSource);
            $newImport->setFilename($csvClientFileName);
            $this->em->persist($newImport);

            $allEmergencies = $this->em->getRepository('AppBundle:Emergency')->findAll();

            foreach ($importObjects as $importObject) {
                if (!empty($importObject['errorString'])) {

                    $personIdentificationString = $this->ihs->getPersonIdentificationString($importObject);
                    $stringForImportWarning = !empty($personIdentificationString) ? " (" . $personIdentificationString . ") " : "";
//                    $stringForImportWarning = $personIdentificationString;

                    $this->ihs->persistImportWarning($this->importWarnings,
                        $newImport,
                        "Person in row " . $importObject['row'] . " " . $stringForImportWarning . "was not imported: " . $importObject['errorString']);
                } else {

                    if ($importObject['hasImportablePerson']) {

                        //Array with comments that is shown in the frontend after the import
                        $addOrUpdatedCommentArray = array();

                        $addedOrUpdatedPersonTmp = array();

                        $findPersonArray = $this->ihs->createFindPersonArray($dataSource, $importKeyColumns, $importObject);

                        //search for already existing person in the database
                        /** @var Person $personInDatabase */
                        $personInDatabase = $this->em->getRepository('AppBundle:Person')->findOneBy($findPersonArray);

                        if ($personInDatabase) {
                            //Person with identical values in the keyColums found - > update person and address

                            if ($detectMissingPersons) {
                                //Removes the current person from $personIdsOfDataSource to generate a list with
                                //the missing persons in import afterwards
                                unset($personIdsOfDataSource[$personInDatabase->getId()]);
                            }

                            //Update person
                            $this->ihs->setPersonProperties($personInDatabase, $importObject, $dataSource);
                            $this->em->persist($personInDatabase);

                            if ($importObject[ImportObject::hasImportablePerson]) {

                                $countContactPersonsOfPersonInDatabase = count($personInDatabase->getContactPersons());

                                switch ($countContactPersonsOfPersonInDatabase) {
                                    case 0:
                                        //If person has no contact person -> add new contact person
                                        $newContactPerson = new ContactPerson();
                                        $this->ihs->setContactPersonProperties($newContactPerson, $importObject, $personInDatabase);
                                        $this->em->persist($newContactPerson);
                                        $addOrUpdatedCommentArray[] = "Contact Person was created";
                                        break;
                                    case 1:
                                        //If person has no contact person -> update this contact person
                                        $contactPerson = $personInDatabase->getContactPersons()[0];
                                        $this->ihs->setContactPersonProperties($contactPerson, $importObject, $personInDatabase);
                                        $this->em->persist($contactPerson);
                                        $addOrUpdatedCommentArray[] = "Existing Contact Person was updated";
                                        break;
                                    default: //PersonInDatabase has more than one Contact Person
                                        $shouldAddContactPerson = false;

                                        $comparisonStringForContactPersonFromCSV =
                                            $this->chs->getComparisonStringForContactPersonFromImportObject($importObject);

                                        foreach ($personInDatabase->getContactPersons() as $contactPerson) {
                                            if ($this->chs->getComparisonStringForContactPersonFromDatabase($contactPerson) ==
                                                $comparisonStringForContactPersonFromCSV
                                            ) {
                                                $shouldAddContactPerson = true;

                                                //Compare contact person from csv with the value in database
                                                $this->ihs->setContactPersonProperties($contactPerson, $importObject, $personInDatabase);
                                                $this->em->persist($contactPerson);

                                                $addOrUpdatedCommentArray[] = "Contact Person was found and updated";

                                                //Loop can be left because the Contact Person was found
                                                break;
                                            }
                                        }

                                        if ($shouldAddContactPerson == false) {
                                            $this->ihs->persistImportWarning($this->importWarnings,
                                                $newImport,
                                                "Contact Person in Import could not be matched with an address in the database.",
                                                $personInDatabase);
                                        }
                                        break;
                                }
                            }

                            if ($importObject[ImportObject::hasImportableAddress]) {

                                //Add or update address
                                //AutomaticAddressUpdate = Automatic Update for clearly identified addresses
                                if ($automaticAddressUpdate) {

                                    $personAddressesOfPersonInDatabaseCount = count($personInDatabase->getPersonAddresses());
                                    if ($personAddressesOfPersonInDatabaseCount <= 1) {
                                        //compare Address from CSV with single address from database
                                        $addressFromCSVString = $this->chs->getAddressStringForComparisonFromImportObject($importObject);

                                        if ($personAddressesOfPersonInDatabaseCount == 1) {
                                            /** @var Address $addressTmp */
                                            $addressTmp = $personInDatabase->getPersonAddresses()[0]->getAddress();

                                            $addressFromDatabaseString = $this->chs->getAddressStringForComparisonFromAddress($addressTmp);

                                            //ShouldAddAddress is true when both addresses are different
                                            //If addresses are the same the address already exists in the database and
                                            //update is not needed
                                            $shouldAddAddress = ($addressFromCSVString != $addressFromDatabaseString);
                                        } else { //PersonAddress has no addresses
                                            $shouldAddAddress = true;
                                        }

                                        if ($shouldAddAddress == true) {

                                            //Remove the old address from the list of addresses
                                            if ($personAddressesOfPersonInDatabaseCount == 1) {

                                                //Remove the connection to the old personAddress
                                                //The cronjob "cleanUpDatabase" should run regularly and removes address
                                                //entities that are not used anymore
                                                /** @var PersonAddress $personAddressToRemove */
                                                $personAddressToRemove = $personInDatabase->getPersonAddresses()[0];
                                                $personAddressToRemove->setPerson(null);
                                                $this->em->persist($personAddressToRemove);
                                            }

                                            //Create new Address and persist the changes
                                            $addressArray = $this->ihs->buildAddressArrayFromImportObject($importObject);

                                            //When geoCoordinate from import should be used, geocoding can be skipped
                                            if ($useGeoPointsWhenAvailable == true && $importObject[ImportObject::hasImportableGeoCoordinates]) {
                                                $geocodingEnabled = false;
                                            }

                                            $newAddress = $persistAddressHelperService->persistAddress($geocoderService, $this->em, $addressArray, $nominatimEmailAddress, $geocodingEnabled);

                                            if ($importObject[ImportObject::hasImportableGeoCoordinates]) {

                                                //Set GeoPoint when $useGeoPointsWhenAvailable is true
                                                if ($useGeoPointsWhenAvailable == true) {
                                                    $this->ihs->setCoordinatesForAddress($newAddress, $importObject);
                                                }

                                                //Check if geocoding did return a value - if not set GeoPoint from importfile
                                                if ($geocodingEnabled == true && $newAddress->getGeopoint()) {
                                                    $this->ihs->setCoordinatesForAddress($newAddress, $importObject);
                                                }

                                            }

                                            $newPersonAddress = new PersonAddress();
                                            $newPersonAddress->setAddress($newAddress);
                                            $newPersonAddress->setPerson($personInDatabase);
                                            $newPersonAddress->setFloor($importObject[ImportObject::floor]);
                                            $newPersonAddress->setRemarks($importObject[ImportObject::remarks]);
                                            $newPersonAddress->setIsActive(true);

                                            $this->em->persist($newPersonAddress);
                                            $this->em->persist($newAddress);

                                            $addOrUpdatedCommentArray[] = "Address has been updated";

                                        }
                                    } else { //PersonInDatabase has more than one PersonAddress

                                        $addressFromCSVFound = false;

                                        $addressFromCSVString = $this->chs->getAddressStringForComparisonFromImportObject($importObject);

                                        /** @var PersonAddress $personAddressOfPersonInDatabase */
                                        foreach ($personInDatabase->getPersonAddresses() as $personAddressOfPersonInDatabase) {
                                            $addressFromDatabaseString = $this->chs->getAddressStringForComparisonFromAddress($personAddressOfPersonInDatabase->getAddress());
                                            if ($addressFromCSVString == $addressFromDatabaseString) {
                                                $addressFromCSVFound = true;
                                                //Matching address was found - remove it and create a new PersonAddress

                                                //Replace the found address with address from CSV
                                                $addressArray = $this->ihs->buildAddressArrayFromImportObject($importObject);

                                                /** @var Address $newAddress */
                                                $newAddress = $persistAddressHelperService->persistAddress($geocoderService, $this->em, $addressArray, $nominatimEmailAddress, $geocodingEnabled);
                                                if ($geocodingEnabled == false) {
                                                    $this->ihs->setCoordinatesForAddress($newAddress, $importObject);
                                                }

                                                //When the id is the same -> the address did not change
                                                //Then just update personAddress-properties
                                                if ($personAddressOfPersonInDatabase->getAddress()->getId() == $newAddress->getId()) {
                                                    $personAddressOfPersonInDatabase->setFloor($importObject[ImportObject::floor]);
                                                    $personAddressOfPersonInDatabase->setRemarks($importObject[ImportObject::adRemarks]);
                                                    $personAddressOfPersonInDatabase->setIsActive(true);

                                                } else {
                                                    //Removes it from the PersonAddresses Array of the person
                                                    $personAddressOfPersonInDatabase->getPerson()->removePersonAddress($personAddressOfPersonInDatabase);

                                                    //Add new personAddress
                                                    $this->em->persist($personAddressOfPersonInDatabase);

                                                    /** @var PersonAddress $newPersonAddress */
                                                    $newPersonAddress = new PersonAddress();
                                                    $newPersonAddress->setAddress($newAddress);
                                                    $newPersonAddress->setPerson($personInDatabase);
                                                    $newPersonAddress->setFloor($importObject[ImportObject::floor]);
                                                    $newPersonAddress->setRemarks($importObject[ImportObject::adRemarks]);
                                                    $newPersonAddress->setIsActive(true);
                                                    $this->em->persist($newPersonAddress);
                                                }

                                                $addOrUpdatedCommentArray[] = "Address has been updated";

                                                //The address was found - The loop can be left
                                                break;
                                            }
                                        }

                                        //Add ImportWarnings when no address did match
                                        if ($addressFromCSVFound == false) {
                                            $this->ihs->persistImportWarning($this->importWarnings,
                                                $newImport,
                                                "Address in Import could not be matched an address in the database.",
                                                $personInDatabase);
                                        }
                                    }

                                } else { //$automaticAddressUpdate == false

                                    //Compare address from importObject with address from person
                                    if ($importObject[ImportObject::hasImportableAddress]) {
                                        //Add address from importObject when there was no address in the database before
                                        if (count($personInDatabase->getPersonAddresses()) == 0) {
                                            //Create new Address and persist the changes
                                            $addressArray = $this->ihs->buildAddressArrayFromImportObject($importObject);
                                            $newAddress = $persistAddressHelperService->persistAddress($geocoderService, $this->em, $addressArray, $nominatimEmailAddress, $geocodingEnabled);
                                            if ($geocodingEnabled == false) {
                                                if (($importObject[ImportObject::hasImportableGeoCoordinates])) {
                                                    $this->ihs->setCoordinatesForAddress($newAddress, $importObject);
                                                }
                                            }
                                            $newPersonAddress = new PersonAddress();
                                            $newPersonAddress->setAddress($newAddress);
                                            $newPersonAddress->setPerson($personInDatabase);
                                            $newPersonAddress->setFloor($importObject[ImportObject::floor]);
                                            $newPersonAddress->setRemarks($importObject[ImportObject::remarks]);
                                            $newPersonAddress->setIsActive(true);

                                            $this->em->persist($newPersonAddress);
                                            $this->em->persist($newAddress);
                                            $addOrUpdatedCommentArray[] = "Address was added";
                                        } else {
                                            //Check if address from import exists in the person-entity
                                            $addressFromCSVFound = false;

                                            $addressFromCSVString = $this->chs->getAddressStringForComparisonFromImportObject($importObject);

                                            /** @var PersonAddress $personAddressOfPersonInDatabase */
                                            foreach ($personInDatabase->getPersonAddresses() as $personAddressOfPersonInDatabase) {
                                                $addressFromDatabaseString = $this->chs->getAddressStringForComparisonFromAddress($personAddressOfPersonInDatabase->getAddress());
                                                if ($addressFromCSVString == $addressFromDatabaseString) {
                                                    $addressFromCSVFound = true;
                                                }
                                            }

                                            //Generate a warning if address is not in database
                                            if ($addressFromCSVFound == false) {
                                                $this->ihs->persistImportWarning($this->importWarnings,
                                                    $newImport,
                                                    "Address from import-file was not found in the addresses of this person. ",
                                                    $personInDatabase);
                                            }
                                        }
                                    } else {
                                        if (count($personInDatabase->getPersonAddresses() >=1)) {
                                            $this->ihs->persistImportWarning($this->importWarnings,
                                                $newImport,
                                                "Address from import-file could not be imported into the database. Existing address(es) of person in database were not touched.",
                                                $personInDatabase);
                                        } else {
                                            $this->ihs->persistImportWarning($this->importWarnings,
                                                $newImport,
                                                "Address from import-file could not be imported into the database.",
                                                $personInDatabase);
                                        }
                                    }
                                }
                            }

                            $addedOrUpdatedPersonTmp["person"] = $personInDatabase;

                            $addOrUpdatedCommentArray[] = "Person in database was updated";
                            $addedOrUpdatedPersonTmp["comment"] = implode(", ", $addOrUpdatedCommentArray);

                        } else { //No Person with identical values in the keyColumns found - > create a new person

                            /** @var Person $newPerson */
                            $newPerson = new Person();
                            $this->ihs->setPersonProperties($newPerson, $importObject, $dataSource);

                            if ($importObject['hasImportableAddress']) {

                                $addressArray = $this->ihs->buildAddressArrayFromImportObject($importObject);

                                //When geoCoordinate from import should be used, geocoding can be skipped
                                if ($useGeoPointsWhenAvailable == true && $importObject[ImportObject::hasImportableGeoCoordinates]) {
                                    $geocodingEnabled = false;
                                }

                                //Persist the personAddress and its related entities
                                $newAddress = $persistAddressHelperService->persistAddress($geocoderService, $this->em, $addressArray, $nominatimEmailAddress, $geocodingEnabled);

                                if ($importObject[ImportObject::hasImportableGeoCoordinates]) {

                                    //Set GeoPoint when $useGeoPointsWhenAvailable is true
                                    if ($useGeoPointsWhenAvailable == true) {
                                        $this->ihs->setCoordinatesForAddress($newAddress, $importObject);
                                    }

                                    //Check if geocoding did return a value - if not set GeoPoint from importfile
                                    if ($geocodingEnabled == true && $newAddress->getGeopoint()) {
                                        $this->ihs->setCoordinatesForAddress($newAddress, $importObject);
                                    }

                                }

                                $newPersonAddress = new PersonAddress();
                                $newPersonAddress->setAddress($newAddress);
                                $newPersonAddress->setPerson($newPerson);
                                $newPersonAddress->setIsActive(true);

                                $this->em->persist($newAddress);
                                $this->em->persist($newPersonAddress);
                            }

                            if ($importObject['hasImportableContactPerson']) {
                                $newContactPerson = new ContactPerson();
                                $this->ihs->setContactPersonProperties($newContactPerson, $importObject, $newPerson);
                                $this->em->persist($newContactPerson);
                            }

                            $this->em->persist($newPerson);

                            $addedOrUpdatedPersonTmp["person"] = $newPerson;
                            $addedOrUpdatedPersonTmp["comment"] = "Person created";
                        }

                        $addedOrUpdatedPersonsArray[] = $addedOrUpdatedPersonTmp;
                    } else { //hasImportablePerson == false
                        //Write existing warnings as ImportWarnings
                        $this->ihs->persistImportWarning($this->importWarnings,
                            $newImport,
                            $importObject[ImportObject::errorString],
                            null);
                    }
                    $this->em->flush();
                }
                $this->em->flush();
            }
			//Add EmergencyPersonSafetyStatus-entities for all emergencies in the database
            foreach ($addedOrUpdatedPersonsArray as $addedOrUpdatedPerson) {

                if ($addedOrUpdatedPerson["comment"] == "Person created") {
                    $this->ihs->addEmergencySaftetyStatusForNewPerson($addedOrUpdatedPerson["person"], $allEmergencies);
                }
            }

            $this->em->flush();

            if ($detectMissingPersons) {
                $numberOfDetectMissingPersons = count($personIdsOfDataSource);

                //Generate ImportWarnings for the missing persons in import
                foreach ($personIdsOfDataSource as $personId => $emptyString) {

                    $missingDescription = "This person was expected to be in the import - But this person was missing.";

                    /** @var Person $personInLoop */
                    $personInLoop = $this->em->getRepository('AppBundle:Person')->find($personId);
                    $this->ihs->persistImportWarning($this->importWarnings,
                        $newImport,
                        $missingDescription,
                        $personInLoop);

                    if ($dataSource->getIsOfficial() && $personInLoop->getPotentialIdentity()) {

                        //Generate messages for removed persons when DataSource isOfficial
                        //Messages are for Persons in other dataSources
                        $newPersonMissingInDataSource = new PersonMissingInDataSource;
                        $newPersonMissingInDataSource->setCreated(new \DateTime());
                        $newPersonMissingInDataSource->setDataSource($dataSource);
                        $newPersonMissingInDataSource->setPerson($personInLoop);
                        $newPersonMissingInDataSource->setPotentialIdentity($personInLoop->getPotentialIdentity());
                        $newPersonMissingInDataSource->setDescription(
                            'This person was not present in an import of official data source: "'. $dataSource->getName() .'"'
                        );

                        $this->em->persist($newPersonMissingInDataSource);
                    }
                }
            }

            $newImport->setNumberOfWarnings(count($this->importWarnings));

            $this->em->persist($newImport);
            $this->em->flush();

            return $this->render('import/success.html.twig', array(
                'addedOrUpdatedPersons' => $addedOrUpdatedPersonsArray,
                'detectMissingPersons' => $detectMissingPersons,
                'numberOfDetectMissingPersons' => $numberOfDetectMissingPersons,
                'selectedDataSource' => $dataSource,
                'personsOfDataSourceCount' => count($personsOfDataSource),
                'newImport' => $newImport,
                'importWarnings' => $this->importWarnings,
                'csvClientFileName' => $csvClientFileName
            ));

        } else {
            return $this->render('import/step2.html.twig', array(
                'import_form' => $importStep2Form->createView(),
                'selectedDataSource' => $dataSource,
                'personsOfDataSourceCount' => 0,
                'errors' => $errors,
                'csvClientFileName' => $csvClientFileName
            ));
        }
    }

    /**
     * For calling the detectAndDeletePotentialIdentitiesCommand
     *
     * @Route("/updatePotentialIdentities", name="import_updatePIs")
     * @Method("GET")
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function executeDetectAndDeletePotentialIdentitiesCommand() {

        $kernel = $this->get('kernel');
        $application = new Application($kernel);
        $application->setAutoExit(false);
        $input = new ArrayInput(array(
            'command' => 'adaptDB:detectAndDeletePotentialIdentities'
        ));

        $output = new BufferedOutput();
        $application->run($input, $output);

        $response = new JsonResponse($output->fetch());

        return $response;
    }
}
