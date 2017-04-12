<?php

namespace AppBundle\Controller;

use AppBundle\Entity\PersonPotentialIdentity;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use AppBundle\Entity\PotentialIdentity;
use AppBundle\Form\PotentialIdentityType;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

/**
 * PersonMissingInDataSource controller.
 *
 * @package AppBundle\Controller
 * @Route("/personMissingInDataSource")
 */
class PersonMissingInDataSourceController extends Controller
{

    /**
     *
     * @Route("/markAsChecked/{personMissingInDataSourceId}", name="person_missing_in_data_source_mark_as_checked")
     * @Method({"GET"})
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function markAsCheckedAction($personMissingInDataSourceId) {

        $em = $this->getDoctrine()->getManager();

        $personMissingInDataSource = $em->getRepository('AppBundle:PersonMissingInDataSource')->find($personMissingInDataSourceId);

        if ($personMissingInDataSource === null) {
            throw new \Doctrine\ORM\EntityNotFoundException();
        }

        $personMissingInDataSource->setHiddenByUser($this->getUser());
        $personMissingInDataSource->setHiddenTimestamp(new \DateTime);

        $em->persist($personMissingInDataSource);

        $em->flush();

        return $this->redirectToRoute("legacydata_index", array("initalTab" => "missing"));
    }
}
