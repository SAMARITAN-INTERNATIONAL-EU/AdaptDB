<?php
namespace AppBundle\Service;

/**
 * Enum for ImportObject-keys
 */
abstract class CombinedPerson
{
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
    const medicalRequirements = "medicalRequirements";
    const vulnerabilityLevel = "vulnerabilityLevel";
    const validUntil = "validUntil";
    const contactPersons = "contactPersons";
    const personAddresses = "personAddresses";
}