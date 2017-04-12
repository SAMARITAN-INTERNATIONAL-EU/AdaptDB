<?php

namespace AppBundle\Repository;

use AppBundle\Entity\PotentialIdentityCluster;

/**
 * Class PotentialIdentityClusterRepository
 * @package AppBundle\Repository
 */
class PotentialIdentityClusterRepository extends \Doctrine\ORM\EntityRepository
{

    function findCreatedForPersonIds($personsIdsArray) {

        if (count($personsIdsArray)==0) {
            return array();
        }

        $queryBuilder = $this->getEntityManager()->createQueryBuilder("p")
            ->select('pic')
            ->from("AppBundle:PotentialIdentityCluster", "pic")
            ->join("pic.persons", "p");

        $orWhereArray = array();

        foreach ($personsIdsArray as $personId) {
            $orWhereArray[] = "p.id = ". $personId;
        }

        $queryBuilder->where(implode(" OR ", $orWhereArray));

        $queryBuilder->andWhere("pic.wasCreated = 1");
        $queryBuilder->addOrderBy("pic.timestampModified", "DESC");

        return $queryBuilder->getQuery()->getResult();
    }

}
