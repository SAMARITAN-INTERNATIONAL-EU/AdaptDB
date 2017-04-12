<?php

namespace AppBundle\Repository;

/**
 * Class GeoAreaRepository
 * @package AppBundle\Repository
 */
class GeoAreaRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * @param $emergencyId
     * @return array
     */
    function findAllCustomGeoAreasForEmergencyId($emergencyId)
    {

        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('ga')
            ->from('AppBundle:GeoArea', 'ga')
            ->where('ga.name LIKE :geoAreaCustomName')
            ->setParameter('geoAreaCustomName', 'custom%')
            ->andWhere('ga.emergency = :emergencyId')
            ->setParameter('emergencyId', $emergencyId);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param $geoAreasIdArray
     * @return array
     */
    function findGeoAreasByIdArray($geoAreasIdArray)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('ga, gp')
            ->from('AppBundle:GeoArea', 'ga')
            ->join("ga.geoPoints", "gp");

        foreach ($geoAreasIdArray as $geoAreaId) {
            $queryBuilder->orWhere('ga.id = ' . $geoAreaId);
        }

        return $queryBuilder->getQuery()->getResult();
    }

}
