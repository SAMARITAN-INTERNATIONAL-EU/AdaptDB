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
 * @Route("/legacydata")
 */
class LegacyDataController extends Controller
{
    /**
     * Shows the page with 3 tabs for Legacy Data
     * InitialTab can be one of these values (validuntil, missing, noupdates)
     *
     * @Route("/{initalTab}", name="legacydata_index")
     * @Method("GET")
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function indexAction($initalTab = "validuntil")
    {

        $em = $this->getDoctrine()->getManager();
        
        $personAddressesValidUntil = $em->getRepository('AppBundle:Person')->findWhereValidUntilIsExceeded();
        $personsMissingInDataSource = $em->getRepository('AppBundle:PersonMissingInDataSource')->findForLegacyDataList();
        $personsNotRecentlyUpdated = $em->getRepository('AppBundle:Person')->findPersonsNotRecentlyUpdated();

        return $this->render('legacydata/index.html.twig', array(
            'initialTab' => $initalTab,
            'personAddressesValidUntil' => $personAddressesValidUntil,
            'personsMissingInDataSource' => $personsMissingInDataSource,
            'personsNotRecentlyUpdated' => $personsNotRecentlyUpdated
        ));
    }
    
}
