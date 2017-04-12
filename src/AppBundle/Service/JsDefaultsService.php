<?php
namespace AppBundle\Service;

/**
 * Class JsDefaultsService
 * @package AppBundle\Service
 */
class JsDefaultsService
{

    private $defaultMapPositionLatLng;
    private $defaultMapZoom;

    /**
     * Constructor
     */
    public function __construct($defaultMapPositionLatLng, $defaultMapZoom, $mapVariant)
    {
        $this->defaultMapPositionLatLng = $defaultMapPositionLatLng;
        $this->defaultMapZoom = $defaultMapZoom;
        $this->mapVariant = $mapVariant;
    }

    public function adaptMapDefaults()
    {
        //Returns the Javascript-string with the variable definitions
        return "var defaultMapCoordinatesArray  = " . $this->defaultMapPositionLatLng . ";" .
        "var defaultMapZoomLevel = " . $this->defaultMapZoom . ";" .
        "var mapVariant = '" . $this->mapVariant . "';";
    }
}
