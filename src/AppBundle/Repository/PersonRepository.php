<?php

namespace AppBundle\Repository;

use AppBundle\Entity\GeoArea;
use AppBundle\Entity\GeoPoint;
use AppBundle\Entity\MedicalRequirement;
use AppBundle\Entity\Person;
use AppBundle\Entity\TransportRequirement;
use AppBundle\Entity\VulnerabilityLevel;
use AppBundle\Form\PeopleAddressesFilterType;
use AppBundle\Service\NameNormalizationService;
use AppBundle\Service\PersonListFilterArray;
use AppBundle\Service\PersonListMode;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Class PersonRepository
 * @package AppBundle\Repository
 */
class PersonRepository extends \Doctrine\ORM\EntityRepository
{

    function findPersonsOfPiByPiIdAndEmergency($piId, $emergencyId, $exactPolygonMatchMode, $emergencyGeoAreas = null) {

        if ($emergencyId) {
            $selectString = 'p, ds, cp, pa, ad, gp, st, zc, vl, medReq, traReq, safety';
        } else {
            $selectString = 'p, ds, cp, pa, ad, gp, st, zc, vl, medReq, traReq';
        }

        $queryBuilder = $this->getEntityManager()->createQueryBuilder("p")
            ->select($selectString)
            ->from("AppBundle:Person", "p")
            ->join('p.dataSource', 'ds')
            ->leftJoin('p.contactPersons', 'cp')
            ->leftJoin('p.personAddresses', 'pa')
            ->leftJoin('pa.address', 'ad')
            ->leftJoin('ad.geopoint', 'gp')
            ->leftJoin('ad.street', 'st')
            ->leftJoin('st.zipcode', 'zc')
            ->join('p.vulnerabilityLevel', 'vl')
            ->leftJoin('p.medicalRequirements', 'medReq')
            ->leftJoin('p.transportRequirements', 'traReq')
            ->andWhere('IDENTITY(p.potentialIdentity) = :piId')
            ->setParameter("piId", $piId)
            ->orderBy("ds.isOfficial", "DESC");

        if ($emergencyId) {
            $queryBuilder
                ->leftJoin('p.emergencySafetyStatuses', 'safety')
                ->andWhere('safety.emergency = :emergencyId')
                ->setParameter('emergencyId', $emergencyId);
        }

        if ($emergencyGeoAreas) {
            $this->addIsInPolygonQueries($queryBuilder, $emergencyGeoAreas, $exactPolygonMatchMode);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    function findPersonsOfPiByPiId($piId) {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder("p")
            ->select('p, pi, ds, cp, pa, ad, st, zc, vl,medReq, traReq')
            ->from("AppBundle:Person", "p")
            ->join('p.potentialIdentity', 'pi')
            ->join('p.dataSource', 'ds')
            ->leftJoin('p.contactPersons', 'cp')
            ->leftJoin('p.personAddresses', 'pa')
            ->leftJoin('pa.address', 'ad')
            ->leftJoin('ad.street', 'st')
            ->leftJoin('st.zipcode', 'zc')
            ->join('p.vulnerabilityLevel', 'vl')
            ->leftJoin('p.medicalRequirements', 'medReq')
            ->leftJoin('p.transportRequirements', 'traReq')
            ->where('pi.id = :piId')
            ->orderBy("ds.isOfficial", "DESC")
            ->setParameter("piId", $piId);
        return $queryBuilder->getQuery()->getResult();
    }

    function countPeopleWithMedicalRequirement(MedicalRequirement $medicalRequirement) {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder("p")
            ->select('COUNT(p.id)')
            ->from("AppBundle:Person", "p")
            ->join('p.medicalRequirements', 'medreq')
            ->where('medreq.id = :medReqId')
            ->setParameter("medReqId", $medicalRequirement->getId());
        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    function countPeopleWithTransportRequirement(TransportRequirement $transportRequirement) {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder("p")
            ->select('COUNT(p.id)')
            ->from("AppBundle:Person", "p")
            ->join('p.transportRequirements', 'trareq')
            ->where('trareq.id = :traReqId')
            ->setParameter("traReqId", $transportRequirement->getId());
        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    function findPeopleInRectangle($geoPoint1Lat, $geoPoint1Lng, $geoPoint2Lat, $geoPoint2Lng, $emergencyId, $exactPolygonMatchMode = null, $emergencyGeoAreas = null) {

        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        if ($emergencyId) {
            $queryBuilder->select('p, pa, epss');
        } else {
            $queryBuilder->select('p, pa');
        }

        $queryBuilder
            ->select('p, pa')
            ->from('AppBundle:Person', 'p')
            ->leftJoin('p.personAddresses', 'pa')
            ->join('pa.address', 'a')
            ->join('a.geopoint', 'gp');

        if ($emergencyId) {
            $queryBuilder->join('p.emergencySafetyStatuses', 'epss');
        }

        $queryBuilder->where('gp.lat <= :geoPoint1Lat')
        ->setParameter('geoPoint1Lat', $geoPoint1Lat)
        ->andWhere('gp.lng >= :geoPoint1Lng')
        ->setParameter('geoPoint1Lng', $geoPoint1Lng)
        ->andWhere('gp.lat >= :geoPoint2Lat')
        ->setParameter('geoPoint2Lat', $geoPoint2Lat)
        ->andWhere('gp.lng <= :geoPoint2Lng')
        ->setParameter('geoPoint2Lng', $geoPoint2Lng);

        if ($emergencyId) {
            $queryBuilder->andWhere("IDENTITY(epss.emergency) = :emergencyId")
                ->setParameter("emergencyId", $emergencyId);
        }

        if ($emergencyGeoAreas) {
            $this->addIsInPolygonQueries($queryBuilder, $emergencyGeoAreas, $exactPolygonMatchMode);
        }


        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * This function finds person where the valid until date is exceeded
     * The results are shown on the "Legacy Data"-page
     */
    function findWhereValidUntilIsExceeded() {

        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('p, medReq, traReq')
            ->from('AppBundle:Person', 'p')
            ->leftJoin('p.medicalRequirements', 'medReq')
            ->leftJoin('p.transportRequirements', 'traReq')
            ->where('p.validUntil<=:today')
            ->orderBy('p.validUntil')
            ->addOrderBy('p.firstName')
            ->setParameter('today', new \DateTime());

        return $queryBuilder->getQuery()->getResult();
    }

    function findPersonsByPotentialIdentityWithSafetyStatusForEmergency($potentialIdentityId, $emergencyId) {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('p, safety')
            ->from('AppBundle:Person', 'p')
            ->where('p.potentialIdentity = :potentialIdentityId')
            ->setParameter('potentialIdentityId', $potentialIdentityId)
            ->leftJoin('p.emergencySafetyStatuses', 'safety')
            ->andWhere('safety.emergency = :emergencyId')
            ->setParameter('emergencyId', $emergencyId)
            ->andWhere('p.potentialIdentity = :potentialIdentityId')
            ->setParameter('potentialIdentityId', $potentialIdentityId);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Gets Person with PersonAddresses -
     * If the person is not within the $emergencyGeoAreas nothing is returned
     *
     * @param $personId
     * @param $emergencyId
     * @param $emergencyGeoAreas
     * @return mixed
     */
    function getPersonWithPersonAddresses($personId, $emergencyId, $exactPolygonMatchMode, $emergencyGeoAreas = null) {

        if ($emergencyId == null) {
            $queryBuilder = $this->getEntityManager()->createQueryBuilder()
                ->select('p, vul, mreq, treq, cp, pa, ad, st, zc')
                ->from('AppBundle:Person', 'p');
        } else {
            $queryBuilder = $this->getEntityManager()->createQueryBuilder()
                ->select('p, vul, mreq, treq, cp, pa, ad, st, zc, safety')
                ->from('AppBundle:Person', 'p')
                ->innerJoin('p.emergencySafetyStatuses', 'safety')
                ->where('safety.emergency = :emergencyId')
                ->setParameter('emergencyId', $emergencyId);
        }

         $queryBuilder
            ->innerJoin('p.vulnerabilityLevel', 'vul')
            ->leftJoin('p.medicalRequirements', 'mreq')
            ->leftJoin('p.transportRequirements', 'treq')
            ->leftJoin('p.contactPersons', 'cp')
            ->leftJoin('p.personAddresses', 'pa')
            ->leftJoin('pa.address', 'ad')
            ->leftJoin('ad.geopoint', 'gp')
            ->leftJoin('ad.street', 'st')
            ->leftJoin('st.zipcode', 'zc')
            ->AndWhere('p.id = :personId')
            ->setParameter('personId', $personId)
            ->orderBy('pa.isActive', 'ASC');

        if ($emergencyGeoAreas) {
            $this->addIsInPolygonQueries($queryBuilder, $emergencyGeoAreas, $exactPolygonMatchMode);
        }

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    private function addIsInPolygonQueries($queryBuilder, $emergencyGeoAreas, $exactPolygonMatchMode) {

        $multiPolygonArray = array();

        /** @var GeoArea $geoArea */
        foreach ($emergencyGeoAreas as $geoArea) {

            $polygonPartsArray = [];

            foreach ($geoArea->getGeoPoints() as $geoPoint) {

                if (!empty($geoPoint->getLat()) && !empty($geoPoint->getLng())) {
                    $polygonPartsArray[] = $geoPoint->getLat() . " " . $geoPoint->getLng();
                }
            }

            //Add the first GeoPoint to close the polygon
            $polygonPartsArray[] = $geoArea->getGeoPoints()[0]->getLat() . " " . $geoArea->getGeoPoints()[0]->getLng();

            $multiPolygonArray[] = "((" . implode(",", $polygonPartsArray) . "))";

            $multiPolygonString = "GeomFromText('Multipolygon(" . implode(",", $multiPolygonArray) . ")')";

            if ($exactPolygonMatchMode) {
                $queryBuilder->andWhere("ST_CONTAINS(" . $multiPolygonString . ", gp.point) = 1");
            } else {
                $queryBuilder->andWhere("MBRContains(" . $multiPolygonString . ", gp.point) = 1");
            }
        }

    }

    /**
     * This function finds person that have not changed recently (currently 1 year)
     * The results are shown on the "Legacy Data"-page
     */
    function findPersonsNotRecentlyUpdated() {

        //TODO Define variable time

        $dateTimeNow = new \DateTime();
        $dateTimeOneYearAgo = $dateTimeNow->modify( '-1 year' );

        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('p as person, MAX(dch.timestamp) as lastUpdateTimestamp')
            ->from('AppBundle:Person', 'p')
            ->innerJoin('AppBundle:DataChangeHistory', 'dch', 'WITH', 'dch.person = p.id')
            ->having('lastUpdateTimestamp <= :dateTimeOneYearAgo')
            ->setParameter('dateTimeOneYearAgo', $dateTimeOneYearAgo)
            ->orderBy('p.id', 'ASC')
            ->groupBy('p.id');

        $tmpResults = $queryBuilder->getQuery()->getResult();

        //Replace the date-string with an DataTime-object
        //This makes rendering it in the frontend more flexible
        foreach ($tmpResults as &$tmpResult) {
            $tmpResult["lastUpdateTimestamp"] = new \DateTime( $tmpResult["lastUpdateTimestamp"] );
        }

        return $tmpResults;
    }

    function findPersonsForPotentialIdentityExludingPerson($potentialIdentityId, $personIdToExlude, $emergencyId) {

        if ($emergencyId == null) {
            $queryBuilder = $this->getEntityManager()->createQueryBuilder()
                ->select('p, pi, pa, ad, st, zc, ds')
                ->from('AppBundle:Person', 'p');
        } else {
            $queryBuilder = $this->getEntityManager()->createQueryBuilder()
                ->select('p, pi, pa, ad, st, zc, safety')
                ->from('AppBundle:Person', 'p')
                ->innerJoin('p.emergencySafetyStatuses', 'safety')
                ->where('safety.emergency = :emergencyId')
                ->setParameter('emergencyId', $emergencyId);
        }

        $queryBuilder
            ->join('p.potentialIdentity', 'pi')
            ->join('p.dataSource', 'ds')
            ->leftJoin('p.personAddresses', 'pa')
            ->leftJoin('pa.address', 'ad')
            ->leftJoin('ad.street', 'st')
            ->leftJoin('st.zipcode', 'zc')
            ->andWhere('pi.id = :potentialIdentityId')
            ->setParameter('potentialIdentityId', $potentialIdentityId)
            ->andWhere('p.id != :personIdToExlude')
            ->setParameter('personIdToExlude', $personIdToExlude);

        return $queryBuilder->getQuery()->getResult();
    }


    /**
     * @param $string
     * @return mixed
     */
    function replaceWildcards($string) {
        return str_replace("*", "%", $string);
    }

    public function filterPersonsForPotentialIdentityExcludingPerson($filterArray, Person $person) {

        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('p, pi, pad, ad, zc, st, ds')
            ->from('AppBundle:Person', 'p')
            ->leftJoin('p.potentialIdentity', 'pi')
            ->leftJoin('p.personAddresses', 'pad')
            ->leftJoin('pad.address', 'ad')
            ->leftJoin('ad.street', 'st')
            ->leftJoin('st.zipcode', 'zc')
            ->join('p.dataSource', 'ds');

        $personIdsToExclude = array($person->getId());
        if ($person->getPotentialIdentity()) {
            foreach ($person->getPotentialIdentity()->getPersons() as $personOfPi) {
                $personIdsToExclude[] = $personOfPi->getId();
            }
        }

        if (key_exists('safetyStatus', $filterArray)) {
            if ($filterArray['safetyStatus'] == intval(PeopleAddressesFilterType::FILTER_SAFETYSTATUS_SAFE)) {
                $queryBuilder->andWhere('p.safetyStatus = 1');
            } else if ($filterArray['safetyStatus'] == intval(PeopleAddressesFilterType::FILTER_SAFETYSTATUS_NOTSAFE)) {
                $queryBuilder->andWhere('p.safetyStatus = 0');
            }
        }

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

        if (key_exists('dateOfBirth', $filterArray)) {
            $queryBuilder->andWhere('p.dateOfBirth = :dateOfBirth')
                ->setParameter('dateOfBirth', $filterArray['dateOfBirth']);
        }

        //Excludes all related Persons so that they cannot be added again
        foreach ($personIdsToExclude as $personIdToExclude) {
            $queryBuilder->andWhere('p.id != '. $personIdToExclude);
        }

        $queryBuilder->orderBy("p.fiscalCode", "ASC")
            ->addOrderBy("p.lastName", "ASC");

        return $queryBuilder->getQuery()->getResult();
    }

    public function findPersonsWithPersonAddressesForPiIdArray($personListMode, $piIdArray, $emergencyId, $exactPolygonMatchMode = null, $emergencyGeoAreas = null) {

        //piHelperId is
        //ID of potential identity if person has one
        //person-ID * (-1) if person has no potential identity
        //This is done to ensure that piHelperId is always different even when person-ID and pi-ID are the same
        $selectString = 'pa, p, ad, zc, st, pi, ds, COALESCE(pi.id, (p.id*(-1))) as piHelperId';

        if ($personListMode == PersonListMode::findVulnerablePeople) {
            $selectString .= ' ,medReq, traReq, vl, gp';
        }

        if ($emergencyId == null) {
            $queryBuilder = $this->getEntityManager()->createQueryBuilder()
                ->select($selectString)
                ->from('AppBundle:Person', 'p');
        } else {
            $queryBuilder = $this->getEntityManager()->createQueryBuilder()
                ->select($selectString . ", safety")
                ->from('AppBundle:Person', 'p')
                ->leftJoin('p.emergencySafetyStatuses', 'safety')
                ->where('safety.emergency = :emergencyId')
                ->setParameter('emergencyId', $emergencyId);
        }

        $queryBuilder
            ->leftJoin('p.dataSource', 'ds')
            ->leftJoin('p.potentialIdentity', 'pi')
            ->leftJoin('p.personAddresses', 'pa')
            ->leftJoin('pa.address', 'ad')
            ->leftJoin('ad.street', 'st')
            ->leftJoin('st.zipcode', 'zc');

        if ($personListMode == PersonListMode::findVulnerablePeople) {
            $queryBuilder
                ->leftJoin("p.medicalRequirements", "medReq")
                ->leftJoin("p.transportRequirements", "traReq")
                ->leftJoin("ad.geopoint", "gp")
                ->join("p.vulnerabilityLevel", "vl");
        }

        //build the query
        $orQueryArray = array();

        foreach ($piIdArray as $piId) {
            $orQueryArray[] = "IDENTITY(p.potentialIdentity) = " .$piId;
        }

        if (!empty($orQueryArray)) {
            $queryBuilder->andWhere(implode(" OR ", $orQueryArray));
        }

        $queryBuilder->orderBy("ds.isOfficial", "DESC");

        //Filter by emergencyGeoAreas if they are given
        if ($emergencyGeoAreas) {
            $this->addIsInPolygonQueries($queryBuilder, $emergencyGeoAreas, $exactPolygonMatchMode);
        }

        return $queryBuilder->getQuery()->getResult();

    }

    public function findForPersonList($personListMode, $filterArray,  NameNormalizationService $nameNormalizationService, $emergencyId, $orderKey, $orderValue, $currentPage, $exactPolygonMatchMode, $entitiesPerPage = null) {
        
        $selectString = "pa, p, ad, zc, ds, st, pi, gp, COALESCE(pi.id, (p.id*(-1))) as piHelperId";

        if ($personListMode == PersonListMode::findVulnerablePeople) {
            $selectString .= ", medReq, traReq, vl";
        }

        if ($emergencyId == null) {
            $queryBuilder = $this->getEntityManager()->createQueryBuilder()
                //Grouping by piHelperId later / Grouping by PI does not work because persons without PI are not
                //in the results.
                ->select($selectString)
                ->from('AppBundle:Person', 'p');
        } else {
            $queryBuilder = $this->getEntityManager()->createQueryBuilder()
                ->select($selectString . ", safety")
                ->from('AppBundle:Person', 'p')
                ->leftJoin('p.emergencySafetyStatuses', 'safety')
                ->where('safety.emergency = :emergencyId')
                ->setParameter('emergencyId', $emergencyId);
        }

            $queryBuilder
                ->leftJoin('p.potentialIdentity', 'pi')
                ->leftJoin('p.personAddresses', 'pa')
                ->leftJoin('pa.address', 'ad')
                ->leftJoin('ad.street', 'st')
                ->leftJoin('st.zipcode', 'zc')
                ->leftJoin('p.dataSource', 'ds')
                ->leftJoin("ad.geopoint", "gp");

        if ($personListMode == PersonListMode::findVulnerablePeople) {
            $queryBuilder
                ->leftJoin("p.medicalRequirements", "medReq")
                ->leftJoin("p.transportRequirements", "traReq")
                ->join("p.vulnerabilityLevel", "vl");
        }

        if (key_exists(PersonListFilterArray::geoAreas, $filterArray)) {
            $geoAreas = $filterArray[PersonListFilterArray::geoAreas];

            $this->addIsInPolygonQueries($queryBuilder, $geoAreas, $exactPolygonMatchMode);
        }

        if (key_exists('isActive', $filterArray)) {
            switch ($filterArray['isActive'])
            {
                case PeopleAddressesFilterType::FILTER_ISACTIVE_ACTIVE:
                    $queryBuilder->andWhere('pa.isActive = 1');
                    break;
                case PeopleAddressesFilterType::FILTER_ISACTIVE_INACTIVE:
                    $queryBuilder->andWhere('pa.isActive = 0');
                    break;
                case PeopleAddressesFilterType::FILTER_ISACTIVE_NOADDRESS:
                    $queryBuilder->andWhere('pa.address is null');
                    break;
            }
        }

        if (key_exists('safetyStatus', $filterArray)) {
            switch ($filterArray['safetyStatus']) {
                case PeopleAddressesFilterType::FILTER_SAFETYSTATUS_SAFE:
                    $queryBuilder->andWhere('safety.safetyStatus = 1');
                    break;
                case PeopleAddressesFilterType::FILTER_SAFETYSTATUS_NOTSAFE:
                    $queryBuilder->andWhere('safety.safetyStatus = 0');
                    break;
            }
        }

        if (key_exists(PersonListFilterArray::fiscalCode, $filterArray)) {
            $queryBuilder->andWhere('p.fiscalCode LIKE :fiscalCode')
                ->setParameter('fiscalCode', $this->replaceWildcards($filterArray[PersonListFilterArray::fiscalCode]));
        }

        if (key_exists(PersonListFilterArray::firstName, $filterArray)) {
            $queryBuilder->andWhere('p.firstName LIKE :firstName')
                ->setParameter('firstName', $this->replaceWildcards($filterArray[PersonListFilterArray::firstName]));
        }

        if (key_exists(PersonListFilterArray::lastName, $filterArray)) {
            $queryBuilder->andWhere('p.lastName LIKE :lastName')
                ->setParameter('lastName', $this->replaceWildcards($filterArray[PersonListFilterArray::lastName]));
        }

        if (key_exists(PersonListFilterArray::dateOfBirth, $filterArray)) {
            $queryBuilder->andWhere('p.dateOfBirth LIKE :dateOfBirth')
                ->setParameter('dateOfBirth', $filterArray[PersonListFilterArray::dateOfBirth]->format('Y-m-d'));
        }

        if (key_exists('age', $filterArray) && intval($filterArray['age'])>= 0) {
            $dateTimeNow = new \DateTime();

            if (isset ($filterArray[PersonListFilterArray::ageGrSm])) {
                $queryAgeGrSm = $filterArray[PersonListFilterArray::ageGrSm];
                $dateTimeForAge = $dateTimeNow->modify( '-' . intval($filterArray[PersonListFilterArray::age]) . ' year' );

                if ($queryAgeGrSm == 'greater') {
                    $queryBuilder->andWhere('p.dateOfBirth <= :dateTimeForAge');
                } else {
                    $queryBuilder->andWhere('p.dateOfBirth >= :dateTimeForAge');
                }
                $queryBuilder->setParameter('dateTimeForAge', $dateTimeForAge);
            }
        }

        if (key_exists(PersonListFilterArray::floor, $filterArray)) {
            if (isset ($filterArray[PersonListFilterArray::floorGrSm])) {
                $queryFloorGrSm = $filterArray[PersonListFilterArray::floorGrSm];
                if ($queryFloorGrSm == 'greater') {
                    $queryBuilder->andWhere('pa.floor >= :floor');
                } else {
                    $queryBuilder->andWhere('pa.floor <= :floor');
                }
                $queryBuilder->setParameter('floor', $filterArray[PersonListFilterArray::floor]);
            } else {
                $queryBuilder->andWhere('pa.floor <= :floor')
                    ->setParameter('floor', $filterArray[PersonListFilterArray::floor]);
            }
        }

        if (key_exists(PersonListFilterArray::selectedStreetIds, $filterArray)) {
            $selectedStreetIds = $filterArray[PersonListFilterArray::selectedStreetIds];

            $streetIdQuerysArray = array();

            foreach ($selectedStreetIds as $streetId) {
                $streetIdQuerysArray[] = 'st.id = ' . $streetId;
            }

            $streetIdQueryString = "(" . implode($streetIdQuerysArray, " OR ") . ")";

            //To find also those persons with no address
            $streetIdQueryString .= " OR st.id IS NULL";
            $queryBuilder->andWhere($streetIdQueryString);
        }

        if (key_exists(PersonListFilterArray::remarks, $filterArray)) {
            $queryBuilder->andWhere('p.remarks LIKE :remarks')
                ->setParameter('remarks', $this->replaceWildcards($filterArray[PersonListFilterArray::remarks]));
        }

        $normalizedStreetName = "";

        if (isset($filterArray[PersonListFilterArray::streetName])) {
            $firstCharsWasAsterisk = substr($filterArray[PersonListFilterArray::streetName], 0, 1) == "*";
            $lastCharsWasAsterisk = substr($filterArray[PersonListFilterArray::streetName], strlen($filterArray[PersonListFilterArray::streetName])-1, 1) == "*";

            $normalizedStreetName = $nameNormalizationService->normalizeName($filterArray[PersonListFilterArray::streetName]);

            //Restore the Asterisk-symbols a the beginning and end of the streetName-querstring
            if ($firstCharsWasAsterisk) {
                $normalizedStreetName = "*" . $normalizedStreetName;
            }

            if ($lastCharsWasAsterisk) {
                $normalizedStreetName = $normalizedStreetName . "*";
            }

            $queryBuilder->andWhere('st.nameNormalized LIKE :normalizedStreetName')
                ->setParameter('normalizedStreetName', $this->replaceWildcards($normalizedStreetName));
        }

        if (key_exists(PersonListFilterArray::streetNr, $filterArray)) {
            $queryBuilder->AndWhere('ad.houseNr LIKE :streetNr')
                ->setParameter('streetNr', $this->replaceWildcards($filterArray[PersonListFilterArray::streetNr]));
        }

        if (key_exists(PersonListFilterArray::zipcode, $filterArray)) {
            $queryBuilder->andWhere('zc.zipcode LIKE :zipcode')
                ->setParameter('zipcode', $this->replaceWildcards($filterArray[PersonListFilterArray::zipcode]));
        }

        if (key_exists(PersonListFilterArray::city, $filterArray)) {
            $queryBuilder->andWhere('zc.city LIKE :city')
                ->setParameter('city', $this->replaceWildcards($filterArray[PersonListFilterArray::city]));
        }

        $queryBuilder
            ->orderBy("p.lastName", "ASC");

        if ($personListMode == PersonListMode::personAddressesOverview || $entitiesPerPage != null) {
            $offset = $entitiesPerPage * ($currentPage - 1);
            $queryBuilder->setFirstResult($offset)
                ->setMaxResults($entitiesPerPage);
        }
        
        $paginator = new Paginator($queryBuilder->getQuery(), $fetchJoinCollection = true);

        $result = [];
        $maxEntries = count($paginator);
        foreach ($paginator as $entry) {
            $result[] = $entry;
        }

        //Only one entry per piHelperId is needed, because the potential identities are fetched in a later step
        //Normally a groupBy("piHelperId") would do that but then the problem exists that the persons only have one personAddress.
        $piHelperIdsArray = [];
        $newResultArray = [];
        foreach ($result as $resultItem) {
            if (!in_array($resultItem["piHelperId"], $piHelperIdsArray)) {
                $newResultArray[] = $resultItem;
                $piHelperIdsArray[] = $resultItem["piHelperId"];
            }
        }

        $returnArray = [];
        $returnArray['resultsTotal'] = $maxEntries;
        $returnArray['results'] = $newResultArray;

        return $returnArray;

    }

    private function findFilteredCount($queryBuilder)
    {
        $countQueryBuilder = clone $queryBuilder;
        return $countQueryBuilder->select('COUNT(p.id)')->getQuery()->getSingleScalarResult();
    }

    public function findPersonsByStreetList($streetIdsArray, $emergencyId) {

        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->from('AppBundle:Person', 'p');

        if ($emergencyId) {
            $queryBuilder->select('pa, p, ad, zc, st, epss');
            $queryBuilder
                ->innerJoin('p.emergencySafetyStatuses', 'epss')
                ->where('epss.emergency = :emergencyId')
                ->setParameter('emergencyId', $emergencyId);
        } else {
            $queryBuilder->select('pa, p, ad, zc, st');
        }

        $queryBuilder
            ->leftJoin('p.personAddresses', 'pa')
            ->leftJoin('pa.address', 'ad')
            ->leftJoin('ad.street', 'st')
            ->leftJoin('st.zipcode', 'zc');

        $queryBuilder->andWhere("st.id IN (:streetIdsArray)")
            ->setParameter("streetIdsArray", $streetIdsArray);

        return $queryBuilder->getQuery()->getResult();
    }
}
