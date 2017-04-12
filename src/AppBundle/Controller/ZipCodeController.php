<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use AppBundle\Entity\ZipCode;
use AppBundle\Form\ZipCodeType;

/**
 * ZipCode controller.
 *
 * @package AppBundle\Controller
 * @Route("/zipcode")
 */
class ZipCodeController extends Controller
{
    /**
     * Creates a new EmergencyStreet entity.
     *
     * @Route("/getZipcodesForAutocomplete/{emergencyId}", name="json_getZipcodesForAutocomplete")
     * @Method("GET")
     * @param {int} $emergencyId
     * @Security("has_role('ROLE_RESCUE_WORKER')")
     */
    public function getZipcodesForAutocompleteAction($emergencyId)
    {

        $em = $this->getDoctrine()->getManager();

        $streetsArray = $em->
        getRepository('AppBundle:Zipcode')->
        findZipcodesForAutocompletion($emergencyId);

        $apiHelperService = $this->get("app.api_helper_service");
        return $apiHelperService->getJsonResponseFromData($streetsArray);

    }

    /**
     * Creates a new EmergencyStreet entity.
     *
     * @Route("/getZipcodesForAddSpecial/{emergencyId}", name="json_getZipcodesForAddSpecial")
     * @Method("GET")
     * @param {int} $emergencyId
     * @Security("has_role('ROLE_RESCUE_WORKER')")
     */
    public function getZipcodesForAddSpecialAction($emergencyId)
    {

        $em = $this->getDoctrine()->getManager();

        $streetsArray = $em->
        getRepository('AppBundle:Zipcode')->findZipcodesForAddSpecial($emergencyId);

        $apiHelperService = $this->get("app.api_helper_service");
        return $apiHelperService->getJsonResponseFromData($streetsArray);
    }

    /**
     * Lists all ZipCode entities.
     *
     * @Route("/", name="zipcode_index")
     * @Method("GET")
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $zipCodes = $em->getRepository('AppBundle:ZipCode')->findAll();

        return $this->render('zipcode/index.html.twig', array(
            'zipCodes' => $zipCodes,
        ));
    }

    /**
     * Creates a new ZipCode entity.
     *
     * @Route("/new", name="zipcode_new")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function newAction(Request $request)
    {
        $zipCode = new ZipCode();
        $form = $this->createForm('AppBundle\Form\ZipCodeType', $zipCode);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($zipCode);
            $em->flush();

            return $this->redirectToRoute('zipcode_show', array('id' => $zipCode->getId()));
        }

        return $this->render('zipcode/new.html.twig', array(
            'zipCode' => $zipCode,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a ZipCode entity.
     *
     * @Route("/{id}", name="zipcode_show")
     * @Method("GET")
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function showAction(ZipCode $zipCode)
    {
        $deleteForm = $this->createDeleteForm($zipCode);

        return $this->render('zipcode/show.html.twig', array(
            'zipCode' => $zipCode,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing ZipCode entity.
     *
     * @Route("/{id}/edit", name="zipcode_edit")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function editAction(Request $request, ZipCode $zipCode)
    {
        $deleteForm = $this->createDeleteForm($zipCode);
        $editForm = $this->createForm('AppBundle\Form\ZipCodeType', $zipCode);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($zipCode);
            $em->flush();

            return $this->redirectToRoute('zipcode_edit', array('id' => $zipCode->getId()));
        }

        return $this->render('zipcode/edit.html.twig', array(
            'zipCode' => $zipCode,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a ZipCode entity.
     *
     * @Route("/{id}", name="zipcode_delete")
     * @Method("DELETE")
     * @param ZipCode $zipCode The ZipCode entity
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function deleteAction(Request $request, ZipCode $zipCode)
    {
        $form = $this->createDeleteForm($zipCode);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($zipCode);
            $em->flush();
        }

        return $this->redirectToRoute('zipcode_index');
    }

    /**
     * Creates a form to delete a ZipCode entity.
     *
     * @param ZipCode $zipCode The ZipCode entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(ZipCode $zipCode)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('zipcode_delete', array('id' => $zipCode->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
