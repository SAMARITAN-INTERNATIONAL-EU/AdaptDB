<?php

namespace AppBundle\Repository;

/**
 * Class PersonMissingInDataSourceRepository
 * @package AppBundle\Repository
 */
class PersonMissingInDataSourceRepository extends \Doctrine\ORM\EntityRepository
{

    /**
     * This function finds all PersonMissingInDataSource-items that haven't been checked
     */
    function findAllWithoutChecked() {

        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('personMissingInDs')
            ->from('AppBundle:PersonMissingInDataSource', 'personMissingInDs')
            ->where('personMissingInDs.hiddenByUser is null')
            ->orderBy('personMissingInDs.created', "ASC");

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * This function finds all PersonMissingInDataSource-items for the list on the legacyData page
     */
    function findForLegacyDataList() {

        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
        ->select('personMissingInDs, ds, p')
        ->from('AppBundle:PersonMissingInDataSource', 'personMissingInDs')
        ->leftJoin("personMissingInDs.dataSource", "ds")
        ->leftJoin("personMissingInDs.person", "p")
        ->where('personMissingInDs.hiddenByUser is null')
        ->orderBy('personMissingInDs.created', "ASC");

        return $queryBuilder->getQuery()->getResult();
    }
}
