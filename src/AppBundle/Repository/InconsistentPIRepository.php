<?php

namespace AppBundle\Repository;

use AppBundle\Entity\MedicalRequirement;
use AppBundle\Entity\Person;
use AppBundle\Entity\TransportRequirement;
use AppBundle\Entity\VulnerabilityLevel;

/**
 * Class InconsistentPIRepository
 * @package AppBundle\Repository
 */
class InconsistentPIRepository extends \Doctrine\ORM\EntityRepository
{

    function findAllForInconsistentDataIndex() {

        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('ipi, p, pi, pa, ad, st, zc, mreq, treq, vul')
            ->from('AppBundle:InconsistentPI', 'ipi')
            ->innerJoin('ipi.potentialIdentity', 'pi')
            ->innerJoin('pi.persons', 'p')
            ->leftJoin('p.personAddresses', 'pa')
            ->leftJoin('pa.address', 'ad')
            ->leftJoin('ad.street', 'st')
            ->leftJoin('st.zipcode', 'zc')
            ->leftJoin('p.medicalRequirements', 'mreq')
            ->leftJoin('p.transportRequirements', 'treq')
            ->leftJoin('p.vulnerabilityLevel', 'vul')
            ->where('ipi.hidden = 0');

        return $queryBuilder->getQuery()->getResult();

    }
}
