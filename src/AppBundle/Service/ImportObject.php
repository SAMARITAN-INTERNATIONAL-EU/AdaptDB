<?php
namespace AppBundle\Service;

/**
 * Enum for ImportObject-keys
 */
abstract class ImportObject
{
    const hasImportablePerson = "hasImportablePerson";
    const hasImportableContactPerson = "hasImportableContactPerson";
    const hasImportableAddress = "hasImportableAddress";
    const hasImportableGeoCoordinates = "hasImportableAddress";

    const row = "row";

    const firstName = "firstName";
    const lastName = "lastName";
    const fiscalCode = "fiscalCode";
    const dateOfBirth = "dateOfBirth";
    const landlinePhone = "landlinePhone";
    const cellPhone = "cellPhone";
    const gender = "gender";
    const email = "email";
    const remarks = "remarks";
    const transportRequirements = "transportRequirements";
    const transportRequirementIds = "transportRequirementIds";
    const medicalRequirements = "medicalRequirements";
    const medicalRequirementIds = "medicalRequirementIds";
    const vulnerabilityLevel = "vulnerabilityLevel";
    const vulnerabilityLevelId = "vulnerabilityLevel";
    const streetName = "streetMame";
    const streetNo = "streetNo";
    const zipcode = "zipcode";
    const city = "city";
    const countryId = "countryId";
    const countryName = "countryName";
    const adRemarks = "adRemarks";
    const floor = "floor";
    const latitude = "latitude";
    const longitude = "longitude";
    const cpFirstName = "cpFirstName";
    const cpLastName = "cpLastName";
    const cpPhone = "cpPhone";
    const cpRemarks = "cpRemarks";

    //String with all errors occured in this importObject
    const errorString = "errorString";
}