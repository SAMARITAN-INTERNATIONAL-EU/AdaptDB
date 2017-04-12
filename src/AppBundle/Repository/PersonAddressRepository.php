<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Person;
use AppBundle\Form\PeopleAddressesFilterType;
use AppBundle\Service\NameNormalizationService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Class PersonAddressRepository
 * @package AppBundle\Repository
 */
class PersonAddressRepository extends \Doctrine\ORM\EntityRepository
{

     /**
     * @param $string
     * @return mixed
     */
    function replaceWildcards($string) {
        return str_replace("*", "%", $string);
    }

    public function filterPersonAddressesForPotentialIdentityExcludingPerson($filterArray, Person $person) {

        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('pa, p, pi, persons, pad, ad, zc, st')
            ->from('AppBundle:PersonAddress', 'pa')
            ->innerJoin('pa.person', 'p')
            ->leftJoin('p.potentialIdentity', 'pi')
            ->leftJoin('pi.persons', 'persons')
            ->leftJoin('persons.personAddresses', 'pad')
            ->leftJoin('pad.address', 'ad')
            ->leftJoin('ad.street', 'st')
            ->leftJoin('st.zipcode', 'zc');


        if (key_exists('fiscalCode', $filterArray)) {
            $queryBuilder->andWhere('p.fiscalCode LIKE :fiscalCode')
                ->setParameter('fiscalCode', $this->replaceWildcards($filterArray['fiscalCode']));
        }

        if (key_exists('firstName', $filterArray)) {
            $queryBuilder->andWhere('p.firstName LIKE :firstName')
                ->setParameter('firstName', $this->replaceWildcards($filterArray['firstName']));
        }

        if (key_exists('lastName', $filterArray)) {
            $queryBuilder->andWhere('p.lastName LIKE :lastName')
                ->setParameter('lastName', $this->replaceWildcards($filterArray['lastName']));
        }

        if (key_exists('streetName', $filterArray)) {
            $queryBuilder->andWhere('st.name LIKE :streetName')
                ->setParameter('streetName', $this->replaceWildcards($filterArray['streetName']));
        }

        if (key_exists('houseNr', $filterArray)) {
            $queryBuilder->andWhere('ad.houseNr LIKE :streetNr')
                ->setParameter('streetNr', $this->replaceWildcards($filterArray['houseNr']));
        }

        if (key_exists('zipcode', $filterArray)) {
            $queryBuilder->andWhere('zc.zipcode LIKE :zipcode')
                ->setParameter('zipcode', $this->replaceWildcards($filterArray['zipcode']));
        }

        if (key_exists('city', $filterArray)) {
            $queryBuilder->andWhere('zc.city LIKE :city')
                ->setParameter('city', $this->replaceWildcards($filterArray['city']));
        }

        //Exclude person
        $queryBuilder->andWhere('p.id != :personId')
            ->setParameter('personId', $person->getId());

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * This function finds personAddresses where the "Absence To" date is in the past
     * The SetIsActiveBasedOnValidUntilDateCommand removes the "Absence From" and "Absence To" values
     * and sets isActive to true
     *
     */
    function findWhereAbsenceToIsExceeded() {

        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('pa')
            ->from('AppBundle:PersonAddress', 'pa')
            ->where('pa.absenceTo <= :today')
            ->setParameter('today', new \DateTime());

        return $queryBuilder->getQuery()->getResult();
    }

}
