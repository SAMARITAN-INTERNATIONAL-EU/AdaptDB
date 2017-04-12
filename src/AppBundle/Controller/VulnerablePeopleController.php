<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Address;
use AppBundle\Entity\ContactPerson;
use AppBundle\Entity\Country;
use AppBundle\Entity\DataSource;
use AppBundle\Entity\GeoPoint;
use AppBundle\Entity\GeoArea;
use AppBundle\Entity\MedicalRequirement;
use AppBundle\Entity\Person;
use AppBundle\Entity\PersonAddress;
use AppBundle\Entity\PotentialIdentity;
use AppBundle\Entity\Street;
use AppBundle\Entity\TransportRequirement;
use AppBundle\Entity\VulnerabilityLevel;
use AppBundle\Entity\Zipcode;
use AppBundle\Service\ApiHelperService;
use AppBundle\Service\NameNormalizationService;
use AppBundle\Service\PersonListFilterArray;
use AppBundle\Service\PersonListHelperService;
use AppBundle\Service\PersonListMode;
use AppBundle\Service\RoleHelperService;
use AppBundle\Service\SessionKeys;
use JMS\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use AppBundle\Entity\Emergency;
use AppBundle\Form\EmergencyType;
use AppBundle\Form\FindVulnerablePeopleType;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use JMS\SerializerBundle\JMSSerializerBundle;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Emergency controller.
 *
 * @package AppBundle\Controller
 * @Route("/findVulnerablePeople")
 */
class VulnerablePeopleController extends Controller
{
    /**
     * This action creates the find page. It provides the data needed for steps 1 and 2. The logic to switch between
     * the pages is written in Javascript.
     *
     * @Route("/forEmergency/{emergencyId}", name="find_vulnerable_people")
     * @Method("GET|POST")
     * @Security("has_role('ROLE_DATA_ADMIN') or has_role('ROLE_RESCUE_WORKER')")
     */
    public function findVulnerablePeopleAction(Request $request, $emergencyId)
    {

        $em = $this->getDoctrine()->getManager();

        /** @var Emergency $emergency */
        $emergency = $em->getRepository('AppBundle:Emergency')->find($emergencyId);

        if ($emergency === null ) {
            throw new \Doctrine\ORM\EntityNotFoundException();
        }

        /** @var RoleHelperService $roleHelperService */
        $roleHelperService = $this->get("app.role_helper_service");

        $activeEmergenciesExist = $roleHelperService->getActiveEmergenciesExist($em);

        $userHasOnlyRescueWorkerRole = $roleHelperService->getUserHasOnlyRescueWorkerRole($em, $this->getUser()->getRoles());

        //Rescue Worker tried to access Adapt with no active emergency
        if ($userHasOnlyRescueWorkerRole && !$activeEmergenciesExist) {
            return $this->render('noAccess.html.twig', array());
        }

        //Rescue Worker tried to access an emergency page where the emergency is not active anymore
        //This could happen by opening an old link or by URL-manipulation
        if ($userHasOnlyRescueWorkerRole && !$emergency->getIsActive()) {
            return $this->render('accessDenied.html.twig', array());
        }

        $form = $this->createForm('AppBundle\Form\FindVulnerablePeopleType', null, array(
            'action' => $this->generateUrl('show_vulnerable_people_results'),
            'method' => 'POST',
        ));

        $form->setData(array('emergencyId' => $emergencyId));

        if ($request) {
            $form->handleRequest($request);
        }

        $geoAreasForEmergency = $em->getRepository('AppBundle:GeoArea')->findBy(array('emergency' => $emergency));

        $geoAreasForEmergency_associativeArray = array();

        /** @var GeoArea $geoArea */
        foreach ($geoAreasForEmergency as $geoArea) {
            $geoAreasForEmergency_associativeArray[$geoArea->getId()] = $geoArea;
        }

        /** @var Session $session */
        $session = new Session();
        $session->set(SessionKeys::selectedEmergencyId, $emergencyId);

        return $this->render('vulnerablePeople/find.html.twig', array(
            'selectedEmergency' => $emergency,
            'geoAreasForEmergencyJSON' => JSON_encode($geoAreasForEmergency_associativeArray),
            'form' => $form->createView()
        ));
    }

    /**
     * Removes all geoAreas for the given EmergencyId
     * @param {int} emergencyId
     * @return bool
     */
    private function removeAllCustomGeoAreasForEmergency($emergencyId) {

        $em = $this->getDoctrine()->getManager();

        if ($emergencyId != "") {

            $geoAreas = $em->getRepository('AppBundle:GeoArea')->findAllCustomGeoAreasForEmergencyId($emergencyId);

            foreach ($geoAreas as $geoArea) {
                $geoPoints = $geoArea->getGeopoints();
                foreach ($geoPoints as $geoPoint) {
                    $em->remove($geoPoint);
                }
                $em->remove($geoArea);
            }

            $em->flush();

            return true;
        } else {
            return false;
        }
    }

    /**
     * Generates the query-string shown in the breadcrumb-bar
     *
     * @param $vulnerabilityLevels
     * @return string
     */
    private function getVulnerabilityLevelsQueryString($vulnerabilityLevels)
    {
        $queryVulnerabilityLevelArray = [];
        if ($vulnerabilityLevels && count($vulnerabilityLevels) >= 1) {
            foreach ($vulnerabilityLevels as $vulnerabilityLevel) {
                $queryVulnerabilityLevelArray[] = $vulnerabilityLevel->getName();
            }
            return implode($queryVulnerabilityLevelArray, ", ");
        }
        return "no rqmts";
    }

    /**
     * Generates the query-string shown in the breadcrumb-bar
     *
     * @param $medicalRequirements
     * @return string
     */
    private function getMedicalRequirementsQueryString($medicalRequirements)
    {
        $queryMedicalRequirementsArray = [];
        if ($medicalRequirements && count($medicalRequirements) >= 1) {
            foreach ($medicalRequirements as $medicalRequirement) {
                $queryMedicalRequirementsArray[] = $medicalRequirement->getName();
            }
            return implode($queryMedicalRequirementsArray, ", ");
        }
        return "no rqmts";
    }

    /**
     * Generates the query-string shown in the breadcrumb-bar
     *
     * @param $transportRequirements
     * @return string
     */
    private function getTransportRequirementsQueryString($transportRequirements)
    {
        $queryTransportRequirementsArray = [];
        if ($transportRequirements && count($transportRequirements) >= 1) {
            foreach ($transportRequirements as $transportRequirement) {
                $queryTransportRequirementsArray[] = $transportRequirement->getName();
            }
            return implode($queryTransportRequirementsArray, ", ");
            }
        return "no rqmts.";
    }

    /**
     * This function provides the results page. The form data is used to build the $filterArray to do the filtering
     * of the vulnerable people.
     * @Route("/results", name="show_vulnerable_people_results")
     * @param {mixed} $request
     * @Method("GET|POST")
     * @Security("has_role('ROLE_RESCUE_WORKER')")
     */
    public function showVulnerablePeopleResultsAction(Request $request) {

        /** @var Session $session */
        $session = new Session();

        //Get emergencyIds from session
        if ($session->get(SessionKeys::selectedEmergencyId) != null) {
            $emergencyId = $session->get(SessionKeys::selectedEmergencyId);

            $em = $this->getDoctrine()->getManager();

            $filterArray = [];

            $geoAreas = [];

            $customGeoAreaNamesArray = [];

            $filterForm = $this->createForm('AppBundle\Form\FindVulnerablePeopleType', null, array(
                'action' => $this->generateUrl('find_vulnerable_people', array('emergencyId' => $emergencyId)),
                'method' => 'POST',
            ));

            $emergency = $em->getRepository('AppBundle:Emergency')->find($emergencyId);

            $customGeoAreas = [];

            //If the user defined a custom polygon it will be saved to be accessible later
            //get the custom defined geoAreas and reset the fields data
            $requestObject = $request->get("find_vulnerable_people");
            $customGeoAreasArrayJSON = $requestObject["customGeoAreasArray"];

            if ($customGeoAreasArrayJSON != "") {
                $customGeoAreasArray = json_decode($customGeoAreasArrayJSON);

                //This deleted all old custom geoAreas // Prevents cluttering the database with useless data
                $this->removeAllCustomGeoAreasForEmergency($emergencyId);

                //persist the customGeoAreas
                foreach ($customGeoAreasArray as $key => $geoPoints) {
                    $newGeoArea = new GeoArea();
                    //generates an unique name
                    $customGeoAreaName = 'custom_' . time() . '_' . $key;
                    $customGeoAreaNamesArray[] = $customGeoAreaName;
                    $newGeoArea->setName($customGeoAreaName);
                    $newGeoArea->setEmergency($emergency);

                    $position = 0;
                    foreach ($geoPoints as $geoPoint) {
                        $newGeoPoint = new GeoPoint();
                        $newGeoPoint->setPosition($position);
                        $newGeoPoint->setLat($geoPoint->lat);
                        $newGeoPoint->setLng($geoPoint->lng);
                        $newGeoPoint->setGeoArea($newGeoArea);
                        $newGeoArea->addGeoPoint($newGeoPoint);
                        $em->persist($newGeoPoint);
                        $position++;
                    }
                    $em->persist($newGeoArea);
                    $customGeoAreas[] = $newGeoArea;
                }

                //persisting the custom geoAreas
                $em->flush();

                //Add the id of the newly persisted GeoAreas to the
                $activeGeoAreaIdsArray = array();
                foreach ($customGeoAreas as $customGeoArea) {
                    $activeGeoAreaIdsArray[] = $customGeoArea->getId();
                }
                $requestObject["activeGeoAreaIdsArray"] = json_encode($activeGeoAreaIdsArray);

                //reset the customGeoAreasArray-field (CustomGeoAreas are persisted now)
                $requestObject["customGeoAreasArray"] = "";

            }

            //sets the modified requestObject to the request before binding it to the form
            $request->request->set("find_vulnerable_people", $requestObject);

            $filterForm->handleRequest($request);

            if ($filterForm->isValid()) {

                if ($filterForm->has("vulnerabilityLevel")) {
                    $vulnerabilityLevels = $filterForm->get("vulnerabilityLevel")->getData();
                }

                if ($filterForm->has("medicalRequirements")) {
                    $medicalRequirements = $filterForm->get("medicalRequirements")->getData();
                }

                if ($filterForm->has("transportRequirements")) {
                    $transportRequirements = $filterForm->get("transportRequirements")->getData();
                }

                if ($filterForm->has("safetyStatus")) {
                    $safetyStatus = $filterForm->get("safetyStatus")->getData();
                }

                if ($filterForm->has("selectedStreetIds")) {
                    $selectedStreetIds = json_decode($filterForm->get("selectedStreetIds")->getData());
                }

                if ($filterForm->has("streetListIds")) {
                    $streetListIds = json_decode($filterForm->get("selectedStreetIds")->getData());
                }

                $form = $this->createForm(
                    'AppBundle\Form\FindVulnerablePeopleType',
                    array(
                        'vulnerabilityLevels' => $vulnerabilityLevels,
                        'medicalRequirements' => $medicalRequirements,
                        'transportRequirements' => $transportRequirements,
                        'safetyStatus' => $safetyStatus,
                        'selectedStreetIds' => $selectedStreetIds,
                        'streetListIds' => $streetListIds,
                    ));

                $form->handleRequest($request);

                if (strtolower($form->get('findMode')->getData()) == "map") {
                    $findMode = "Map";
                } else {
                    $findMode = "Streets";
                }

                //Only search by streets when Streets-findMode is active
                if ($findMode == "Streets") {
                    if (count($streetListIds) > 0) {
                        $filterArray['selectedStreetIds'] = $streetListIds;
                    }
                }

                if (empty($vulnerabilityLevels)) {
                    $vulnerabilityLevels = $form->get("vulnerabilityLevel")->getData();
                }
                $filterArray['vulnerabilityLevels'] = $vulnerabilityLevels;

                if (empty($medicalRequirements)) {
                    $medicalRequirements = $form->get("medicalRequirement")->getData();
                }
                $filterArray['medicalRequirements'] = $medicalRequirements;

                if (empty($transportRequirements)) {
                    $transportRequirements = $form->get("transportRequirement")->getData();
                }
                $filterArray['transportRequirements'] = $transportRequirements;

                if (empty($safetyStatus)) {
                    $safetyStatus = $form->get("safetyStatus")->getData();
                }
                $filterArray['safetyStatus'] = $safetyStatus;

                $queryVulnerabilityLevelString = $this->getVulnerabilityLevelsQueryString($vulnerabilityLevels);
                $queryMedicalRequirementsString = $this->getMedicalRequirementsQueryString($medicalRequirements);
                $queryTransportRequirementsString = $this->getTransportRequirementsQueryString($transportRequirements);

                $orderKey = $form->get('orderKey')->getData();
                $orderValue = $form->get('orderValue')->getData();
                $sortArray = [];

                if ($orderValue != "" && $orderKey != "") {
                    $sortArray[$orderKey] = $orderValue;
                }

                $filterHelperService = $this->get('app.filter_helper_service');
                $filterHelperService->addFilterArrayPropertyFromFilterForm($form, $filterArray, "queryFirstName", PersonListFilterArray::firstName);
                $filterHelperService->addFilterArrayPropertyFromFilterForm($form, $filterArray, "queryLastName", PersonListFilterArray::lastName);
                $filterHelperService->addFilterArrayPropertyFromFilterForm($form, $filterArray, "queryAge", PersonListFilterArray::age);
                $filterHelperService->addFilterArrayPropertyFromFilterForm($form, $filterArray, "queryAgeGrSm", PersonListFilterArray::ageGrSm);
                $filterHelperService->addFilterArrayPropertyFromFilterForm($form, $filterArray, "queryStreet", PersonListFilterArray::streetName);
                $filterHelperService->addFilterArrayPropertyFromFilterForm($form, $filterArray, "queryStreetNumber", PersonListFilterArray::streetNr);
                $filterHelperService->addFilterArrayPropertyFromFilterForm($form, $filterArray, "queryZip", PersonListFilterArray::zipcode);
                $filterHelperService->addFilterArrayPropertyFromFilterForm($form, $filterArray, "queryCity", PersonListFilterArray::city);
                $filterHelperService->addFilterArrayPropertyFromFilterForm($form, $filterArray, "queryFloor", PersonListFilterArray::floor);
                $filterHelperService->addFilterArrayPropertyFromFilterForm($form, $filterArray, "queryFloorGrSm", PersonListFilterArray::floorGrSm);
                $filterHelperService->addFilterArrayPropertyFromFilterForm($form, $filterArray, "queryRemarks", PersonListFilterArray::remarks);

                /** @var NameNormalizationService $nameNormalizationService */
                $nameNormalizationService = $this->get("app.name_normalization_service");

                if (isset($filterArray[PersonListFilterArray::streetName])) {
                    $filterArray[PersonListFilterArray::streetName] = $nameNormalizationService->normalizeStreetName($filterArray[PersonListFilterArray::streetName]);
                }

                //If findMode is Map than the received persons are checked against the selected geoArea(s)
                if (mb_strtolower($findMode) == "map" && $filterForm->has("activeGeoAreaIdsArray") && $filterForm->get("activeGeoAreaIdsArray")->getData() != null) {

                    if (count($customGeoAreas) == 0) {
                        $activeGeoAreaIdsArray = json_decode($filterForm->get("activeGeoAreaIdsArray")->getData());
                        $geoAreas = $em->getRepository('AppBundle:GeoArea')->findGeoAreasByIdArray($activeGeoAreaIdsArray);
                    } else {
                        //customGeoAreas are created at the beginning of this method
                        //because the form data can't be changed here
                        $geoAreas = $customGeoAreas;
                    }

                    $filterArray[PersonListFilterArray::geoAreas] = $geoAreas;
                }

                if ($filterForm->get('currentPage')->getData() != null) {
                    $currentPage = intval($filterForm->get('currentPage')->getData());
                } else {
                    $currentPage = 1;
                }

                $exactPolygonMatchMode = $this->container->getParameter("exact_polygon_match_mode");

                if (isset($filterForm[PersonListFilterArray::showAllEntities]) && $filterForm[PersonListFilterArray::showAllEntities]->getData() == 1) {
                    $personsTmp = $em->getRepository('AppBundle:Person')->findForPersonList(PersonListMode::findVulnerablePeople, $filterArray, $nameNormalizationService, $emergencyId, $orderKey, $orderValue, $currentPage, $exactPolygonMatchMode);
                    $pagesTotal = 1;
                } else {
                    $entitiesPerPage = $this->container->getParameter('entities_per_page');
                    $personsTmp = $em->getRepository('AppBundle:Person')->findForPersonList(PersonListMode::findVulnerablePeople, $filterArray, $nameNormalizationService, $emergencyId, $orderKey, $orderValue, $currentPage, $exactPolygonMatchMode, $entitiesPerPage);
                    $resultsTotal = $personsTmp['resultsTotal'];
                    $pagesTotal = intval(ceil($resultsTotal / $entitiesPerPage));
                }

                $personAddressesArray = $personsTmp['results'];

                $addressesForMarkerClusterArray = array();

                $newVulnerablePeopleArray = $personAddressesArray;

                //To serialize $addressesArrayForMarkerClusterJSON with all the required values
                $serializer = SerializerBuilder::create()->build();
                $serializationContext = new SerializationContext();
                $serializationContext->setSerializeNull(true);

                //Many of these properties are not really needed for the markerCluster
                //But it is included because because the serializer otherwise generates an object instead of an array
                //See https://github.com/schmittjoh/JMSSerializerBundle/issues/373
                $serializationContext->setGroups([
                    PersonAddress::ID_GROUP,
                    PersonAddress::DATA_GROUP,
                    PersonAddress::PERSON_ONLY_GROUP,
                    PotentialIdentity::ID_GROUP,
                    Address::DATA_GROUP,
                    Address::ID_GROUP,
                    Person::ID_GROUP,
                    Person::DATA_GROUP,
                    ContactPerson::ID_GROUP,
                    DataSource::ID_GROUP,
                    GeoPoint::DATA_GROUP,
                    Street::ID_GROUP,
                    Street::DATA_GROUP,
                    Street::ZIPCODE_ONLY_GROUP,
                    Zipcode::ID_GROUP,
                    Zipcode::DATA_GROUP,
                    Country::ID_GROUP,
                    VulnerabilityLevel::ID_GROUP,
                    MedicalRequirement::ID_GROUP,
                    TransportRequirement::ID_GROUP
                ]);

                /** @var User $user */
                $user = $this->getUser();

                /** @var PersonListHelperService $personListHelperService */
                $personListHelperService = $this->get("app.person_list_helper_service");
                $arrayForDisplay = $personListHelperService->buildArrayForDisplayPersonLists(PersonListMode::findVulnerablePeople, $em, $newVulnerablePeopleArray, $emergencyId, $user, $exactPolygonMatchMode);

                foreach ($arrayForDisplay as $personsOfPiArray) {
                    /** @var Person $personOfPi */
                    foreach ($personsOfPiArray as $personOfPi) {
                        /** @var PersonAddress $personAddress */
                        foreach ($personOfPi["person"]->getPersonAddresses() as $personAddress) {
                            if ($personAddress->getAddress() != null && $personAddress->getAddress()->getGeopoint() != null) {
                                $addressesForMarkerClusterArray[] = $personAddress;
                            }
                        }
                    }
                }

                $addressesArrayForMarkerClusterJSON = $serializer->serialize($addressesForMarkerClusterArray, "json", $serializationContext);

                return $this->render('vulnerablePeople/results.html.twig', array(
                    'geoAreasArrayJSON' => JSON_encode($geoAreas),
                    'selectedEmergency' => $emergency,
                    'addressesArrayForMarkerClusterJSON' => addslashes($addressesArrayForMarkerClusterJSON),
                    'arrayForDisplay' => $arrayForDisplay,
                    'form' => $form->createView(),
                    'findForm' => $filterForm->createView(),
                    'orderKey' => $orderKey,
                    'orderValue' => $orderValue,
                    'findMode' => $findMode,
                    'queryVulnerabilityLevelsString' => $queryVulnerabilityLevelString,
                    'queryMedicalRequirementsString' => $queryMedicalRequirementsString,
                    'queryTransportRequirementsString' => $queryTransportRequirementsString,
                    'currentPage' => $currentPage,
                    'pagesTotal' => $pagesTotal,

                    //Renders the searchInfoModal defined in base.html.twig
                    'renderSearchInfoModal' => true,
                ));
            } else {
                //Form is not valid
                return $this->redirect($this->generateUrl('find_vulnerable_people', array("emergencyId" => $emergency->getId())));
            }
        } // EmergencyId is not set in session
        return $this->redirect($this->generateUrl('login'));
    }
}
