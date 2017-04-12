<?php

namespace AppBundle\Repository;

/**
 * Class UserRepository
 * @package AppBundle\Repository
 */
class UserRepository extends \Doctrine\ORM\EntityRepository
{

    /**
     * Gets all AuthTokens for the given user
     * @return array
     */
    function getAuthTokensOfUser($userId) {

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('at')
            ->from('AppBundle:AuthToken', 'at')
            ->join("at.apiKey", "ak")
            ->join("ak.user", "u")
            ->where("u.id = :userId")
            ->setParameter("userId", $userId);

        return $qb->getQuery()->getResult();

    }
}
