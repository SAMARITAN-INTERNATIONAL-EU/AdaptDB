<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use AppBundle\Entity\Country;
use AppBundle\Form\CountryType;

/**
 * Country controller.
 *
 * @package AppBundle\Controller
 * @Route("/country")
 */
class CountryController extends Controller
{
    /**
     * Lists all Country entities.
     *
     * @Route("/", name="country_index")
     * @Method("GET")
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $countries = $em->getRepository('AppBundle:Country')->findAll();

        return $this->render('country/index.html.twig', array(
            'countries' => $countries,
        ));
    }

    /**
     * Creates a new Country entity.
     *
     * @Route("/new", name="country_new")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function newAction(Request $request)
    {
        $country = new Country();
        $form = $this->createForm('AppBundle\Form\CountryType', $country);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($country);
            $em->flush();

            return $this->redirectToRoute('country_index');
        }

        return $this->render('country/new.html.twig', array(
            'country' => $country,
            'form' => $form->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Country entity.
     *
     * @Route("/{id}/edit", name="country_edit")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function editAction(Request $request, Country $country)
    {
        $editForm = $this->createForm('AppBundle\Form\CountryType', $country);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($country);
            $em->flush();

            return $this->redirectToRoute('country_index');
        }

        return $this->render('country/edit.html.twig', array(
            'country' => $country,
            'edit_form' => $editForm->createView(),
        ));
    }

// Delete function could be activated if required
// for now it is not active because deleting countries could cause problems with addresses
//    /**
//     * Deletes a Country entity.
//     *
//     * @Route("/{id}/delete", name="country_delete")
//     * @Method("GET")
//     * @Security("has_role('ROLE_DATA_ADMIN')")
//     */
//    public function deleteAction($id)
//    {
//
//        $em = $this->getDoctrine()->getManager();
//        $countryToDelete = $em->getRepository('AppBundle:Country')->find($id);
//
//        if ($countryToDelete) {
//
//            $em = $this->getDoctrine()->getManager();
//            $em->remove($countryToDelete);
//            $em->flush();
//        }
//
//        return $this->redirectToRoute('country_index');
//    }
}
