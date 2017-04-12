<?php

namespace AppBundle\Controller;

use AppBundle\Entity\DataSource;
use AppBundle\Entity\Emergency;
use AppBundle\Entity\PotentialIdentity;
use AppBundle\Entity\User;
use AppBundle\Service\ImportHelperService;
use AppBundle\Service\NameNormalizationService;
use AppBundle\Service\PersonListFilterArray;
use AppBundle\Service\PersonListHelperService;
use AppBundle\Service\PersonListMode;
use AppBundle\Service\PotentialIdentityClusterHelperService;
use AppBundle\Service\PotentialIdentityHelperService;
use AppBundle\Service\ScrollToContainer;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Forms;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use AppBundle\Entity\Address;
use AppBundle\Entity\GeoArea;
use AppBundle\Entity\GeoPoint;
use AppBundle\Entity\PotentialIdentityCluster;
use AppBundle\Entity\PersonAddressWithoutPerson;
use AppBundle\Entity\Person;
use AppBundle\Entity\PersonAddress;
use AppBundle\Entity\ContactPerson;
use AppBundle\Form\PersonType;
use AppBundle\Service\MailerService;
use AppBundle\Service\SessionKeys;
use Symfony\Component\HttpFoundation\Session\Session;


/**
 * PersonController controller.
 *
 * @package AppBundle\Controller
 *
 */
class PersonController extends Controller
{
    /**
     * Marks a person as safe or unsafe
     *
     * @Route("/person/markAllPersonsOfPotentialIdentityAsSafe/{potentialIdentityId}/{originPersonId}/{emergencyId}", name="markAllPersonsOfPotentialIdentityAsSafe")
     * @Security("has_role('ROLE_DATA_ADMIN')")
     * @Method("GET")
     */
    public function markAllPersonsOfPotentialIdentityAsSafeAction($potentialIdentityId, $originPersonId, $emergencyId) {

        $em = $this->getDoctrine()->getManager();

        $persons = $em->getRepository('AppBundle:Person')->findPersonsByPotentialIdentityWithSafetyStatusForEmergency($potentialIdentityId, $emergencyId);

        foreach ($persons as $person) {

            $safetyStatusForEmergency = $person->getEmergencySafetyStatuses()[0];
            $safetyStatusForEmergency->setSafetyStatus(true);
            $em->persist($person);
        }

        $em->flush();

        return $this->redirectToRoute("person_show", array('personId' => $originPersonId, 'scrollToContainer' => ScrollToContainer::SCROLLTOCONTAINER_POTENTIALIDENTITIES, 'emergencyId' => $emergencyId));
    }

    /**
     * Marks a person as safe or unsafe
     *
     * @Route("/person/{personId}/markas{safeOrUnsafe}/{emergencyId}/{originPersonId}", name="person_mark_as_safe_or_unsafe")
     * @Security("has_role('ROLE_DATA_ADMIN') or has_role('ROLE_RESCUE_WORKER')")
     * @Method("GET")
     */
    public function markAsSafeOrUnsafeAction($personId, $safeOrUnsafe, $emergencyId, $originPersonId = null) {

        $em = $this->getDoctrine()->getManager();
        $emergencySafetyStatus = $em->getRepository('AppBundle:EmergencyPersonSafetyStatus')->findOneBy(array('person' => $personId, 'emergency' => $emergencyId));

        if ( strtolower($safeOrUnsafe) == 'safe' ) {
            $emergencySafetyStatus->setSafetyStatus(1);
        } else {
            $emergencySafetyStatus->setSafetyStatus(0);
        }

        $em->persist($emergencySafetyStatus);
        $em->flush();


        if ($originPersonId != null) {
            return $this->redirectToRoute("person_show", array('personId' => $originPersonId, 'emergencyId' => $emergencyId, 'scrollToContainer' => ScrollToContainer::SCROLLTOCONTAINER_POTENTIALIDENTITIES));
        } else {
            return $this->redirectToRoute("person_show", array('personId' => $personId, 'emergencyId' => $emergencyId));
        }
    }

    /**
     * Displays a form to edit an existing person entity.
     *
     * @Route("/{personId}/edit", name="person_edit")
     * @Security("has_role('ROLE_DATA_ADMIN')")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, $personId)
    {

        $em = $this->getDoctrine()->getManager();

        $person = $em->getRepository('AppBundle:Person')->find($personId);
        $editForm = $this->createForm('AppBundle\Form\PersonType', $person, array());

        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->persist($person);
            $em->flush();
            return new Response("success");
        }

        return $this->render('person/edit.html.twig', array(
            'edit_form' => $editForm->createView(),
        ));

    }

    /**
     * Lists all Person entities.
     *
     * @Route("/personaddress/{emergencyId}", name="personaddress_index")
     * @Security("has_role('ROLE_DATA_ADMIN')")
     * @Method({"GET", "POST"})
     */
    public function indexAction(Request $request, $emergencyId = null)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        $entitiesPerPage = $this->container->getParameter('entities_per_page');

        $filterArray = [];

        $filterForm = $this->createForm('AppBundle\Form\PeopleAddressesFilterType');
        $filterForm->handleRequest($request);

        /** @var NameNormalizationService $nameNormalizationService */
        $nameNormalizationService = $this->get("app.name_normalization_service");

        if ($filterForm->isValid()) {
            $filterHelperService = $this->get('app.filter_helper_service');
            $filterHelperService->addFilterArrayPropertyFromFilterForm($filterForm, $filterArray, "queryIsActive", PersonListFilterArray::isActive);
            $filterHelperService->addFilterArrayPropertyFromFilterForm($filterForm, $filterArray, "querySafetyStatus", PersonListFilterArray::safetyStatus);
            $filterHelperService->addFilterArrayPropertyFromFilterForm($filterForm, $filterArray, "queryFiscalCode", PersonListFilterArray::fiscalCode);
            $filterHelperService->addFilterArrayPropertyFromFilterForm($filterForm, $filterArray, "queryFirstName", PersonListFilterArray::firstName);
            $filterHelperService->addFilterArrayPropertyFromFilterForm($filterForm, $filterArray, "queryLastName", PersonListFilterArray::lastName);
            $filterHelperService->addFilterArrayPropertyFromFilterForm($filterForm, $filterArray, "queryDateOfBirth", PersonListFilterArray::dateOfBirth);
            $filterHelperService->addFilterArrayPropertyFromFilterForm($filterForm, $filterArray, "queryAge", PersonListFilterArray::age);
            $filterHelperService->addFilterArrayPropertyFromFilterForm($filterForm, $filterArray, "queryAgeGrSm", PersonListFilterArray::ageGrSm);
            $filterHelperService->addFilterArrayPropertyFromFilterForm($filterForm, $filterArray, "queryStreetName", PersonListFilterArray::streetName);
            $filterHelperService->addFilterArrayPropertyFromFilterForm($filterForm, $filterArray, "queryStreetNr", PersonListFilterArray::streetNr);
            $filterHelperService->addFilterArrayPropertyFromFilterForm($filterForm, $filterArray, "queryCity", PersonListFilterArray::city);
            $filterHelperService->addFilterArrayPropertyFromFilterForm($filterForm, $filterArray, "queryZipcode", PersonListFilterArray::zipcode);

            if (isset($filterArray[PersonListFilterArray::streetName])) {
                $filterArray[PersonListFilterArray::streetName] = $nameNormalizationService->normalizeStreetName($filterArray[PersonListFilterArray::streetName]);
            }
        }

        if ($filterForm->get('currentPage')->getData() != null) {
            $currentPage = intval($filterForm->get('currentPage')->getData());
        } else {
            $currentPage = 1;
        }

        $orderKey = 'p.lastName';
        $orderValue = 'ASC';

        $exactPolygonMatchMode = $this->container->getParameter("exact_polygon_match_mode");

        $personsTmp = $em->getRepository('AppBundle:Person')->findForPersonList(PersonListMode::personAddressesOverview, $filterArray, $nameNormalizationService, $emergencyId, $orderKey, $orderValue, $currentPage, $exactPolygonMatchMode, $entitiesPerPage);
        $personsArray = $personsTmp['results'];
        $resultsTotal = $personsTmp['resultsTotal'];

        /** @var User $user */
        $user = $this->getUser();

        //Creates an array that is used for display on the index-template
        //To preserve the pagination every person from $personArray is enhanced with a list of possible other persons
        //belonging to the same potential identity

        /** @var PersonListHelperService $personListHelperService */
        $personListHelperService = $this->get("app.person_list_helper_service");



        $arrayForDisplay = $personListHelperService->buildArrayForDisplayPersonLists(PersonListMode::personAddressesOverview, $em, $personsArray, $emergencyId, $user, $exactPolygonMatchMode);

        $pagesTotal = ceil($resultsTotal / $entitiesPerPage);

        return $this->render('person/index.html.twig', array(
            'arrayForDisplay' => $arrayForDisplay,
            'filterForm' => $filterForm->createView(),
            'currentPage' => $currentPage,
            'pagesTotal' => $pagesTotal,
            'emergencyId' => $emergencyId,

            //Renders the searchInfoModal defined in base.html.twig
            'renderSearchInfoModal' => true,
        ));
    }

    /**
     * Deletes a Person entity.
     *
     * @Route("/{personId}", name="person_delete")
     * @Security("has_role('ROLE_DATA_ADMIN')")
     * @Method("POST")
     */
    public function deletePersonAction($personId)
    {

        $em = $this->getDoctrine()->getManager();
        /** @var Person $person */
        $person = $em->getRepository('AppBundle:Person')->find($personId);

        $potentialIdentity = $person->getPotentialIdentity();

        if ($potentialIdentity) {
            $piHelperService = $this->get("app.potential_identity_helper_service");
            $piClusterHelperService = $this->get("app.potential_identity_cluster_helper_service");
            $piHelperService->dissolvePotentialIdentity($em, $piClusterHelperService, $this->getUser(), $potentialIdentity);
        }
        
        //Remove related contact Persons
        $contactPersons = $person->getContactPersons();
        foreach ($contactPersons as $contactPerson) {
            $em->remove($contactPerson);
        }

        //Remove related personAddresses
        $personAddresses = $person->getPersonAddresses();
        foreach ($personAddresses as $personAddress) {
            $em->remove($personAddress);
        }

        $em->flush();

        //Remove related Data Change History
        $dataChangeHistoryForPerson = $em->getRepository('AppBundle:DataChangeHistory')->findBy(array('person' => $person));
        foreach ($dataChangeHistoryForPerson as $dataChangeHistoryItem) {
            $em->remove($dataChangeHistoryItem);
        }

        //Remove related Import Warnings
        $importWarningsForPerson = $em->getRepository('AppBundle:ImportWarning')->findBy(array('person' => $person));
        foreach ($importWarningsForPerson as $importWarning) {
            $em->remove($importWarning);
        }

        //Remove person related person safety-status
        $safetyStatuses = $em->getRepository('AppBundle:EmergencyPersonSafetyStatus')->findBy(array('person' => $person));
        foreach ($safetyStatuses as $safetyStatus) {
            $em->remove($safetyStatus);
        }

        $em->flush();

        $em->remove($person);

        $em->flush();

        return new Response("success");
    }

    /**
     * Deletes a PersonAddress entity.
     *
     * @Route("/delete/{personId}/{addressId}", name="personaddress_delete")
     * @Security("has_role('ROLE_DATA_ADMIN')")
     * @Method("POST")
     */
    public function deletePersonAddressAction($personId, $addressId)
    {
        $em = $this->getDoctrine()->getManager();

        $person = $em->getRepository('AppBundle:Person')->find($personId);
        $address = $em->getRepository('AppBundle:Address')->find($addressId);

        if ($address === null || $person === null) {
            throw new \Doctrine\ORM\EntityNotFoundException();
        }

        //PersonAddress has a composite primary key {personId, AddressId} -> so it's clear that the correct entity will be chosen
        $personAddress = $em->getRepository('AppBundle:PersonAddress')->findOneBy(array('person' => $person, 'address' => $address));

        if ($personAddress === null) {
            throw new \Doctrine\ORM\EntityNotFoundException();
        }

        $em->remove($personAddress);
        $em->flush();

        return new Response("success");
    }

    /**
     * Creates a new Person entity.
     *
     * @Route("/person/new", name="person_new")
     * @Security("has_role('ROLE_DATA_ADMIN')")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $personAddress = new PersonAddress();
        $personAddress->setIsActive(true);
        $personAddressForm = $this->createForm('AppBundle\Form\PersonAddressType', $personAddress);

        $personAddressForm->handleRequest($request);

        if ($request != null) {

            //manually getting the values from POST
            $dataFromForm = $request->request->get("person_address");

            if ($dataFromForm != null) {
                if (isset($dataFromForm['address'])) {
                    $addressArray = array();
                    foreach ($dataFromForm['address'] as $key => $value) {
                        if ($key != "_token") {
                            $addressArray[$key] = $value;
                        }
                    }

                    //Set DataSource to "Backend"
                    $dataSourceBackend = $em->getRepository('AppBundle:DataSource')->findOneBy(array("name" => "Backend"));
                    $personAddress->getPerson()->setDataSource($dataSourceBackend);


                    $persistAddressHelperService = $this->get('app.persist_address_helper_service');

                    $geocoderService = $this->get('app.geocoder_service');
                    $nominatimEmailAddress = $this->container->getParameter('nominatim_email_address');
                    $geocodingEnabled = true;

                    $em->persist($personAddress->getPerson());

                    //Persist the personAddress and its related entities
                    $address = $persistAddressHelperService->persistAddress($geocoderService, $em, $addressArray, $nominatimEmailAddress, $geocodingEnabled);

                    $personAddress->setAddress($address);
                    $personAddressForm = $this->createForm('AppBundle\Form\PersonAddressType', $personAddress);
                    $personAddressForm->handleRequest($request);

                    $personAddress->setIsActive(true);

                    //Return the form if there are errors
                    if ($personAddressForm->isValid() == false) {
                        return $this->render('person/new.html.twig', array(
                            'person' => $personAddress,
                            'form' => $personAddressForm->createView(),
                        ));
                    }

                    //Flush if there have been no errors
                    $em->persist($address);
                    $em->persist($personAddress);
                    $em->flush();

                    //Create EmergencySafetyStatus-Entities for all emergencies in the database
                    $allEmergencies = $em->getRepository('AppBundle:Emergency')->findAll();

                    /** @var ImportHelperService $ihs */
                    $importHelperService = $this->get("app.import_helper_service");
                    $importHelperService->addEmergencySaftetyStatusForNewPerson($personAddress->getPerson(), $allEmergencies);

                    $em->flush();

                    return $this->redirectToRoute('personaddress_index', array('id' => $personAddress->getPerson()->getId()));

                }
            }
        }

        return $this->render('person/new.html.twig', array(
            'person' => $personAddress,
            'form' => $personAddressForm->createView(),
        ));
    }

    /**
     * Finds and displays a Person entity.
     *
     * @Route("/person/{personId}/{scrollToContainer}", name="person_show")
     * @Security("has_role('ROLE_DATA_ADMIN') or has_role('ROLE_RESCUE_WORKER')")
     * @Method("GET")
     */
    public function showAction($personId, $scrollToContainer = 0)
    {

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var User $user */
        $user = $this->getUser();

        /** @var Session $session */
        $session = new Session();

        $emergencyId = $session->get(SessionKeys::selectedEmergencyId);

        /** @var Emergency $selectedEmergency */
        $selectedEmergency = $emergencyId ? $em->getRepository('AppBundle:Emergency')->find($emergencyId): null;

        $exactPolygonMatchMode = $this->container->getParameter("exact_polygon_match_mode");

        // When user has only the role Rescue Worker
        if ($user->hasOnlyRoleRescueWorker()) {

            if (!$emergencyId) {
                // The user needs to select an emergency first
                $this->addFlash(
                    'notice',
                    "The page couldn't be opened because no emergency was selected."
                );

                return $this->redirect($this->generateUrl("emergency_index"));
            }



            // Get polygons of current emergency for the polygon check
            $emergencyGeoAreas = $selectedEmergency->getGeoAreas();

            $person = $em->getRepository('AppBundle:Person')->getPersonWithPersonAddresses($personId, $emergencyId, $exactPolygonMatchMode, $emergencyGeoAreas);

            //If the Person is outside of the polygon of the selected emergency-geoArea the function will not return a person
            if (!$person) {
                $this->addFlash(
                    'notice',
                    "Access denied"
                );

                return $this->redirect($this->generateUrl("emergency_index"));
            }

        } else { // hasOnlyRoleRescueWorker == false
            $person = $em->getRepository('AppBundle:Person')->getPersonWithPersonAddresses($personId, $emergencyId, $exactPolygonMatchMode);
        }

        //Get the potential identities
        $personsOfPI = array();
        /** @var PotentialIdentity $potentialIdentity */
        $potentialIdentity = $person->getPotentialIdentity();

        if ($potentialIdentity != null) {
            $personsOfPI = $em->getRepository('AppBundle:Person')->findPersonsForPotentialIdentityExludingPerson($potentialIdentity->getId(), $person->getId(), $emergencyId);
        }

        $dataChangeHistory = $em->getRepository('AppBundle:DataChangeHistory')->findBy(array('person' => $person), array('timestamp' => 'DESC'));

        /** @var PersonListHelperService $personListHelperService */
        $personListHelperService = $this->get("app.person_list_helper_service");
        $showMap = $personListHelperService->shouldShowMapInFrontend($person);

        return $this->render('person/show.html.twig', array(
            'dataChangeHistory' => $dataChangeHistory,
            'person' => $person,
            'selectedEmergency' => $selectedEmergency,
            'showMap' => $showMap,
            'personsOfPI' => $personsOfPI,
            'renderSearchInfoModal' => true,
            'scrollToContainer' => $scrollToContainer,
            'emergencyId' => $emergencyId
        ));

    }
}
