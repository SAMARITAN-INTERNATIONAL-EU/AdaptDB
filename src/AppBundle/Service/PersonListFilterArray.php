<?php
namespace AppBundle\Service;

/**
 * Enum for keys in filterArray
 */
abstract class PersonListFilterArray
{
    const isActive = "isActive";
    const safetyStatus = "safetyStatus";
    const fiscalCode = "fiscalCode";
    const firstName = "firstName";
    const lastName = "lastName";
    const remarks = "remarks";
    const dateOfBirth = "dateOfBirth";
    const age = "age";
    const ageGrSm = "ageGrSm";
    const floor = "floor";
    const floorGrSm = "floorGrSm";
    const streetName = "streetName";
    const streetNr = "streetNr";
    const city = "city";
    const zipcode = "zipcode";
    const cpRemarks = "cpRemarks";
    const selectedStreetIds = "selectedStreetIds";
    const geoAreas = "geoAreas";
    const showAllEntities = "showAllEntities";
}
