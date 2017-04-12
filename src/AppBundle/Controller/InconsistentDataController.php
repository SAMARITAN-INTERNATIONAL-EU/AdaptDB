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
 * Inconsistent data controller.
 *
 * @package AppBundle\Controller
 * @Route("/inconsistentdata")
 */
class InconsistentDataController extends Controller
{
    /**
     * Lists all Country entities.
     *
     * @Route("/", name="inconsistentdata_index")
     * @Method("GET")
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $InconsistentPIs = $em->getRepository('AppBundle:InconsistentPI')->findAllForInconsistentDataIndex();

        return $this->render('inconsistentdata/index.html.twig', array(
           'inconsistentPIs' => $InconsistentPIs
        ));
    }


    /**
     *
     *
     * @Route("/removeInconsistentPIById/{id}", name="removeInconsistentPIById")
     * @Method("GET")
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function removeInconsistentPIByIdAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $inconsistentPIToBeRemoved = $em->getRepository('AppBundle:InconsistentPI')->find($id);

        if ($inconsistentPIToBeRemoved === null ) {
            throw new \Doctrine\ORM\EntityNotFoundException();
        }

        $em->remove($inconsistentPIToBeRemoved);
        $em->flush();

        return $this->redirectToRoute("inconsistentdata_index");
    }
}
