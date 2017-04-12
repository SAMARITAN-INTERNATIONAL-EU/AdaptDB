<?php

namespace AppBundle\Repository;

use AppBundle\Entity\MedicalRequirement;
use AppBundle\Entity\Person;
use AppBundle\Entity\TransportRequirement;
use AppBundle\Entity\VulnerabilityLevel;
use AppBundle\Entity\DataChangeHistory;

/**
 * Class DataChangeHistoryRepository
 * @package AppBundle\Repository
 */
class DataChangeHistoryRepository extends \Doctrine\ORM\EntityRepository
{

    function findDataChangesToSendEmailsFor($notModifiedMinutesThreshold) {

        $changesUntilThisTimestamp = strtotime('-' . intval($notModifiedMinutesThreshold) .' minutes');
        $changesUntilThisDataTime = new \DateTime();
        $changesUntilThisDataTime->setTimestamp($changesUntilThisTimestamp);

        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('dch')
            ->from('AppBundle:DataChangeHistory', 'dch')
            ->where('dch.timestamp <= :changesUntilThisDataTime')
            ->setParameter('changesUntilThisDataTime', $changesUntilThisDataTime->format("Y-m-d H:i:s"))
            ->andWhere('dch.sendEmailCronjobDone = 0');

        return $queryBuilder->getQuery()->getResult();
    }
}
