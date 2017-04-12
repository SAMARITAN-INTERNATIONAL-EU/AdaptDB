<?php

namespace AppBundle\Repository;

use AppBundle\Entity\MedicalRequirement;
use AppBundle\Entity\Person;
use AppBundle\Entity\TransportRequirement;
use AppBundle\Entity\VulnerabilityLevel;
use AppBundle\Entity\DataChangeHistory;

/**
 * Class ImportRepository
 * @package AppBundle\Repository
 */
class ImportRepository extends \Doctrine\ORM\EntityRepository
{

    function findGroupedByDataSource() {

        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('im')
            ->from('AppBundle:Import', 'im')
            ->groupBy('im.dataSource');

        return $queryBuilder->getQuery()->getResult();
    }
}
