<?php
namespace AppBundle\Service;
use AppBundle\Entity\Address;
use AppBundle\Entity\PersonAddress;
use AppBundle\Entity\Street;
use AppBundle\Entity\Zipcode;
use Doctrine\ORM\EntityManager;

/**
 * Class RoleHelperService
 * @package AppBundle\Service
 */
class RoleHelperService
{

    public static function getUserHasOnlyRescueWorkerRole(EntityManager $em, $userRoles) {
        //At least 2 roles: ROLE_RESCUE_WORKER + ROLE_USER
        return (count($userRoles) <= 2) && ($userRoles[0] == "ROLE_RESCUE_WORKER");
    }

    public static function getActiveEmergenciesExist($em) {
        return ($em->getRepository('AppBundle:Emergency')->findOneBy(array('isActive' => true)) != null);
    }
}

