<?php

namespace AppBundle\Controller;

use AppBundle\Entity\EmergencyPersonSafetyStatus;
use AppBundle\Entity\GeoPoint;
use AppBundle\Entity\Street;
use AppBundle\Entity\GeoArea;
use AppBundle\Service\SessionKeys;
use AppBundle\Entity\Emergency;
use AppBundle\Form\EmergencyType;

use CrEOF\Spatial\PHP\Types\Geometry\Point;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Emergency controller.
 *
 * @package AppBundle\Controller
 * @Route("/emergency")
 */
class EmergencyController extends Controller
{
    /**
     * Lists all Emergency entities.
     *
     * @Route("/", name="emergency_index")
     * @Method("GET")
     * @Security("has_role('ROLE_DATA_ADMIN') or has_role('ROLE_RESCUE_WORKER')")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $emergencies = $em->getRepository('AppBundle:Emergency')->findAll();

        /** @var Session $session */
        $session = new Session();

        $selectedEmergencyId = $session->get(SessionKeys::selectedEmergencyId);

        //Redirect to the "create emergency" page if no emergencies are defined
        if (count($emergencies) == 0) {
            return $this->redirect($this->generateUrl('emergency_new'));
        }

        return $this->render('emergency/index.html.twig', array(
            'emergencies' => $emergencies,
            'selectedEmergencyId' => $selectedEmergencyId,
        ));
    }

    /**
     * Creates a new Emergency entity. The user define one or more polygons on this page that are set as the geoAreas of the created emergency.
     *
     * @Route("/setActive/{emergencyId}/{newValue}", name="emergency_setActive")
     * @Method({"GET"})
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function setActiveAction($emergencyId , $newValue)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var Emergency $emergency */
        $emergency = $em->getRepository('AppBundle:Emergency')->find($emergencyId);

        if ($emergency === null) {
            throw new \Doctrine\ORM\EntityNotFoundException();
        }

        $emergency->setIsActive($newValue);
        $em->persist($emergency);
        $em->flush();

        return $this->redirect($this->generateUrl("emergency_index"));
    }


    /**
     * Creates a new Emergency entity. The user define one or more polygons on this page that are set as the geoAreas of the created emergency.
     *
     * @Route("/new", name="emergency_new")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function newAction(Request $request)
    {
        $emergency = new Emergency();
        $form = $this->createForm('AppBundle\Form\EmergencyType', $emergency);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $geoAreasString = $form->get('coordinatesString')->getData();
            $geoAreasArray =  json_decode($geoAreasString);

            //Check if lng values are valid
            //This prevents problems when the map has been scrolled to the "next earth-tile"

            $position=0;

            if (!empty($geoAreasArray)) {

                foreach ($geoAreasArray as $geoArea) {

                    $newGeoArea = new GeoArea();
                    $newGeoArea->setName('placeholdername for geoArea');
                    $newGeoArea->setEmergency($emergency);

                    if (!empty($geoAreasArray)) {
                        foreach ($geoArea as $geoPoint) {

                            //Checks if all geopoints are on the "center-tile"
                            //It fails if the user has moved the map to the "earth-tile" right or left of the original one
                            if ($geoPoint->lng<=-180 || $geoPoint->lng>=+180) {
                                $this->addFlash(
                                    'notice',
                                    "The emergency could not be created because at least one coordinate was not valid."
                                );

                                return $this->redirect($this->generateUrl("emergency_index"));
                            }

                            $newGeoPoint = new GeoPoint();
                            $newGeoPoint->setLat($geoPoint->lat);
                            $newGeoPoint->setLng($geoPoint->lng);
                            $newGeoPoint->setPoint(new Point($newGeoPoint->getLat(), $newGeoPoint->getLng()));
                            $newGeoPoint->setPosition($position);
                            $newGeoPoint->setGeoArea($newGeoArea);

                            $em->persist($newGeoPoint);
                            $position++;
                        }
                    }

                    $em->persist($newGeoArea);
                }
            }

            $emergency->setCreatedAt(new \DateTime());
            $emergency->setIsActive(true);
            $em->persist($emergency);
            $em->flush();

            //Create EmergencyPersonSafetyStatus-entities for this emergency
            $persons = $em->getRepository('AppBundle:Person')->findAll();

            foreach ($persons as $person) {
                $newEmergencyPersonSafetyStatus = new EmergencyPersonSafetyStatus();
                $newEmergencyPersonSafetyStatus->setPerson($person);
                $newEmergencyPersonSafetyStatus->setEmergency($emergency);
                $newEmergencyPersonSafetyStatus->setSafetyStatus(false);

                $em->persist($newEmergencyPersonSafetyStatus);
            }
            $em->flush();

            return $this->redirectToRoute('find_vulnerable_people', array("emergencyId" => $emergency->getId()));
        }

        return $this->render('emergency/new.html.twig', array(
            'emergency' => $emergency,
            'form' => $form->createView(),
            'geoNamesUsername' => $this->container->getParameter('geonames_username')
        ));
    }

    public function activeEmergenciesForNavbarAction()
    {
        $em = $this->getDoctrine()->getManager();
        $emergencies = $em->getRepository('AppBundle:Emergency')->findBy(array('isActive' => true));

        /** @var Session $session */
        $session = new Session();
        $selectedEmergencyId = $session->get(SessionKeys::selectedEmergencyId);

        return $this->render(
            'emergency/recent_emergencies_for_navbar.html.twig',
            array(
                'activeEmergencies' => $emergencies,
                'selectedEmergencyId' => $selectedEmergencyId
            )
        );
    }

    public function getDefaultsForAdaptMapAction()
    {

        return $this->render(
            'emergency/defaultsForAdaptMapAction.js.twig',
        array(
            "defaultMapCoordinatesJsonArray" => $this->container->getParameter('defaultMapPositionLatLng'),
            "defaultMapZoomLevel" => $this->container->getParameter('defaultMapZoom'),
        ));
    }

    /**
     * Displays a form to edit an existing Emergency entity.
     *
     * @Route("/{id}/edit", name="emergency_edit")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function editAction(Request $request, Emergency $emergency)
    {
        $deleteForm = $this->createDeleteForm($emergency);
        $editForm = $this->createForm('AppBundle\Form\EmergencyType', $emergency);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($emergency);
            $em->flush();

            return $this->redirectToRoute('emergency_edit', array('id' => $emergency->getId()));
        }

        return $this->render('emergency/edit.html.twig', array(
            'emergency' => $emergency,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes an emergency entity.
     *
     * @Route("/{emergencyId}/delete", name="emergency_delete")
     * @Method("GET")
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function deleteAction($emergencyId)
    {

        $em = $this->getDoctrine()->getManager();

        $emergency = $em->getRepository('AppBundle:Emergency')->find($emergencyId);

        //Remove all related GeoAreas
        $geoAreas = $em->getRepository('AppBundle:GeoArea')->findBy(array('emergency' =>  $emergencyId));

        foreach ($geoAreas as $geoArea) {

            foreach ($geoArea->getGeoPoints() as $geoPoint) {
                $em->remove($geoPoint);
            }

            $em->remove($geoArea);

        }

        //Remove all related EmergencyPersonSafetyStatus-entities for this emergencs
        $emergencyPersonSafetyStatusEntities = $em->getRepository('AppBundle:EmergencyPersonSafetyStatus')->findBy(array('emergency' =>  $emergencyId));

        /** @var EmergencyPersonSafetyStatus $emergencyPersonSafetyStatusEntitiy */
        foreach ($emergencyPersonSafetyStatusEntities as $emergencyPersonSafetyStatusEntitiy) {
            $em->remove($emergencyPersonSafetyStatusEntitiy);
        }

        //Remove the emergency itself
        if ($emergency) {
            $em->remove($emergency);
            $em->flush();
        }

        //Clear the sessionKey selectedEmergencyId if it has the ID of the emergency that was deleted
        /** @var Session $session */
        $session = new Session();
        if ($session->get(SessionKeys::selectedEmergencyId) == $emergencyId) {
            $session->set(SessionKeys::selectedEmergencyId, null);
        }

        return $this->redirectToRoute('emergency_index');
    }
}
