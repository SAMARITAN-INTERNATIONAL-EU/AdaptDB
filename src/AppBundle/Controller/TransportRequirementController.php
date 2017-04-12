<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use AppBundle\Entity\TransportRequirement;
use AppBundle\Form\TransportRequirementType;

/**
 * TransportRequirement controller.
 *
 * @package AppBundle\Controller
 * @Route("/transportrequirement")
 */
class TransportRequirementController extends Controller
{
    /**
     * Lists all TransportRequirement entities.
     *
     * @Route("/", name="transportrequirement_index")
     * @Security("has_role('ROLE_SYSTEM_ADMIN')")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $transportRequirements = $em->getRepository('AppBundle:TransportRequirement')->findAll();

        return $this->render('transportrequirement/index.html.twig', array(
            'transportRequirements' => $transportRequirements,
        ));
    }

    /**
     * Creates a new TransportRequirement entity.
     *
     * @Route("/new", name="transportrequirement_new")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_SYSTEM_ADMIN')")
     */
    public function newAction(Request $request)
    {
        $transportRequirement = new TransportRequirement();
        $form = $this->createForm('AppBundle\Form\TransportRequirementType', $transportRequirement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($transportRequirement);
            $em->flush();
            
            return $this->redirectToRoute('transportrequirement_index');
        }

        return $this->render('transportrequirement/new.html.twig', array(
            'transportRequirement' => $transportRequirement,
            'form' => $form->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing TransportRequirement entity.
     *
     * @Route("/{id}/edit", name="transportrequirement_edit")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_SYSTEM_ADMIN')")
     */
    public function editAction(Request $request, TransportRequirement $transportRequirement)
    {
        $editForm = $this->createForm('AppBundle\Form\TransportRequirementType', $transportRequirement);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($transportRequirement);
            $em->flush();

            return $this->redirectToRoute('transportrequirement_index');
        }

        return $this->render('transportrequirement/edit.html.twig', array(
            'transportRequirement' => $transportRequirement,
            'edit_form' => $editForm->createView(),
        ));
    }

    /**
     * Deletes a TransportRequirement entity.
     *
     * @Route("/{id}/delete", name="transportrequirement_delete")
     * @Method("GET")
     * @Security("has_role('ROLE_SYSTEM_ADMIN')")
     */
    public function deleteAction($id)
    {

        $em = $this->getDoctrine()->getManager();
        $transportRequirement = $em->getRepository('AppBundle:TransportRequirement')->find($id);

        if ($transportRequirement) {
            $countPersonsWithTransportRequirement = $em->getRepository('AppBundle:Person')->countPeopleWithTransportRequirement($transportRequirement);
            if ($countPersonsWithTransportRequirement > 0) {
                $this->addFlash("error", 'Transport Requirement "' . $transportRequirement->getName() . '" could not be deleted because there are Person-entities with reference to this entity.');
            } else {
                $em = $this->getDoctrine()->getManager();
                $em->remove($transportRequirement);
                $em->flush();
            }
        }

        return $this->redirectToRoute('transportrequirement_index');
    }

}
