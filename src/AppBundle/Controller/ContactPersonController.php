<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use AppBundle\Entity\ContactPerson;
use AppBundle\Form\ContactPersonType;
use AppBundle\Entity\DataChangeHistory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * ContactPerson controller.
 *
 * @package AppBundle\Controller
 * @Route("/contactperson")
 */
class ContactPersonController extends Controller
{
    /**
     * Lists all ContactPerson entities.
     *
     * @Route("/", name="contactperson_index")
     * @Security("has_role('ROLE_DATA_ADMIN')")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $contactPeople = $em->getRepository('AppBundle:ContactPerson')->findAll();

        return $this->render('contactperson/index.html.twig', array(
            'contactPeople' => $contactPeople,
        ));
    }

    /**
     * Creates a new ContactPerson entity.
     *
     * @Route("/new", name="contactperson_new")
     * @Security("has_role('ROLE_DATA_ADMIN')")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $contactPerson = new ContactPerson();
        $form = $this->createForm('AppBundle\Form\ContactPersonType', $contactPerson);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($contactPerson);
            $em->flush();

            return new Response("success");
        }

        return $this->render('contactperson/new.html.twig', array(
            'contactPerson' => $contactPerson,
            'form' => $form->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing ContactPerson entity.
     *
     * @Route("/{id}/edit", name="contactperson_edit")
     * @Security("has_role('ROLE_DATA_ADMIN')")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, ContactPerson $contactPerson)
    {
        $editForm = $this->createForm('AppBundle\Form\ContactPersonType', $contactPerson);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($contactPerson);
            $em->flush();

            return new Response("success");
        }

        return $this->render('contactperson/edit.html.twig', array(
            'contactPerson' => $contactPerson,
            'edit_form' => $editForm->createView(),
        ));
    }

    /**
     * Deletes a ContactPerson entity.
     *
     * @Route("/{id}", name="contactperson_delete")
     * @Security("has_role('ROLE_DATA_ADMIN')")
     * @Method("POST")
     */
    public function deleteAction(ContactPerson $contactPerson)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($contactPerson);
        $em->flush();

        return new Response("success");
    }



    /**
     * @Route("/JSON/{contactPersonId}", name="json_getContactPersonById")
     * @Security("has_role('ROLE_DATA_ADMIN')")
     * @Method("GET")
     */
    public function getContactPersonByIdJSONAction($contactPersonId)
    {

        $em = $this->getDoctrine()->getManager();

        $contactPerson =  $em->getRepository('AppBundle:ContactPerson')->find($contactPersonId);

        $apiHelperService = $this->get("app.api_helper_service");
        return $apiHelperService->getJsonResponseFromData($contactPerson);
    }

}
