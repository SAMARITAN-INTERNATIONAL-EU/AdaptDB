<?php
namespace AppBundle\Service;

use AppBundle\Entity\GeoPoint;

/**
 * Class FilterHelperService
 * @package AppBundle\Service
 */
class GeocoderService
{

    /**
     * This used the
     * http://wiki.openstreetmap.org/wiki/Nominatim_usage_policy
     */
    public static function getGeoPointForAddress($address, $nominatimEmailAddress) {

        if (empty($nominatimEmailAddress)) {
            return null;
        } else {

            //$baseUrl = 'http://nominatim.openstreetmap.org/search?format=json&limit=1';

            //This was suggested by a user in a forum thread, when having an errormessage 403 forbidden
            //$baseUrl = 'http://nominatim.openstreetmap.org/search?&email=name@provider.com&format=json&limit=1';

            //TODO extract the email-parameter to the application parameters

            //Added Email to avoid error "Forbidden 403"
            $baseUrl = 'http://nominatim.openstreetmap.org/search?&email=' . $nominatimEmailAddress . '&format=json&limit=1';

            //Add parameters to one array if properties are set

            $nominatimQueryParameters = array();

            if ($address->getStreet()) {

                array_push($nominatimQueryParameters, "&street=" . urlencode($address->getStreet()->getName() . ' ' . $address->getHouseNr()));

                if ($address->getStreet()->getZipcode()) {
                    if ($address->getStreet()->getZipcode()->getZipcode() != "") {
                        array_push($nominatimQueryParameters, "&postalcode=" . urlencode($address->getStreet()->getZipcode()->getZipcode()));
                    }
                    if ($address->getStreet()->getZipcode()->getCity() != "") {
                        array_push($nominatimQueryParameters, "&city=" . urlencode($address->getStreet()->getZipcode()->getCity()));
                    }

                    if ($address->getStreet()->getZipcode()->getCountry()) {
                        array_push($nominatimQueryParameters, "&country=" . urlencode($address->getStreet()->getZipcode()->getCountry()->getName()));
                    }
                }
            }

            $nominatimQuery = implode('', $nominatimQueryParameters);
            $completeQuery = "{$baseUrl}{$nominatimQuery}";
            $data = file_get_contents($completeQuery);
            $nominatimResult = json_decode( $data );

            if (count($nominatimResult) != 0) {
                $nominatimResult = (array)$nominatimResult[0];

                $newGeoPointForAddress = new GeoPoint();
                $newGeoPointForAddress->setLat($nominatimResult['lat']);
                $newGeoPointForAddress->setLng($nominatimResult['lon']);

                return $newGeoPointForAddress;
            } else {
                //The Nominatim-Service did not return a result.
                return null;
            }
        }
    }
}
