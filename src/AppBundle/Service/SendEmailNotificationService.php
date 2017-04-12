<?php

namespace AppBundle\Service;

use AppBundle\Entity\GeoPoint;
use AppBundle\Entity\PersonChangeHistory;
use AppBundle\Entity\Person;

/**
 * Class MailService
 * @package AppBundle\Service
 */
class SendEmailNotificationService
{

    const PROPNAME_PERSON_FIRSTNAME = 'firstName';
    const PROPNAME_PERSON_LASTNAME = 'lastName';
    const PROPNAME_PERSON_REMARKS = 'remarks';
    const PROPNAME_PERSON_VALID_UNTIL = 'validUntil';
    const PROPNAME_PERSON_VULNERABILITYLEVEL = 'vulnerabilityLevel';
    const PROPNAME_PERSON_DATE_OF_BIRTH = 'dateOfBirth';
    const PROPNAME_PERSON_CELLPHONE = 'cellPhone';
    const PROPNAME_PERSON_FISCALCODE = 'fiscalCode';
    const PROPNAME_PERSON_EMAIL = 'email';
    const PROPNAME_PERSON_SAFETY_STATUS = 'safetyStatus';

    private  $emailDataChangeInformationSubject;
    private  $emailDataChangeInformationFromEmail;
    private  $mailerService;

    public function __construct($email_data_change_information_subject, $email_data_change_information_from_email, $mailerService)
    {
        $this->emailDataChangeInformationSubject = $email_data_change_information_subject;
        $this->emailDataChangeInformationFromEmail = $email_data_change_information_from_email;
        $this->mailerService = $mailerService;
    }

    /**
     */
    public function sendEMailNotification($translatorService, Person $person, $changedPropertitesArray, $fromEmailAddress) {

        $message = "Your data have been updated recently. Please check if the data are correct. \r\n\r\n";

        $personPropertyArray = array();

        $personPropertyArray[$this::PROPNAME_PERSON_FIRSTNAME] = $person->getFirstName();
        $personPropertyArray[$this::PROPNAME_PERSON_LASTNAME] = $person->getLastName();
        $personPropertyArray[$this::PROPNAME_PERSON_FISCALCODE] = $person->getFiscalCode();
        $personPropertyArray[$this::PROPNAME_PERSON_DATE_OF_BIRTH] = $person->getDateOfBirth() ? $person->getDateOfBirth()->format('d M Y') : "";
        $personPropertyArray[$this::PROPNAME_PERSON_VALID_UNTIL] = $person->getValidUntil() ? $person->getValidUntil()->format('d M Y') : "";
        $personPropertyArray[$this::PROPNAME_PERSON_EMAIL] = $person->getEmail();
        $personPropertyArray[$this::PROPNAME_PERSON_CELLPHONE] = $person->getCellPhone();
        $personPropertyArray[$this::PROPNAME_PERSON_REMARKS] = $person->getRemarks();
        $personPropertyArray[$this::PROPNAME_PERSON_SAFETY_STATUS] = $person->getSafetyStatus() == 0 ? "Not Safe" : "Safe";

        foreach ($personPropertyArray as $propertyName => $propertyValue) {

            if (in_array($propertyName, $changedPropertitesArray)) {
                $message .=  $translatorService->trans($propertyName) . '(*): '. $propertyValue . PHP_EOL;
            } else {
                $message .=   $translatorService->trans($propertyName) . ': ' . $propertyValue . PHP_EOL;
            }
        }

        $contactPersonCount = 1;
        foreach ($person->getContactPersons() as $contactPerson) {
            $contactPersonPropertyArray = array();
            $contactPersonPropertyArray["firstName"] = $contactPerson->getFirstName();
            $contactPersonPropertyArray["lastName"] = $contactPerson->getLastName();
            $contactPersonPropertyArray["phone"] = $contactPerson->getPhone();
            $contactPersonPropertyArray["remarks"] = $contactPerson->getRemarks();


            $message .=  PHP_EOL . $translatorService->trans("contactPerson") . ' ('. $contactPersonCount .'):' . PHP_EOL;
            foreach ($contactPersonPropertyArray as $propertyName => $propertyValue) {
                $message .=  $translatorService->trans($propertyName) . ': '. $propertyValue . PHP_EOL;
            }

            $contactPersonCount++;
        }

        $personAddressCount = 1;
        foreach ($person->getPersonAddresses() as $personAddress) {
            $personAddressPropertyArray = array();
            $personAddressPropertyArray["street"] = $personAddress->getAddress()->getStreet()->getName();
            $personAddressPropertyArray["streetNr"] = $personAddress->getAddress()->getHouseNr();
            $personAddressPropertyArray["zipcode"] = $personAddress->getAddress()->getStreet()->getZipcode()->getZipcode();
            $personAddressPropertyArray["city"] = $personAddress->getAddress()->getStreet()->getZipcode()->getCity();
            $personAddressPropertyArray["country"] = $personAddress->getAddress()->getStreet()->getZipcode()->getCountry();


            $message .=  PHP_EOL . $translatorService->trans("address") . ' ('. $personAddressCount .'):' . PHP_EOL;
            foreach ($personAddressPropertyArray as $propertyName => $propertyValue) {
                $message .=  $translatorService->trans($propertyName) . ': '. $propertyValue . PHP_EOL;
            }

            $personAddressCount++;
        }

        $this->sendEmail($message, $fromEmailAddress, $person->getEmail());
    }

    private function sendEmail($message, $fromEmailAddress, $toEmailAddress)
    {
        $message = \Swift_Message::newInstance()
            ->setSubject($this->emailDataChangeInformationSubject)
            ->setFrom($fromEmailAddress)
            ->setTo($toEmailAddress)
            ->setBody(
                $message, 'text/plain'
            );
        $this->mailerService->send($message);

    }

}
