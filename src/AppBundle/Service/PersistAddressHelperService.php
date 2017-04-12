<?php
namespace AppBundle\Service;
use AppBundle\Entity\Address;
use AppBundle\Entity\PersonAddress;
use AppBundle\Entity\Street;
use AppBundle\Entity\Zipcode;

use CrEOF\Spatial\PHP\Types\Geometry\Point;
use Doctrine\ORM\EntityManager;

/**
 * Class NameNormalizationService
 * @package AppBundle\Service
 */
class PersistAddressHelperService
{

    public static function persistAddress(GeocoderService $geocoderService, EntityManager $em, $addressArray, $nominatimEmailAddress, $geocodingEnabled)
    {
        //Create an array with the relevant values
        //This avoids problem with entities updating in cases they shouldn't update
        //Example situation:
        //Person A has Person-Address: A-Street 1, 12345 A-City
        //Person B has Person-Address: A-Street 1, 12345 A-City
        ///if Person B is changed for example to "A-Street 1, 12345 A-City-Woods"
        //You don't want to have this update for Person A - The values for this person should stay untouched

        if (is_object($addressArray['street']['zipcode']['country'])) {
            $countryId = $addressArray['street']['zipcode']['country']->getId();
        } else {
            $countryId = $addressArray['street']['zipcode']['country'];
        }

        $country =$em->getRepository('AppBundle:Country')->find($countryId);

        if ($country) {

            $zipcodeFromDB = $em->getRepository('AppBundle:Zipcode')->findOneBy(array(
                'zipcode' => $addressArray['street']['zipcode']['zipcode'],
                'city' => $addressArray['street']['zipcode']['city'],
                'country' => $addressArray['street']['zipcode']['country'],
            ));

            if ($zipcodeFromDB == null) {
                $newZipcode = new Zipcode();
                $newZipcode->setCity($addressArray['street']['zipcode']['city']);
                $newZipcode->setZipcode($addressArray['street']['zipcode']['zipcode']);
                $newZipcode->setCountry($country);

                $em->persist($newZipcode);

                $zipcode = $newZipcode;
            } else {
                $zipcode = $zipcodeFromDB;
            }
            $em->persist($zipcode);

            $streetFromDB = $em->getRepository('AppBundle:Street')->findOneBy(array(
                'name' => $addressArray['street']['name'],
                'zipcode' => $zipcode
            ));

            if ($streetFromDB == null) {
                $newStreet = new Street();
                $newStreet->setZipcode($zipcode);
                $newStreet->setName($addressArray['street']['name']);

                $em->persist($newStreet);

                $street = $newStreet;

            } else {
                $street = $streetFromDB;
            }

            $em->persist($street);

            $addressFromDB = $em->getRepository('AppBundle:Address')->findOneBy(array(
                'street' => $street,
                'houseNr' => $addressArray['houseNr'],
            ));

            if ($addressFromDB == null) {
                $newAddress = new Address();
                $newAddress->setHouseNr($addressArray['houseNr']);
                $newAddress->setStreet($street);

                $em->persist($newAddress);

                $address = $newAddress;
            } else {
                $address = $addressFromDB;
            }

            $em->persist($address);

            if ($geocodingEnabled == true) {

                //Try to geocode the address
                $geoPointForAddress = $geocoderService->getGeoPointForAddress($address, $nominatimEmailAddress);

                if (!$geoPointForAddress) {

                    //Nominatims usage policy says:
                    //No heavy uses (an absolute maximum of 1 request per second).
                    //see https://wiki.openstreetmap.org/wiki/Nominatim_usage_policy
                    sleep(1);

                    //see #9
                    //Try to geocode without zipcode to avoid problems with cities in Sicily, Italy
                    $zipCodeBackup = $address->getStreet()->getZipcode()->getZipcode();
                    $address->getStreet()->getZipcode()->setZipcode("");
                    $geoPointForAddress = $geocoderService->getGeoPointForAddress($address, $nominatimEmailAddress);
                    $address->getStreet()->getZipcode()->setZipcode($zipCodeBackup);
                }

                $address->setGeoPoint($geoPointForAddress);

                //Persists geoPoint, if a point was returned from the Nominatim-geocoding-service
                if ($geoPointForAddress != null) {
                    $geoPointForAddress->setPoint(new Point($geoPointForAddress->getLat(), $geoPointForAddress->getLng()));
                    $em->persist($geoPointForAddress);
                }
            }

            return $address;
        } else {
            //Country was not found
            return null;
        }
    }
}

