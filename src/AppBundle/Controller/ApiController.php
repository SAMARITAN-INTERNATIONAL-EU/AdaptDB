<?php

namespace AppBundle\Controller;

use AppBundle\Entity\ApiKey;
use AppBundle\Entity\AuthToken;
use AppBundle\Entity\ContactPerson;
use AppBundle\Entity\Country;
use AppBundle\Entity\DataSource;
use AppBundle\Entity\Emergency;
use AppBundle\Entity\EmergencyPersonSafetyStatus;
use AppBundle\Entity\GeoArea;
use AppBundle\Entity\GeoPoint;
use AppBundle\Entity\MedicalRequirement;
use AppBundle\Entity\Person;
use AppBundle\Entity\PersonAddress;
use AppBundle\Entity\PotentialIdentity;
use AppBundle\Entity\Street;
use AppBundle\Entity\Address;
use AppBundle\Entity\TransportRequirement;
use AppBundle\Entity\User;
use AppBundle\Entity\VulnerabilityLevel;
use AppBundle\Entity\Zipcode;
use AppBundle\Service\ApiHelperService;
use AppBundle\Service\RoleHelperService;
use Doctrine\ORM\EntityManager;
use FOS\UserBundle\Doctrine\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Response;


/**
 * Address controller.
 *
 * @package AppBundle\Controller
 */
class ApiController extends FOSRestController
{

    /** @var ApiHelperService $apiHelperService */
    private $apiHelperService;

    /** @var EntityManager $em */
    private $em;

    /** @var array $parameters */
    private $parameters;

    /** @var RoleHelperService $roleHelperService */
    private $roleHelperService;

    /** @var string $version */
    private $version;

    /** @var array $validVersionsArray */
    private $validVersionsArray;

    /**
     * Sets class variables
     * It should also be possible to do this with injecting the services and setting them in the construct method
     */
    private function init(ParamFetcherInterface $paramFetcher) {
        $this->parameters = array_filter($paramFetcher->all(true));
        $this->em = $this->getDoctrine()->getManager();
        $this->apiHelperService = $this->get('app.api_helper_service');
        $this->roleHelperService = $this->get('app.role_helper_service');
        $this->version = isset($this->parameters['version']) ? $this->parameters['version'] : "";
        $this->validVersionsArray = ["1"];
    }

    /**
     * Shortcut-function to call apiHelperService->authCheck with the default parameters
     */
    private function authCheck() {
        return $this->apiHelperService->authCheck($this->em, $this->parameters, $this->roleHelperService);
    }

    /**
     * Before accessing any other route a login is required. The login returns an Auth-Token (string)
     * that is needed for any request that is send later. After one hour of inactivity the Auth-Token becomes
     * invalid and a new login is required.
     * The credentials of Adapt DB and an API-Key is required to successfully authenticate.
     *
     * @ApiDoc()
     * @param ParamFetcherInterface $paramFetcher Parameter fetcher
     * @Rest\Get("api/login.{_format}", defaults={"_format"="json"})
     * @Method("POST")
     * @Rest\QueryParam(name="api_key", nullable=false, description="Valid API-Key for the given username",)
     * @Rest\QueryParam(name="username", nullable=false, description="Adapt DB username",)
     * @Rest\QueryParam(name="password", nullable=false, description="Password for the given username",)
     * @Rest\QueryParam(name="version", nullable=false, description="Parameter to select the version of the API")
     * @Rest\View()
     * @return Response
     */
    public function loginAction(ParamFetcherInterface $paramFetcher)
    {

        $this->init($paramFetcher);

        $apiKey = isset($this->parameters['api_key']) ? $this->parameters['api_key'] : "";
        $username = isset($this->parameters['username']) ? $this->parameters['username'] : "";
        $password = isset($this->parameters['password']) ? $this->parameters['password'] : "";

        //Check if version is valid
        if (!in_array($this->version, $this->validVersionsArray)) {
            return $this->apiHelperService->getJsonResponseFromData(array(
                'status' => 'error',
                'message' => "The requested version is not available."
            ));
        }

        if (empty($apiKey) || empty($username) || empty($password)) {
            $message = "Login not successful";
        } else {
            $this->em = $this->getDoctrine()->getManager();

            //Check username and password
            /** @var UserManager $user_manager */
            $user_manager = $this->get('fos_user.user_manager');
            $factory = $this->get('security.encoder_factory');

            //Pre-Check if a user with the username exists
            //The check beneath ($user_manager->loadUserByUsername($username) ) redirects to an other url when
            //the user does not exist in the database - This is why the user needs to be fetched here
            $userFromDatabase1 = $this->em->getRepository('AppBundle:User')->findOneBy(array('username' => $username));

            if ($userFromDatabase1) {

                //This call redirects to the login-page when $username is wrong
                $userFromDatabase2 = $user_manager->findUserBy(array("username" => $username));

                $passwordCorrect = false;

                if ($userFromDatabase2) {
                    $encoder = $factory->getEncoder($userFromDatabase2);

                    $passwordCorrect = $encoder->isPasswordValid($userFromDatabase2->getPassword(), $password, $userFromDatabase2->getSalt());
                }
            } else {
                //no user with $username in database
                $message = "Login not successful";
            }

            //Check if ApiKey is correct
            $apiKeyFromDatabase = $this->em->getRepository('AppBundle:ApiKey')->findOneBy(array('apiKey' => $apiKey));

            if ($apiKeyFromDatabase && $userFromDatabase2 && $passwordCorrect && ($apiKeyFromDatabase->getUser() == $userFromDatabase1)) {
                //generate authToken
                $authToken = new AuthToken();

                $apiKeyHelperService = $this->get('app.api_helper_service');
                $authToken->setToken($apiKeyHelperService->generateAuthToken());
                $authToken->setApiKey($apiKeyFromDatabase);

                $generatedDate =  new \DateTime();
                $authToken->setGenerated($generatedDate);

                //Clone $generatedDate to have the exact same timestamp again
                $exceedsDate = clone($generatedDate);
                $lastUsageDate = clone($generatedDate);

                // Add an interval to the date to get the $exceedsDate date
                // Add one hour
                $exceedInterval = new \DateInterval('PT1H');
                $exceedsDate->add($exceedInterval);

                $authToken->setExceeds($exceedsDate);
                $authToken->setLastUsage($lastUsageDate);

                //save authToken
                $this->em->persist($authToken);
                $this->em->flush();

//                return $this->apiHelperService->getJsonResponseFromData($authToken, array(AuthToken::DATA_GROUP));
                return $this->apiHelperService->getJsonResponseFromData($authToken,
                    array(AuthToken::DATA_GROUP),
                    $this->version
                );

//                return $this->apiHelperService->getJsonResponseFromData($persons, array("idOnly","Default", DataSource::DATA_GROUP));
            } else {
                //API Key not found in database
                $message = "Login not successful";
            }
        }

        return $this->apiHelperService->getJsonResponseFromData(array(
            'status' => 'error',
            'message' => $message
        ));
    }



    /**
     * A list of all zip codes and cities in the database is provided.
     *
     * @ApiDoc()
     * @Rest\Get("api/getZipCodesAndCities.json")
     * @param ParamFetcherInterface $paramFetcher Parameter fetcher
     * @Method("POST")
     * @Rest\QueryParam(name="api_key", nullable=false, description="Valid API-Key for Adapt DB.")
     * @Rest\QueryParam(name="auth_token", nullable=false, description="Valid Auth-Token. Use login to get the AuthToken.")
     * @Rest\QueryParam(name="version", nullable=false, description="Parameter to select the version of the API")
     * @Rest\View()
     * @return Response
     */
    public function getZipCodesAndCities(ParamFetcherInterface $paramFetcher)
    {
        $this->init($paramFetcher);

        //Checks if combination of api_key and auth_token are valid and AuthToken is not expired
        if (($message = $this->authCheck()) != "") {
            return $this->apiHelperService->getJsonResponseFromData(array(
                'status' => 'error',
                'message' => $message,
            ));
        }

        //Check if version is valid
        if (!in_array($this->version, $this->validVersionsArray)) {
            return $this->apiHelperService->getJsonResponseFromData(array(
                'status' => 'error',
                'message' => "The requested version is not available."
            ));
        }

        $zipcodes = $this->em->getRepository('AppBundle:Zipcode')->findAll();

        return $this->apiHelperService->getJsonResponseFromData($zipcodes,
            array(Zipcode::ID_GROUP,
                Zipcode::DATA_GROUP,
                Country::ID_GROUP),
            $this->version
        );
    }

    /**
     * Returns a list of all streets for a given zipCode.
     *
     * @ApiDoc()
     * @param ParamFetcherInterface $paramFetcher Parameter fetcher
     * @Rest\Get("api/getStreetsByZipCode.json")
     * @Method("POST")
     * @Rest\QueryParam(name="api_key", nullable=false, description="Valid API-Key for Adapt DB.")
     * @Rest\QueryParam(name="auth_token", nullable=false, description="Valid Auth-Token. Use login to get the AuthToken.")
     * @Rest\QueryParam(name="version", nullable=false, description="Parameter to select the version of the API")
     * @Rest\QueryParam(name="zip_code_id", nullable=false, description="Id of the zipCode to get the streets for")
     * @Rest\View()
     * @return Response
     */
    public function getStreetsByZipCode(ParamFetcherInterface $paramFetcher)
    {
        $this->init($paramFetcher);

        //Checks if combination of api_key and auth_token are valid and AuthToken is not expired
        if (($message = $this->authCheck()) != "") {
            return $this->apiHelperService->getJsonResponseFromData(array(
                'status' => 'error',
                'message' => $message,
            ));
        }

        //Check if version is valid
        if (!in_array($this->version, $this->validVersionsArray)) {
            return $this->apiHelperService->getJsonResponseFromData(array(
                'status' => 'error',
                'message' => "The requested version is not available."
            ));
        }

        $zipcodeId = isset($this->parameters['zip_code_id']) ? $this->parameters['zip_code_id'] : "";

        if (intVal($zipcodeId) < 1 || !preg_match("/^[0-9]{1,10}$/", $zipcodeId)) {
            return $this->apiHelperService->getJsonResponseFromData(array(
                'status' => 'error',
                'message' => "No valid value for parameter zip_code_id found.",
            ));
        }

        $streets = $this->em->getRepository('AppBundle:Street')->findBy(array('zipcode' => $zipcodeId));

        return $this->apiHelperService->getJsonResponseFromData($streets,
            array(Street::ID_GROUP, Street::DATA_GROUP),
            $this->version
        );
    }

    /**
     * To allow users to search for streets in Adapt DB this route will return an array of matching streets.
     * This route allows the application to implement an autocomplete-mechanism, where the user can type a part
     * of a street name and the application shows a list of matching streets in Adapt DB. The street can be
     * used later to search people by a list of streets.
     *
     * @ApiDoc()
     * @param ParamFetcherInterface $paramFetcher Parameter fetcher
     * @Rest\Get("api/findStreetsByName.json")
     * @Method("POST")
     * @Rest\QueryParam(name="api_key", nullable=false, description="Valid API-Key for Adapt DB.")
     * @Rest\QueryParam(name="auth_token", nullable=false, description="Valid Auth-Token. Use login to get the AuthToken.")
     * @Rest\QueryParam(name="version", nullable=false, description="Parameter to select the version of the API")
     * @Rest\QueryParam(name="query", nullable=false, description="QueryString that is used to query the streets table. Query may contain the wildcard character *.")
     * @Rest\View()
     * @return Response
     */
    public function findStreetsByName(ParamFetcherInterface $paramFetcher)
    {
        $this->init($paramFetcher);

        //Checks if combination of api_key and auth_token are valid and AuthToken is not expired
        if (($message = $this->authCheck()) != "") {
            return $this->apiHelperService->getJsonResponseFromData(array(
                'status' => 'error',
                'message' => $message,
            ));
        }

        //Check if version is valid
        if (!in_array($this->version, $this->validVersionsArray)) {
            return $this->apiHelperService->getJsonResponseFromData(array(
                'status' => 'error',
                'message' => "The requested version is not available."
            ));
        }

        $query = isset($this->parameters['query']) ? $this->parameters['query'] : "";

        //Wildcards are replaced in the repository-function
        $streets = $this->em->getRepository('AppBundle:Street')->findStreetsByName($query);

        return $this->apiHelperService->getJsonResponseFromData($streets,
            array(Street::ID_GROUP, Street::DATA_GROUP),
            $this->version
        );

    }

    /**
     * This route will return a list will all emergencies in Adapt DB.
     * With an optional parameter it can be defined if only the active emergencies should be returned.
     *
     * @ApiDoc()
     * @Rest\Get("api/getEmergencies.json")
     * @param ParamFetcherInterface $paramFetcher Parameter fetcher
     * @Method("POST")
     * @Rest\QueryParam(name="api_key", nullable=false, description="Valid API-Key for Adapt DB.")
     * @Rest\QueryParam(name="auth_token", nullable=false, description="Valid Auth-Token. Use login to get the AuthToken.")
     * @Rest\QueryParam(name="version", nullable=false, description="Parameter to select the version of the API")
     * @Rest\QueryParam(name="only_active", nullable=false, description="Determines if only active emergencies should be returned. Valid values: 'true', 'false'")
     * @Rest\View()
     * @return Response
     */
    public function getEmergenciesAction(ParamFetcherInterface $paramFetcher)
    {
        $this->init($paramFetcher);

        //Checks if combination of api_key and auth_token are valid and AuthToken is not expired
        if (($message = $this->authCheck()) != "") {
            return $this->apiHelperService->getJsonResponseFromData(array(
                'status' => 'error',
                'message' => $message,
            ));
        }

        //Check if version is valid
        if (!in_array($this->version, $this->validVersionsArray)) {
            return $this->apiHelperService->getJsonResponseFromData(array(
                'status' => 'error',
                'message' => "The requested version is not available."
            ));
        }

        //Caution: != null does not work here!
        if (is_null($onlyActive = $this->apiHelperService->getBooleanParameter($this->parameters, "only_active"))) {
            return $this->apiHelperService->getJsonResponseFromData(array(
                'status' => 'error',
                'message' => "Parameter only_active must exist with value true or false",
            ));
        }

        if ($onlyActive == true) {
            $emergencies = $this->em->getRepository('AppBundle:Emergency')->findBy(array("isActive" => true));
        } else {
            $emergencies = $this->em->getRepository('AppBundle:Emergency')->findAll();
        }

        return $this->apiHelperService->getJsonResponseFromData(
            $emergencies,
            array(Emergency::ID_GROUP, Emergency::DATA_GROUP,GeoArea::DATA_GROUP, GeoPoint::DATA_GROUP),
            $this->version
        );
    }

    /**
     * Persons also can be retrieved from the API based on a list of streets.
     * Users with only the Rescue Worker role only can query a subset of the emergency street list of the requested emergency
     *
     * @ApiDoc()
     * @param ParamFetcherInterface $paramFetcher Parameter fetcher
     * @Rest\Get("api/getPersonsByStreetList.json")
     * @Method("POST")
     * @Rest\QueryParam(name="api_key", nullable=false, description="Valid API-Key for Adapt DB.")
     * @Rest\QueryParam(name="auth_token", nullable=false, description="Valid Auth-Token. Use login to get the AuthToken.")
     * @Rest\QueryParam(name="version", nullable=false, description="Parameter to select the version of the API")
     * @Rest\QueryParam(name="street_ids", nullable=false, description="A comma separated list of street Ids.")
     * @Rest\QueryParam(name="emergency_id", nullable=true, description="If an emergencyId is given the safety-statuses are included for this emergency. ")
     * @Rest\View()
     * @return Response
     */
    public function getPersonsByStreetList(ParamFetcherInterface $paramFetcher)
    {
        $this->init($paramFetcher);

        //Checks if combination of api_key and auth_token are valid and AuthToken is not expired
        if (($message = $this->authCheck()) != "") {
            return $this->apiHelperService->getJsonResponseFromData(array(
                'status' => 'error',
                'message' => $message,
            ));
        }

        /** @var ApiKey $apiKeyFromDatabase */
        $apiKeyFromDatabase = $this->em->getRepository('AppBundle:ApiKey')->findOneBy(array('apiKey' => $this->parameters['api_key']));

        /** @var User $user */
        $user = $apiKeyFromDatabase->getUser();

        $emergencyId = (isset($this->parameters["emergency_id"])) ? $this->parameters["emergency_id"]: null;

        $streetIdsString = isset($this->parameters['street_ids']) ? $this->parameters['street_ids'] : "";

        if (mb_strlen($streetIdsString) < 1) {
            return $this->apiHelperService->getJsonResponseFromData(array(
                'status' => 'error',
                'message' => "No valid value for street_ids found.",
            ));
        }

        $streetIdsArray = explode(",", $streetIdsString);

        if ($user->hasOnlyRoleRescueWorker()) {

            if (!$emergencyId) {
                return $this->apiHelperService->getJsonResponseFromData(array(
                    'status' => 'error',
                    'message' => "Your user account only allows accessing data for the emergency street list. Please provide an emergency_id.",
                ));
            }

            /** @var Emergency $emergency */
            $emergency = $this->em->getRepository("AppBundle:Emergency")->find($emergencyId);

            // get Emergency Street List
            $emergencyStreetList = $emergency->getStreets();

            $emergencyStreetIdsArray = [];
            /** @var Street $street */
            foreach ($emergencyStreetList as $street) {
                $emergencyStreetIdsArray[] =  $street->getId();
            }

            //To prevent that Rescue Workers can access information about more streets than defined in the emergency street list
            $streetIdsArray = array_intersect($emergencyStreetIdsArray, $streetIdsArray);

        } else {  // hasOnlyRoleRescueWorker = false
            //Check if version is valid
            if (!in_array($this->version, $this->validVersionsArray)) {
                return $this->apiHelperService->getJsonResponseFromData(array(
                    'status' => 'error',
                    'message' => "The requested version is not available."
                ));
            }
        }

        $persons = $this->em->getRepository('AppBundle:Person')->findPersonsByStreetList($streetIdsArray, $emergencyId);

        $groupsArray = array(
            Person::ID_GROUP,
            Person::DATA_GROUP,
            MedicalRequirement::ID_GROUP,
            TransportRequirement::ID_GROUP,
            VulnerabilityLevel::ID_GROUP,
            ContactPerson::DATA_GROUP,
            PersonAddress::DATA_GROUP,
            Address::DATA_GROUP,
            PotentialIdentity::ID_GROUP,
            Street::ID_GROUP,
            Street::DATA_GROUP,
            GeoPoint::DATA_GROUP,
            DataSource::ID_GROUP
        );

        if ($emergencyId) {
            $groupsArray[] = Person::SAFETY_STATUS_GROUP;
        }

        return $this->apiHelperService->getJsonResponseFromData($persons, $groupsArray, $this->version);
    }

    /**
     * Requesting all persons within one rectangle. The rectangle is specified by passing the top left and the bottom
     * right coordinate. The result will be an array of all persons that have an address within the given rectangle.
     *
     * Users with only the Rescue Worker rule need to pass the emergency_id parameter
     * The results are always filtered by the emergency polygon to prevent that rescue workers can request all information
     *
     * @ApiDoc()
     * @Rest\Get("api/getPersonsByRectangle.json")
     * @param ParamFetcherInterface $paramFetcher Parameter fetcher
     * @Method("POST")
     * @Rest\QueryParam(name="api_key", nullable=false, description="Valid API-Key for Adapt DB.")
     * @Rest\QueryParam(name="auth_token", nullable=false, description="Valid Auth-Token. Use login to get the AuthToken.")
     * @Rest\QueryParam(name="version", nullable=false, description="Parameter to select the version of the API")
     * @Rest\QueryParam(name="geopoint_1_lat", nullable=false, description="Latitude value for the top left coordinate.")
     * @Rest\QueryParam(name="geopoint_1_lng", nullable=false, description="Longitude value for the top left coordinate.")
     * @Rest\QueryParam(name="geopoint_2_lat", nullable=false, description="Latitude value for the bottom right coordinate.")
     * @Rest\QueryParam(name="geopoint_2_lng", nullable=false, description="Longitude value for the bottom right coordinate.")
     * @Rest\QueryParam(name="emergency_id", nullable=true, description="If an emergencyId is given the safety-statuses are included for this emergency. ")
     * @Rest\View()
     * @return Response
     */
    public function getPersonsByRectangle(ParamFetcherInterface $paramFetcher)
    {
        $this->init($paramFetcher);

        //Checks if combination of api_key and auth_token are valid and AuthToken is not expired
        if (($message = $this->authCheck()) != "") {
            return $this->apiHelperService->getJsonResponseFromData(array(
                'status' => 'error',
                'message' => $message,
            ));
        }

        //Check if version is valid
        if (!in_array($this->version, $this->validVersionsArray)) {
            return $this->apiHelperService->getJsonResponseFromData(array(
                'status' => 'error',
                'message' => "The requested version is not available."
            ));
        }

        $geopoint1Lat = $this->apiHelperService->getDecimalParameter($this->parameters, "geopoint_1_lat");
        $geopoint1Lng = $this->apiHelperService->getDecimalParameter($this->parameters, "geopoint_1_lng");
        $geopoint2Lat = $this->apiHelperService->getDecimalParameter($this->parameters, "geopoint_2_lat");
        $geopoint2Lng = $this->apiHelperService->getDecimalParameter($this->parameters, "geopoint_2_lng");

        $emergencyId = (isset($this->parameters["emergency_id"])) ? $this->parameters["emergency_id"]: null;

        /** @var Emergency $emergency */
        $emergency = $this->em->getRepository("AppBundle:Emergency")->find($emergencyId);

        if (!$emergency) {
            return $this->apiHelperService->getJsonResponseFromData(array(
                'status' => 'error',
                'message' => "Emergency not found."
            ));
        }

        /** @var ApiKey $apiKeyFromDatabase */
        $apiKeyFromDatabase = $this->em->getRepository('AppBundle:ApiKey')->findOneBy(array('apiKey' => $this->parameters['api_key']));

        /** @var User $user */
        $user = $apiKeyFromDatabase->getUser();

        if ($user->hasOnlyRoleRescueWorker()) {
            $exactPolygonMatchMode = $this->container->getParameter("exact_polygon_match_mode");
            $emergencyGeoAreas = $emergency->getGeoAreas();
            $persons = $this->em->getRepository('AppBundle:Person')->findPeopleInRectangle($geopoint1Lat, $geopoint1Lng, $geopoint2Lat, $geopoint2Lng, $emergencyId, $exactPolygonMatchMode, $emergencyGeoAreas);
        } else { // hasOnlyRoleRescueWorker == false
            $persons = $this->em->getRepository('AppBundle:Person')->findPeopleInRectangle($geopoint1Lat, $geopoint1Lng, $geopoint2Lat, $geopoint2Lng, $emergencyId);
        }

        $groupsArray =  array(
            Person::ID_GROUP,
            Person::DATA_GROUP,
            MedicalRequirement::ID_GROUP,
            TransportRequirement::ID_GROUP,
            VulnerabilityLevel::ID_GROUP,
            ContactPerson::DATA_GROUP,
            PersonAddress::DATA_GROUP,
            Address::DATA_GROUP,
            PotentialIdentity::ID_GROUP,
            Street::ID_GROUP,
            Street::DATA_GROUP,
            GeoPoint::DATA_GROUP,
            DataSource::ID_GROUP);

        if ($emergencyId) {
            $groupsArray[] = Person::SAFETY_STATUS_GROUP;
        }

        return $this->apiHelperService->getJsonResponseFromData($persons,
            $groupsArray,
            $this->version
        );

    }

    /**
     * To allow applications to modify the safety-state of a person the API provides a way to change it.
     * A safety-status is defined in context of an emergency.
     *
     * @ApiDoc()
     * @Rest\Get("api/setSafetyStatus")
     * @param ParamFetcherInterface $paramFetcher Parameter fetcher
     * @Method("PUT")
     * @Rest\QueryParam(name="api_key", nullable=false, description="Valid API-Key for Adapt DB.")
     * @Rest\QueryParam(name="auth_token", nullable=false, description="Valid Auth-Token. Use login to get the AuthToken.")
     * @Rest\QueryParam(name="version", nullable=false, description="Parameter to select the version of the API")
     * @Rest\QueryParam(name="person_id", nullable=false, description="The Id of the Person where the safetyStatus should be changed.")
     * @Rest\QueryParam(name="emergency_id", nullable=false, description="The safetyStatus is saved in context of an emergency. Pass the Id of the emergency.")
     * @Rest\QueryParam(name="safety_status", nullable=false, description="New value for safetyStatus. Valid values: 'safe', 'unsafe'")
     * @Rest\View()
     * @return Response
     */
    public function setSafetyStatus(ParamFetcherInterface $paramFetcher)
    {
        $this->init($paramFetcher);

        //Checks if combination of api_key and auth_token are valid and AuthToken is not expired
        if (($message = $this->authCheck()) != "") {
            return $this->apiHelperService->getJsonResponseFromData(array(
                'status' => 'error',
                'message' => $message,
            ));
        }

        //Check if version is valid
        if (!in_array($this->version, $this->validVersionsArray)) {
            return $this->apiHelperService->getJsonResponseFromData(array(
                'status' => 'error',
                'message' => "The requested version is not available."
            ));
        }

        $personId = isset($this->parameters['person_id']) ? intval($this->parameters['person_id']) : -1;
        $emergencyId = isset($this->parameters['emergency_id']) ? intval($this->parameters['emergency_id']) : -1;
        $newSafetyStatus = isset($this->parameters['safety_status']) ? mb_strtolower($this->parameters['safety_status']) : -1;

        /** @var Emergency $emergency */
        $emergency = $this->em->getRepository("AppBundle:Emergency")->find($emergencyId);

        if (!$emergency) {
            return $this->apiHelperService->getJsonResponseFromData(array(
                'status' => 'error',
                'message' => "Emergency not found."
            ));
        }

        if ($emergency->getIsActive() == false) {
            return $this->apiHelperService->getJsonResponseFromData(array(
                'status' => 'error',
                'message' => "It is not possible to change the safety-status for an inactive emergency."
            ));
        }

        if ($personId >= 0 && $emergencyId >= 0 && ($newSafetyStatus == "safe" || $newSafetyStatus == "unsafe")) {

            $emergencySafetyStatus = $this->em->getRepository('AppBundle:EmergencyPersonSafetyStatus')->findOneBy(array('person' => $personId, 'emergency' => $emergencyId));

            if ($emergencySafetyStatus) {

                if ($newSafetyStatus == 'safe') {
                    $emergencySafetyStatus->setSafetyStatus(1);
                } else {
                    //This works because there is a check before if value is "safe" or "unsafe"
                    $emergencySafetyStatus->setSafetyStatus(0);
                }

                $this->em->flush();

            } else {
                $message = "person_id or emergency_id not valid";
                $returnArrayserialized = array(
                    'status' => 'error',
                    'message' => $message,
                );
                return $this->apiHelperService->getJsonResponseFromData($returnArrayserialized);
            }
        } else {
            $message = "person_id or emergency_id not valid or invalid value for safety_status";
            $returnArrayserialized = array(
                'status' => 'error',
                'message' => $message,
            );
            return $this->apiHelperService->getJsonResponseFromData($returnArrayserialized);
        }
        $returnArrayserialized = array(
            'status' => 'success',
        );
        return $this->apiHelperService->getJsonResponseFromData($returnArrayserialized);

    }

    /**
     * Returns a list of medicalRequirements in Adapt DB. This is needed to map the output of the country-field
     * in a zipCode record.
     *
     * @ApiDoc()
     * @param ParamFetcherInterface $paramFetcher Parameter fetcher
     * @Rest\Get("api/getCountries.json")
     * @Method("POST")
     * @Rest\QueryParam(name="api_key", nullable=false, description="Valid API-Key for Adapt DB.")
     * @Rest\QueryParam(name="auth_token", nullable=false, description="Valid Auth-Token. Use login to get the AuthToken.")
     * @Rest\QueryParam(name="version", nullable=false, description="Parameter to select the version of the API")
     * @Rest\View()
     * @return Response
     */
    public function getCountries(ParamFetcherInterface $paramFetcher)
    {
        $this->init($paramFetcher);

        //Checks if combination of api_key and auth_token are valid and AuthToken is not expired
        if (($message = $this->authCheck()) != "") {
            return $this->apiHelperService->getJsonResponseFromData(array(
                'status' => 'error',
                'message' => $message,
            ));
        }

        //Check if version is valid
        if (!in_array($this->version, $this->validVersionsArray)) {
            return $this->apiHelperService->getJsonResponseFromData(array(
                'status' => 'error',
                'message' => "The requested version is not available."
            ));
        }

        $countries = $this->em->getRepository('AppBundle:Country')->findAll();

        return $this->apiHelperService->getJsonResponseFromData(
            $countries,
            array(Country::ID_GROUP, Country::DATA_GROUP),
            $this->version
        );
    }

    /**
     * @param ParamFetcherInterface $paramFetcher Parameter fetcher
     * @Rest\Get("api/getVulnerabilityLevels.json")
     * @Method("POST")
     * @Rest\QueryParam(name="api_key", nullable=false, description="Valid API-Key for Adapt DB.")
     * @Rest\QueryParam(name="auth_token", nullable=false, description="Valid Auth-Token. Use login to get the AuthToken.")
     * @Rest\QueryParam(name="version", nullable=false, description="Parameter to select the version of the API")
     * @Rest\View()
     * @return Response
     */
    public function getVulnerabilityLevels(ParamFetcherInterface $paramFetcher)
    {
        $this->init($paramFetcher);

        //Checks if combination of api_key and auth_token are valid and AuthToken is not expired
        if (($message = $this->authCheck()) != "") {
            $this->apiHelperService->getJsonResponseFromData(array(
                'status' => 'error',
                'message' => $message,
            ));
        }

        $vulnerabilityLevels = $this->em->getRepository('AppBundle:VulnerabilityLevel')->findAll();

        return $this->apiHelperService->getJsonResponseFromData(
            $vulnerabilityLevels,
            array(VulnerabilityLevel::ID_GROUP, VulnerabilityLevel::DATA_GROUP),
            $this->version
        );
    }

    /**
     * Returns a list of medicalRequirements in Adapt DB. This is needed to map the output of the medicalRequirements
     * in a person record.
     *
     * @ApiDoc()
     * @param ParamFetcherInterface $paramFetcher Parameter fetcher
     * @Rest\Get("api/getMedicalRequirements.json")
     * @Method("POST")
     * @Rest\QueryParam(name="api_key", nullable=false, description="Valid API-Key for Adapt DB.")
     * @Rest\QueryParam(name="auth_token", nullable=false, description="Valid Auth-Token. Use login to get the AuthToken.")
     * @Rest\QueryParam(name="version", nullable=false, description="Parameter to select the version of the API")
     * @Rest\View()
     * @return Response
     */
    public function getMedicalRequirements(ParamFetcherInterface $paramFetcher)
    {
        $this->init($paramFetcher);

        //Checks if combination of api_key and auth_token are valid and AuthToken is not expired
        if (($message = $this->authCheck()) != "") {
            return $this->apiHelperService->getJsonResponseFromData(array(
                'status' => 'error',
                'message' => $message,
            ));
        }

        //Check if version is valid
        if (!in_array($this->version, $this->validVersionsArray)) {
            return $this->apiHelperService->getJsonResponseFromData(array(
                'status' => 'error',
                'message' => "The requested version is not available."
            ));
        }

        $medicalRequirements = $this->em->getRepository('AppBundle:MedicalRequirement')->findAll();

        return $this->apiHelperService->getJsonResponseFromData(
            $medicalRequirements,
            array(
                MedicalRequirement::ID_GROUP,
                MedicalRequirement::DATA_GROUP),
            $this->version
        );
    }

    /**
     * Returns a list of medicalRequirements in Adapt DB. This is needed to map the output of the medicalRequirements
     * in a person record.
     *
     * @ApiDoc()
     * @param ParamFetcherInterface $paramFetcher Parameter fetcher
     * @Rest\Get("api/getDataSources.json")
     * @Method("POST")
     * @Rest\QueryParam(name="api_key", nullable=false, description="Valid API-Key for Adapt DB.")
     * @Rest\QueryParam(name="auth_token", nullable=false, description="Valid Auth-Token. Use login to get the AuthToken.")
     * @Rest\QueryParam(name="version", nullable=false, description="Parameter to select the version of the API")
     * @Rest\View()
     * @return Response
     */
    public function getDataSources(ParamFetcherInterface $paramFetcher)
    {
        $this->init($paramFetcher);

        //Checks if combination of api_key and auth_token are valid and AuthToken is not expired
        if (($message = $this->authCheck()) != "") {
            return $this->apiHelperService->getJsonResponseFromData(array(
                'status' => 'error',
                'message' => $message,
            ));
        }

        //Check if version is valid
        if (!in_array($this->version, $this->validVersionsArray)) {
            return $this->apiHelperService->getJsonResponseFromData(array(
                'status' => 'error',
                'message' => "The requested version is not available."
            ));
        }

        $dataSources = $this->em->getRepository('AppBundle:DataSource')->findAll();

        return $this->apiHelperService->getJsonResponseFromData(
            $dataSources,
            array(
                DataSource::ID_GROUP,
                DataSource::DATA_GROUP),
            $this->version
        );
    }

    /**
     * Returns a list of medicalRequirements in Adapt DB. This is needed to map the output of the medicalRequirements
     * in a person record.
     *
     * @ApiDoc()
     * @param ParamFetcherInterface $paramFetcher Parameter fetcher
     * @Rest\Get("api/getTransportRequirements.json")
     * @Method("POST")
     * @Rest\QueryParam(name="api_key", nullable=false, description="Valid API-Key for Adapt DB.")
     * @Rest\QueryParam(name="auth_token", nullable=false, description="Valid Auth-Token. Use login to get the AuthToken.")
     * @Rest\QueryParam(name="version", nullable=false, description="Parameter to select the version of the API")
     * @Rest\View()
     * @return Response
     */
    public function getTransportRequirements(ParamFetcherInterface $paramFetcher)
    {
        $this->init($paramFetcher);

        //Checks if combination of api_key and auth_token are valid and AuthToken is not expired
        if (($message = $this->authCheck()) != "") {
            return $this->apiHelperService->getJsonResponseFromData(array(
                'status' => 'error',
                'message' => $message,
            ));
        }

        //Check if version is valid
        if (!in_array($this->version, $this->validVersionsArray)) {
            return $this->apiHelperService->getJsonResponseFromData(array(
                'status' => 'error',
                'message' => "The requested version is not available."
            ));
        }

        $transportRequirements = $this->em->getRepository('AppBundle:TransportRequirement')->findAll();

        return $this->apiHelperService->getJsonResponseFromData(
            $transportRequirements,
            array(TransportRequirement::ID_GROUP, TransportRequirement::DATA_GROUP),
            $this->version
        );
    }
}
