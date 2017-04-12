<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\Street;
use AppBundle\Form\StreetType;
use Zend\Serializer\Adapter\Json;

/**
 * Street controller.
 *
 * @package AppBundle\Controller
 * @Route("/street")
 */
class StreetController extends Controller
{

    /**
     * Lists all Street entities.
     *
     * @Route("/", name="street_index")
     * @Method("GET")
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $streets = $em->getRepository('AppBundle:Street')->findAll();

        return $this->render('street/index.html.twig', array(
            'streets' => $streets,
        ));
    }

    /**
     * Creates a new EmergencyStreet entity.
     *
     * @Route("/getStreetsByNameForAutocomplete/{searchString}", name="json_getStreetsByNameForAutocomplete")
     * @Method("GET")
     * @Security("has_role('ROLE_DATA_ADMIN') or has_role('ROLE_RESCUE_WORKER')")
     */
    public function getStreetsByNameForAutocompleteAction($searchString)
    {
        $em = $this->getDoctrine()->getManager();
        
        $nameNormalizationService = $this->get('app.name_normalization_service');
        $searchString = $nameNormalizationService->normalizeName($searchString);
        $streetsArray = $em->getRepository('AppBundle:Street')->findStreetsByNameForAutocompleter($searchString);

        $apiHelperService = $this->get("app.api_helper_service");
        return $apiHelperService->getJsonResponseFromData($streetsArray);
    }

      /**
      * @Route("/getStreetsByStreetListIds", name="json_getStreetsByStreetListIds")
      * @Method("GET")
      * @Security("has_role('ROLE_RESCUE_WORKER')")
      */
    public function getStreetsByStreetListIdsAction()
    {

        if (isset($_REQUEST['streetListIdsArray'])) {
            $streetListIdsArray = json_decode($_REQUEST['streetListIdsArray']);

            $em = $this->getDoctrine()->getManager();

            $streetsArray = $em->getRepository('AppBundle:Street')->findBy(array('id' => $streetListIdsArray));

            $streetsDict =  [];
            foreach ($streetsArray as $street) {
                $zipcode = $street->getZipcode();
                $streetsDict[$street->getId()] = array("name" => $street->getName(), "zipcode" => $zipcode->getZipcode(), "city" => $zipcode->getCity());
            }

            $apiHelperService = $this->get("app.api_helper_service");
            return $apiHelperService->getJsonResponseFromData($streetsDict);

        } else {
            echo("Error - Parameter 'streetListIdsArray' is missing!");
            die();
        }
    }

    /**
     *
     * @Route("/saveStreetListAsEmergencyStreetList", name="saveStreetListAsEmergencyStreetList")
     * @Method("POST")
     * @Security("has_role('ROLE_RESCUE_WORKER')")
     */
    public function saveStreetListAsEmergencyStreetListAction(Request $request)
    {

        $streetsArrayToPersist = json_decode(trim($request->get('streetsArray')), true);

        $em = $this->getDoctrine()->getManager();
        $emergencyId = $request->get('emergencyId');
        $emergency = $em->getRepository('AppBundle:Emergency')->find($emergencyId);

        //removes all streets that are currently related to the emergency
        $streetsArrayToUnlink = $emergency->getStreets();

        foreach ($streetsArrayToUnlink as $street) {
            $emergency->removeStreet($street);
        }

        //Adds the selected streets to the emergency
        foreach ($streetsArrayToPersist as $key => $value) {

            $street = $em->getRepository('AppBundle:Street')->find($value);
            if ($street != null) {
                $emergency->addStreet($street);
            }
        }

        $em->persist($emergency);
        $em->flush();

        $apiHelperService = $this->get("app.api_helper_service");
        return $apiHelperService->getJsonResponseFromData(array('status' => 'success', 'success'));

    }

    /**
     * Creates a new Street entity.
     *
     * @Route("/new", name="street_new")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function newAction(Request $request)
    {
        $street = new Street();
        $form = $this->createForm('AppBundle\Form\StreetType', $street);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->persist($street);
            $em->flush();

            return $this->redirectToRoute('street_show', array('id' => $street->getId()));
        }

        return $this->render('street/new.html.twig', array(
            'street' => $street,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Street entity.
     *
     * @Route("/{id}", name="street_show")
     * @Method("GET")
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function showAction(Street $street)
    {
        $deleteForm = $this->createDeleteForm($street);

        return $this->render('street/show.html.twig', array(
            'street' => $street,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Street entity.
     *
     * @Route("/{id}/edit", name="street_edit")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function editAction(Request $request, Street $street)
    {
        $deleteForm = $this->createDeleteForm($street);
        $editForm = $this->createForm('AppBundle\Form\StreetType', $street);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($street);
            $em->flush();

            return $this->redirectToRoute('street_edit', array('id' => $street->getId()));
        }

        return $this->render('street/edit.html.twig', array(
            'street' => $street,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a Street entity.
     *
     * @Route("/{id}", name="street_delete")
     * @Method("DELETE")
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function deleteAction(Request $request, Street $street)
    {
        $form = $this->createDeleteForm($street);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($street);
            $em->flush();
        }

        return $this->redirectToRoute('street_index');
    }

    /**
     * Creates a form to delete a Street entity.
     *
     * @param Street $street The Street entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Street $street)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('street_delete', array('id' => $street->getId())))
            ->setMethod('DELETE')
            ->getForm();
    }


    /**
     * Gets a JSON-Array with all the streets in the database
     * @Route("/json/getStreetNamesForEmergency/{emergencyId}", name="json_getStreetNamesForEmergency")
     * @Method("GET")
     * @Security("has_role('ROLE_DATA_ADMIN') or has_role('ROLE_RESCUE_WORKER')")
     * @return Json
     */
    public function getDistinctStreetNamesAction($emergencyId) {

        $em = $this->getDoctrine()->getManager();

        $streetsArray = $em->getRepository('AppBundle:Street')->findStreetsByEmergencyId($emergencyId);

        $streetsDict =  [];
        foreach ($streetsArray as $street) {
            $streetsDict[$street['id']] = array("name" => $street['name'], "zipcode" => $street['zipcode'], "city" => $street['city']);
        }

        $apiHelperService = $this->get("app.api_helper_service");
        return $apiHelperService->getJsonResponseFromData($streetsDict);

    }

    /**
     *
     * Returns a JSON-Array with all streets in the database
     * @Route("/json/getAllStreetsInDatabase", name="json_getAllStreetsInDatabase")
     * @Method("GET")
     * @Security("has_role('ROLE_DATA_ADMIN') or has_role('ROLE_RESCUE_WORKER')")
     * @return Json
     */
    public function getAllStreetsInDatabaseAction() {

        $em = $this->getDoctrine()->getManager();

        $streetsArray = $em->getRepository('AppBundle:Street')->findAll_forStreetsListOnFindPage();

        $streetsDict =  [];
        foreach ($streetsArray as $street) {
            $streetsDict[$street['id']] = array("name" => $street['name'], "zipcode" => $street['zipcode'], "city" => $street['city']);
        }

        $apiHelperService = $this->get("app.api_helper_service");
        return $apiHelperService->getJsonResponseFromData($streetsDict);
    }

    /**
     *
     * @Route("/json/getAllStreetsByZipcode/{zipcodeIdsArray}", name="json_getAllStreetsByZipcode", defaults={"searchScope" = "zipcode"})
     * @Route("/json/getAllStreetsByCity/{zipcodeIdsArray}", name="json_getAllStreetsByCity", defaults={"searchScope" = "city"})
     * @Security("has_role('ROLE_RESCUE_WORKER')")
     */
    public function getAllStreetsByZipcodeAction($zipcodeIdsArray, $searchScope) {

        $em = $this->getDoctrine()->getManager();

        $zipcodeIdsArray = json_decode($zipcodeIdsArray);
        $streetsArray =  $em->getRepository('AppBundle:Street')->findStreetsByZipcodeIdsArray_forStreetsListOnFindPage($zipcodeIdsArray);

        $streetsDict = [];
        foreach ($streetsArray as $street) {
            $streetsDict[$street['id']] = array("name" => $street['name'], "zipcode" => $street['zipcode'], "city" => $street['city']);
        }

        $apiHelperService = $this->get("app.api_helper_service");

        return $apiHelperService->getJsonResponseFromData($streetsDict);
    }
}
