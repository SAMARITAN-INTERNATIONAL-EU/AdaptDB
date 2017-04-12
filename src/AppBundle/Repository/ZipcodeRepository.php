<?php

namespace AppBundle\Repository;

/**
 * Class ZipcodeRepository
 * @package AppBundle\Repository
 */
class ZipcodeRepository extends \Doctrine\ORM\EntityRepository
{

    /**
     * This function gets all zipcodes for the AddSpecial menu on the find page
     * @return array
     */
    function findZipcodesForAddSpecial() {

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('zc.id, zc.zipcode, zc.city')
        ->from('AppBundle:Zipcode', 'zc')
        ->addOrderBy('zc.zipcode', 'ASC');

        return $qb->getQuery()->getResult();

    }

    /**
     * This function gets all zipcodes for the typeahead-autocompleter.
     * @return array
     */
    function findZipcodesForAutocompletion() {

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('zc.id, concat(zc.zipcode, concat('. $qb->expr()->literal(' ') .  ', zc.city )) as name')
            ->from('AppBundle:Zipcode', 'zc')
            ->addOrderBy('zc.zipcode', 'ASC');

        return $qb->getQuery()->getResult();

    }
}
