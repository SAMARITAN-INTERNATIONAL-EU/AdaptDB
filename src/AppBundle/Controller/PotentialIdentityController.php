<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Address;
use AppBundle\Entity\Person;
use AppBundle\Entity\PersonAddress;
use AppBundle\Entity\PersonPotentialIdentity;
use AppBundle\Entity\DataSource;
use AppBundle\Entity\PotentialIdentityCluster;
use AppBundle\Entity\PotentialIdentity;

use AppBundle\Service\PersonListHelperService;
use AppBundle\Service\SessionKeys;
use AppBundle\Service\PotentialIdentityHelperService;
use AppBundle\Service\PotentialIdentityClusterHelperService;
use AppBundle\Service\ScrollToContainer;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;


use AppBundle\Service\CompareHelperService;
use AppBundle\Service\CombinedPerson;

use AppBundle\Form\PotentialIdentityType;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * PotentialIdentity controller.
 *
 * @package AppBundle\Controller
 * @Route("/potentialidentity")
 */
class PotentialIdentityController extends Controller
{
    /** @var CompareHelperService */
    private $compareHelperService;

    /**
     * Lists all PotentialIdentity entities.
     *
     * @Route("/", name="potentialidentity_index")
     * @Method("GET")
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $potentialIdentities = $em->getRepository('AppBundle:PotentialIdentity')->findAll();

        return $this->render('potentialidentity/index.html.twig', array(
            'potentialIdentities' => $potentialIdentities,
        ));
    }

    /**
     * This confirms a Potential Identity by creating an PI-Cluster with created=0 with the given personIds of the PI
     *
     * @Route("/confirm/{potentialIdentityId}", name="potential_identity_confirm")
     * @Security("has_role('ROLE_DATA_ADMIN')")
     * @Method("GET")
     */
    public function potentialIdentityConfirmAction($potentialIdentityId) {

        /** @var PotentialIdentityClusterHelperService $piHelperService */
        $piHelperService = $this->get("app.potential_identity_cluster_helper_service");

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        $personsOfPI = $em->getRepository('AppBundle:Person')->findPersonsOfPiByPiId($potentialIdentityId);

        $personIds = $piHelperService->getPersonIdsArrayFromPersonArray($personsOfPI);

        //find existing piCluster
        $existingPICluster = $em->getRepository('AppBundle:PotentialIdentityCluster')->findCreatedForPersonIds($personIds);

        if ($existingPICluster) { //A cluster of a with these persons (or a subset) does already exist.
            /** @var PotentialIdentityCluster $piCluster */

            //Remove the existing cluster and create a new one
            //The existing cluster is subset of the cluster to be created
            $em->remove($existingPICluster[0]);

        }

        $piCluster = $piHelperService->getNewPotentialIdentityCluster(1, $this->getUser());

        $piHelperService->addPersonsToPotentialIdentityCluster($piCluster, $personsOfPI);

        $em->persist($piCluster);

        $em->flush();

        return $this->redirect($this->generateUrl("show_potential_identity", array("potentialIdentityId" => $potentialIdentityId)));

    }


    /**
     * Finds and displays a Person entity.
     *
     * @Route("/showPotentialIdentity/{potentialIdentityId}", name="show_potential_identity")
     * @Security("has_role('ROLE_DATA_ADMIN') or has_role('ROLE_RESCUE_WORKER')")
     * @Method("GET")
     */
    public function piViewAction($potentialIdentityId)
    {

        /** @var Session $session */
        $session = new Session();

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var PotentialIdentity $potentialIdentity */
        $potentialIdentity = $em->getRepository('AppBundle:PotentialIdentity')->find($potentialIdentityId);

        if ($potentialIdentity === null) {
            throw new \Doctrine\ORM\EntityNotFoundException();
        }

        $emergencyId = $session->get(SessionKeys::selectedEmergencyId);

        /** @var Emergency $selectedEmergency */
        $selectedEmergency = $emergencyId ? $em->getRepository('AppBundle:Emergency')->find($emergencyId): null;

        /** @var CompareHelperService compareHelperService */
        $this->compareHelperService = $this->get("app.compare_helper_service");

        //This array contains the columns that are important for PI-creation
        //If two persons match with these columns a PI will be generated
        $piDetectionArray = json_decode($this->getParameter("potential_identity_detection"));

        /** @var User $user */
        $user = $this->getUser();

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

            $personsOfPI = $em->getRepository('AppBundle:Person')->findPersonsOfPiByPiIdAndEmergency($potentialIdentityId, $emergencyId, $exactPolygonMatchMode, $emergencyGeoAreas);

            //If this returns more than one person the Rescue Worker should be able to see the PI page.
            if (count($personsOfPI)>=1) {
                //Load personsOfPI again without the geoArea-filter
                $personsOfPI = $em->getRepository('AppBundle:Person')->findPersonsOfPiByPiIdAndEmergency($potentialIdentityId, $emergencyId, $exactPolygonMatchMode);
            } else {
                $this->addFlash(
                    'notice',
                    "Access denied"
                );

                return $this->redirect($this->generateUrl("emergency_index"));
            }

        } else { // hasOnlyRoleRescueWorker == false
            $personsOfPI = $em->getRepository('AppBundle:Person')->findPersonsOfPiByPiIdAndEmergency($potentialIdentityId, $emergencyId, $exactPolygonMatchMode);
        }

        /** @var PersonListHelperService $personListHelperService */
        $personListHelperService = $this->get("app.person_list_helper_service");
        $showMap = $personListHelperService->shouldShowMapInFrontend($personsOfPI);

        $this->setIsConfirmedForPotentialIdentity($potentialIdentity, $em, $personsOfPI);

        $combinedPerson = $this->combinePersonsOfPI($personsOfPI);

        return $this->render('person/show_potential_identity.html.twig', array(
            'personsOfPI' => $personsOfPI,
            'potentialIdentity' => $potentialIdentity,
            'combinedPerson' => $combinedPerson,
            'showMap' => $showMap,
            'piDetectionArray' => $piDetectionArray,
            'renderSearchInfoModal' => true,
            'emergencyId' => $emergencyId
        ));
    }

    private function setIsConfirmedForPotentialIdentity(PotentialIdentity &$potentialIdentity, $em, $personsOfPI) {

        //created = confirmed!

        $personIdsArray = array();
        foreach ($personsOfPI as $person) {
            $personIdsArray[] = $person->getId();
        }

        /** @var PotentialIdentityCluster $createdPiCluster */
        $createdPiCluster = $em->getRepository('AppBundle:PotentialIdentityCluster')->findCreatedForPersonIds($personIdsArray);
        $createdPiCluster = isset($createdPiCluster[0]) ? $createdPiCluster[0] : null;

        if ($createdPiCluster) {
            $newConfirmedValue = (count($createdPiCluster->getPersons()) == count($personsOfPI));
        } else {
            $newConfirmedValue = false;
        }

        $potentialIdentity->setIsConfirmed($newConfirmedValue);
    }

    public function combinePersonsOfPI($personsOfPI) {

        $combinePersonsHelperArray = array(
            //[
            //Key
            //Value to be shown in frontend
            //Value for comparison (optional)
            //],
            [
                CombinedPerson::firstName,
                function (Person $p) { return $p->getFirstName(); },
                function (Person $p) { return $p->getFirstNameNormalized(); },
            ],
            [
                CombinedPerson::lastName,
                function (Person $p) { return $p->getLastName(); },
                function (Person $p) { return $p->getLastNameNormalized(); },
            ],
            [
                CombinedPerson::fiscalCode,
                function (Person $p) { return $p->getFiscalCode(); },
            ],
            [CombinedPerson::dateOfBirth,
                function (Person $p) { return ($p->getDateOfBirth()) ? $p->getDateOfBirth()->format("d.m.Y") : ""; }],
            [
                CombinedPerson::landlinePhone,
                function (Person $p) { return $p->getLandlinePhone(); },
            ],
            [
                CombinedPerson::cellPhone,
                function (Person $p) { return $p->getCellPhone(); },
            ],
            [
                CombinedPerson::gender,
                function (Person $p) { return $p->getGenderMale() ? "Male" : "Female"; },
            ],
            [
                CombinedPerson::email,
                function (Person $p) { return $p->getEmail(); },
            ],
            [
                CombinedPerson::remarks,
                function (Person $p) { return $p->getRemarks(); },
            ],
            [
                CombinedPerson::vulnerabilityLevel,
                function (Person $p) { return $p->getVulnerabilityLevel(); },
            ],
            [
                CombinedPerson::transportRequirements,

                function (Person $p) {
                    $transportRequirements = $p->getTransportRequirements();
                    $returnArray = array();
                    foreach ($transportRequirements as $transportRequirement) {
                        $returnArray[] = $transportRequirement->getName();
                    }
                    return implode(", ", $returnArray);
                },
            ],
            [
                CombinedPerson::medicalRequirements,

                function (Person $p) {
                    $medicalRequirements = $p->getMedicalRequirements();
                    $returnArray = array();
                    foreach ($medicalRequirements as $medicalRequirement) {
                        $returnArray[] = $medicalRequirement->getName();
                    }
                    return implode(", ", $returnArray);
                },
            ],
            [
                CombinedPerson::validUntil,
                function (Person $p) { return $p->getValidUntil() ? $p->getValidUntil()->format("d-m-Y") : ""; }
            ],
            [
                CombinedPerson::contactPersons,
                function (Person $p) { return $p->getContactPersons()->getValues(); },
            ],
            [
                CombinedPerson::personAddresses,
                function (Person $p) { return $p->getPersonAddresses()->getValues(); },
            ]
        );

        $combinedPerson = array();
        $combinedPerson[CombinedPerson::firstName] = array();
        $combinedPerson[CombinedPerson::lastName] = array();
        $combinedPerson[CombinedPerson::fiscalCode] = array();
        $combinedPerson[CombinedPerson::dateOfBirth] = array();
        $combinedPerson[CombinedPerson::landlinePhone] = array();
        $combinedPerson[CombinedPerson::cellPhone] = array();
        $combinedPerson[CombinedPerson::gender] = array();
        $combinedPerson[CombinedPerson::email] = array();
        $combinedPerson[CombinedPerson::remarks] = array();
        $combinedPerson[CombinedPerson::vulnerabilityLevel] = array();
        $combinedPerson[CombinedPerson::transportRequirements] = array();
        $combinedPerson[CombinedPerson::medicalRequirements] = array();
        $combinedPerson[CombinedPerson::validUntil] = array();
        $combinedPerson[CombinedPerson::contactPersons] = array();
        $combinedPerson[CombinedPerson::personAddresses] = array();

        /** @var Person $personOfPI */
        foreach ($personsOfPI as $personOfPI) {
            $isOfficial = $personOfPI->getDataSource()->getIsOfficial();
            $dataSourceShortcut = $personOfPI->getDataSource()->getNameShort();

            foreach ($combinePersonsHelperArray as $combinePersonsHelper) {

                if (isset($combinePersonsHelper[2])) {
                    $value = $combinePersonsHelper[1]($personOfPI);
                    $valueForComparison = $combinePersonsHelper[2]($personOfPI);
                } else {
                    $value = $valueForComparison = $combinePersonsHelper[1]($personOfPI);
                }

                if (!empty($value)) {
                    if (is_array($value)) {
                        if ($combinePersonsHelper[0] == CombinedPerson::contactPersons) {

                            $contactPersonsArray = array();

                            foreach ($value as $contactPerson) {
                                $contactPersonsArray[] = [
                                    "isOfficial" => $isOfficial,
                                    "dataSource" => $dataSourceShortcut,
                                    "contactPerson" => $contactPerson,
                                    "valueForComparison" => $this->compareHelperService->getComparisonStringForContactPersonFromDatabase($contactPerson),
                                ];
                            }

                            foreach ($contactPersonsArray as $contactPerson) {

                                if (!$this->hasKeyValueWithValue($combinedPerson[$combinePersonsHelper[0]], $contactPerson["valueForComparison"], $isOfficial)) {
                                    $combinedPerson[$combinePersonsHelper[0]][] = $contactPerson;
                                }
                            }
                        } else if ($combinePersonsHelper[0] == CombinedPerson::personAddresses) {
                            $contactPersonsArray = array();

                            foreach ($value as $personAddress) {
                                $contactPersonsArray[] = [
                                    "isOfficial" => $isOfficial,
                                    "dataSource" => $dataSourceShortcut,
                                    "personAddress" => $personAddress,
                                    "valueForComparison" => $this->compareHelperService->getComparisonStringForPersonAddressFromDatabase($personAddress),
                                ];
                            }

                            foreach ($contactPersonsArray as $contactPerson) {

                                if (!$this->hasKeyValueWithValue($combinedPerson[$combinePersonsHelper[0]], $contactPerson["valueForComparison"], $isOfficial)) {
                                    $combinedPerson[$combinePersonsHelper[0]][] = $contactPerson;
                                }
                            }
                        }
                    } else {
                        if (!$this->hasKeyValueWithValue($combinedPerson[$combinePersonsHelper[0]], $valueForComparison, $isOfficial)) {

                            $combinedPerson[$combinePersonsHelper[0]][] = array(
                                "value" => $value,
                                "valueForComparison" => $valueForComparison,
                                "isOfficial" => $isOfficial,
                                "dataSource" => $dataSourceShortcut
                            );
                        }
                    }
                }
            }
        }

        return $combinedPerson;
    }

    /**
     * Searches in the array for an item where key:"value" matched $value
     * IsOfficial is set the value in the found array-item will be overwritten
     */
    private function hasKeyValueWithValue(&$array, $value, $isOfficial) {

        foreach ($array as &$arrayItem) {

            if (isset($arrayItem["valueForComparison"])) {
                if ($arrayItem["valueForComparison"] == $value ) {

                    //Value could be added with official = false
                    //If the same value exists in an official data source value should be marked official
                    if ($isOfficial) {
                        $arrayItem["isOfficial"] = true;
                    }
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Creates a new PotentialIdentity entity.
     *
     * @Route("/new", name="potentialidentity_new")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function newAction(Request $request)
    {
        $potentialIdentity = new PotentialIdentity();
        $form = $this->createForm('AppBundle\Form\PotentialIdentityType', $potentialIdentity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($potentialIdentity);
            $em->flush();

            return $this->redirectToRoute('potentialidentity_index');
        }

        return $this->render('potentialidentity/new.html.twig', array(
            'potentialIdentity' => $potentialIdentity,
            'form' => $form->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing PotentialIdentity entity.
     *
     * @Route("/{id}/edit", name="potentialidentity_edit")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function editAction(Request $request, PotentialIdentity $potentialIdentity)
    {
        $deleteForm = $this->createDeleteForm($potentialIdentity);
        $editForm = $this->createForm('AppBundle\Form\PotentialIdentityType', $potentialIdentity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($potentialIdentity);
            $em->flush();

            return $this->redirectToRoute('potentialidentity_edit', array('id' => $potentialIdentity->getId()));
        }

        return $this->render('potentialidentity/edit.html.twig', array(
            'potentialIdentity' => $potentialIdentity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a PotentialIdentity entity.
     *
     * @Route("/{id}", name="potentialidentity_delete")
     * @Method("DELETE")
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function deleteAction(Request $request, PotentialIdentity $potentialIdentity)
    {
        $form = $this->createDeleteForm($potentialIdentity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($potentialIdentity);
            $em->flush();
        }

        return $this->redirectToRoute('potentialidentity_index');
    }

    /**
     * Creates a form to delete a PotentialIdentity entity.
     *
     * @param PotentialIdentity $potentialIdentity The PotentialIdentity entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(PotentialIdentity $potentialIdentity)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('potentialidentity_delete', array('id' => $potentialIdentity->getId())))
            ->setMethod('DELETE')
            ->getForm();
    }

    /**
     *
     * This function removes a given Person from it's potential Identity
     * If the PI has only one person assigned to it after this person has been removed this PI will be dissolved
     *
     * @Route("/removePersonFromPotentialIdentity/{personIdToRemove}/{originPersonId}", name="removePersonFromPotentialIdentity")
     * @Method({"GET"})
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function removePersonFromPotentialIdentityAction(Request $request, $personIdToRemove, $originPersonId) {

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var PotentialIdentityHelperService $piHelperService */
        $piHelperService = $this->get("app.potential_identity_helper_service");
        /** @var PotentialIdentityClusterHelperService $piClusterHelperService */
        $piClusterHelperService = $this->get("app.potential_identity_cluster_helper_service");
        $piHelperService->removePersonFromPotentialIdentity($em, $piClusterHelperService, $this->getUser(), $personIdToRemove, $originPersonId);

        return $this->redirectToRoute("person_show", array('personId' => $originPersonId, 'scrollToContainer' => ScrollToContainer::SCROLLTOCONTAINER_POTENTIALIDENTITIES));

    }

    /**
     *
     * This action removes a potential identity and all assignments from persons to this PI
     *
     *
     * @Route("/dissolvePotentialIdentityOfPerson/{personId}", name="dissolvePotentialIdentityOfPerson")
     * @Method({"GET"})
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function dissolvePotentialIdentityOfPersonAction(Request $request, $personId) {

        $em = $this->getDoctrine()->getManager();

        $person = $em->getRepository('AppBundle:Person')->find($personId);

        if ($person === null) {
            throw new \Doctrine\ORM\EntityNotFoundException();
        }

        $potentialIdentity = $person->getPotentialIdentity();

        if ($potentialIdentity === null) {
            throw new \Doctrine\ORM\EntityNotFoundException();
        }

        /** @var PotentialIdentityHelperService $piHelperService */
        $piHelperService = $this->get("app.potential_identity_helper_service");

        /** @var PotentialIdentityClusterHelperService $piClusterHelperService */
        $piClusterHelperService = $this->get("app.potential_identity_cluster_helper_service");
        $piHelperService->dissolvePotentialIdentity($em, $piClusterHelperService, $this->getUser(), $potentialIdentity);

        return $this->redirectToRoute("person_show", array('personId' => $personId, 'scrollToContainer' => ScrollToContainer::SCROLLTOCONTAINER_POTENTIALIDENTITIES));

    }

    /**
     *
     * @Route("/addPersonToPotentialIdentityOfPerson/{personExistingId}/{personNewId}/{emergencyId}", name="addPersonToPotentialIdentityOfPerson")
     * @Method({"GET"})
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function addPersonToPotentialIdentityOfPerson(Request $request, $personExistingId, $personNewId, $emergencyId = null) {

        $em = $this->getDoctrine()->getManager();

        /** @var PotentialIdentityClusterHelperService $piHelperService */
        $piHelperService = $this->get("app.potential_identity_cluster_helper_service");

        //Get PersonIds to find the current PICluster $personExistingId
        /** @var Person $personExisting */
        $personExisting = $em->getRepository('AppBundle:Person')->find($personExistingId);

        /** @var Person $personNew */
        $personNew = $em->getRepository('AppBundle:Person')->find($personNewId);

        $potentialIdentityOfExistingPerson = $personExisting->getPotentialIdentity();

        //If no potential Identity existed before
        if ($potentialIdentityOfExistingPerson == null) {
            //Is PersonExisting had no PotentialIdentity create a new one
            $potentialIdentity = new PotentialIdentity();
            $potentialIdentity->setName($personExisting->getFirstName() . ' '. $personExisting->getLastName() );

            //Add both persons to the new potential Identity
            $personNew->setPotentialIdentity($potentialIdentity);
            $personExisting->setPotentialIdentity($potentialIdentity);
            $em->persist($potentialIdentity);
            $em->persist($personExisting);
            $em->persist($personNew);
            $em->flush();

        } else {
            $personNew->setPotentialIdentity($potentialIdentityOfExistingPerson);
            $em->persist($personNew);
        }

        //Add or Update PiCluster
        $piClusterOfExistingPerson = null;

        if ($personExisting->getPotentialIdentity()) {

            $personIdsArray =
                $piHelperService->getPersonIdsArrayFromPersonArray($personExisting->getPotentialIdentity()->getPersons());

            //Get PiCluster that belongs to the Pi
            /** @var PotentialIdentityCluster $piClusterOfExistingPerson */
            $piClusterOfExistingPerson = $em->getRepository('AppBundle:PotentialIdentityCluster')->findCreatedForPersonIds($personIdsArray);
            $piClusterOfExistingPerson = isset($piClusterOfExistingPerson[0]) ? $piClusterOfExistingPerson[0] : null;
        }

        if ($piClusterOfExistingPerson) { //If the cluster exists add the new person to it

            //Add the new Person
            $piClusterOfExistingPerson->addPerson($personNew);

            $em->persist($piClusterOfExistingPerson);

        } else {
            //If the cluster does not exist create it
            /** @var PotentialIdentityCluster $newPotentialIdentityCluster */
            $newPotentialIdentityCluster = $piHelperService->getNewPotentialIdentityCluster(1, $this->getUser());

            $newPotentialIdentityCluster->addPerson($personNew);
            $newPotentialIdentityCluster->addPerson($personExisting);

            //Add the new Person
            $em->persist($newPotentialIdentityCluster);
        }

        $em->flush();

        //To redirect the user to the url the route was called from
        $refererUrl = $request->headers->get('referer');

        if (!empty($refererUrl)) {
            return $this->redirect($refererUrl);
        } else {
            if (!empty($emergencyId)) {
                return $this->redirectToRoute("person_show", array('personId' => $personExistingId, 'scrollToContainer' => PersonController::SCROLLTOCONTAINER_POTENTIALIDENTITIES, 'emergencyId' => $emergencyId));
            } else {
                return $this->redirectToRoute("person_show", array('personId' => $personExistingId, 'scrollToContainer' => PersonController::SCROLLTOCONTAINER_POTENTIALIDENTITIES));
            }
        }

    }

    /**
     *
     * @Route("/JSON/person/getPersonsForPotentialIdentityFilter", name="getPersonsForPotentialIdentityFilterJSON")
     * @Security("has_role('ROLE_DATA_ADMIN')")
     * @Method({"POST"})
     */
    public function getPersonsForPotentialIdentityFilterJSON(Request $request) {

        $filterArray = (array)json_decode($request->request->get('filterArray'));

        $em = $this->getDoctrine()->getManager();
        $personId = $filterArray['thisPersonId'];
        $person = $em->getRepository('AppBundle:Person')->find($personId);
        unset($filterArray["thisPersonId"]);

        $persons = $em->getRepository('AppBundle:Person')->filterPersonsForPotentialIdentityExcludingPerson($filterArray, $person);

        $apiHelperService = $this->get("app.api_helper_service");

        return $apiHelperService->getJsonResponseFromData($persons, array(Person::ID_GROUP, Person::BASIC_DATA_GROUP, DataSource::DATA_GROUP));
    }
}
