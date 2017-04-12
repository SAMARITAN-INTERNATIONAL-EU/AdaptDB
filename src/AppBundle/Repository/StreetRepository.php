<?php

namespace AppBundle\Repository;

/**
 * Class StreetRepository
 * @package AppBundle\Repository
 */
class StreetRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * @param $emergencyId
     * @return array
     */
    function findStreetsByEmergencyId($emergencyId) {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('s.id, s.name, zc.zipcode, zc.city')
            ->from('AppBundle:Street', 's')
            ->join('s.emergencies', 'e')
            ->leftJoin('s.zipcode', 'zc')
            ->where('e.id = :emergencyId')
            ->setParameter('emergencyId', $emergencyId)
            ->addOrderBy('s.name', 'ASC');

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @return array
     */
    function findAll_forStreetsListOnFindPage() {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('s.id, s.name, zc.zipcode, zc.city')
            ->from('AppBundle:Street', 's')
            ->leftJoin('s.emergencies', 'e')
            ->leftJoin('s.zipcode', 'zc')
            ->addOrderBy('s.name', 'ASC');

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param $zipcodeIdArray
     * @return array
     */
    function findStreetsByZipcodeIdsArray_forStreetsListOnFindPage($zipcodeIdArray) {

        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('s.id, s.name, zc.zipcode, zc.city')
            ->from('AppBundle:Street', 's')
            ->join('s.zipcode', 'zc');

        $counter=0;
        foreach ($zipcodeIdArray as $zipcodeId) {
            $queryBuilder->orWhere('zc.id = :zipcodeId'.$counter)
                ->setParameter('zipcodeId'.$counter, $zipcodeId);
            //the parameter-name needs to be unique
            $counter++;
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param $normalizedNameQuery
     * @return array
     */
    function findStreetsByNameForAutocompleter($normalizedNameQuery) {
        $normalizedNameQuery = '%'.$normalizedNameQuery.'%';

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('s.id, s.name, zc.zipcode, zc.city')
            ->from('AppBundle:Street', 's')
            ->join('s.zipcode', 'zc')
            ->where('s.nameNormalized LIKE :normalizedNameQuery')
            ->setParameter('normalizedNameQuery', $normalizedNameQuery)
            ->addOrderBy('s.name', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param $query
     * @return array
     */
    function findStreetsByName($query) {
        $query = str_replace("*", "%", $query);

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('s.id, s.name, zc.zipcode, zc.city')
            ->from('AppBundle:Street', 's')
            ->join('s.zipcode', 'zc')
            ->where('s.nameNormalized LIKE :normalizedNameQuery')
            ->setParameter('normalizedNameQuery', $query)
            ->addOrderBy('s.name', 'ASC');

        return $qb->getQuery()->getResult();
    }
}
