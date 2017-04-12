<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use AppBundle\Entity\MedicalRequirement;
use AppBundle\Form\MedicalRequirementType;


/**
 * MedicalRequirement controller.
 *
 * @package AppBundle\Controller
 * @Route("/medicalrequirement")
 */
class MedicalRequirementController extends Controller
{
    /**
     * Lists all MedicalRequirement entities.
     *
     * @Route("/", name="medicalrequirement_index")
     * @Method("GET")
     * @Security("has_role('ROLE_SYSTEM_ADMIN')")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $medicalRequirements = $em->getRepository('AppBundle:MedicalRequirement')->findAll();

        return $this->render('medicalrequirement/index.html.twig', array(
            'medicalRequirements' => $medicalRequirements,
        ));
    }

    /**
     * Creates a new MedicalRequirement entity.
     *
     * @Route("/new", name="medicalrequirement_new")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_SYSTEM_ADMIN')")
     */
    public function newAction(Request $request)
    {
        $medicalRequirement = new MedicalRequirement();
        $form = $this->createForm('AppBundle\Form\MedicalRequirementType', $medicalRequirement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($medicalRequirement);
            $em->flush();

            return $this->redirectToRoute('medicalrequirement_index');
        }

        return $this->render('medicalrequirement/new.html.twig', array(
            'medicalRequirement' => $medicalRequirement,
            'form' => $form->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing MedicalRequirement entity.
     *
     * @Route("/{id}/edit", name="medicalrequirement_edit")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_SYSTEM_ADMIN')")
     */
    public function editAction(Request $request, MedicalRequirement $medicalRequirement)
    {
        $editForm = $this->createForm('AppBundle\Form\MedicalRequirementType', $medicalRequirement);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($medicalRequirement);
            $em->flush();

            return $this->redirectToRoute('medicalrequirement_index');
        }

        return $this->render('medicalrequirement/edit.html.twig', array(
            'medicalRequirement' => $medicalRequirement,
            'edit_form' => $editForm->createView(),
        ));
    }

    /**
     * Deletes a MedicalRequirement entity.
     *
     * @Route("/{id}/delete", name="medicalrequirement_delete")
     * @Method("GET")
     * @Security("has_role('ROLE_SYSTEM_ADMIN')")
     */
    public function deleteAction($id)
    {
        
        $em = $this->getDoctrine()->getManager();
        $medicalRequirement = $em->getRepository('AppBundle:MedicalRequirement')->find($id);

        if ($medicalRequirement) {
            $countPersonsWithMedicalRequirement = $em->getRepository('AppBundle:Person')->countPeopleWithMedicalRequirement($medicalRequirement);
            if ($countPersonsWithMedicalRequirement > 0) {
                $this->addFlash("error", 'Medical Requirement "' . $medicalRequirement->getName() . '" could not be deleted because there are Person-entities with reference to this entity.');
            } else {
                $em = $this->getDoctrine()->getManager();
                $em->remove($medicalRequirement);
                $em->flush();
            }
        }

        return $this->redirectToRoute('medicalrequirement_index');
    }
}
