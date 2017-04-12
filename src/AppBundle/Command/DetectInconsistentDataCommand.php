<?php
/**
 * This command sends emails to the users where data changes have been made
 *
 */
namespace AppBundle\Command;

use AppBundle\Entity\InconsistentPI;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Entity\DataChangeHistory;

/**
 * Class DetectInconsistentDataCommand
 * @package AppBundle\Command
 */
class DetectInconsistentDataCommand extends ContainerAwareCommand
{

    /**
     * Configures this command.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('adaptDB:detectInconsistentData')

            // the short description shown while running "php bin/console list"
            ->setDescription('This function checks potentialIdentities for inconsistent data.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp("This function checks potentialIdentities for inconsistent data.");
    }

    /**
     * Executes this command.
     *
     * @param InputInterface  $input  The input object
     * @param OutputInterface $output The output object
     * @return integer Exit code
     * @throws \RuntimeException When in test environment
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $counterInconsistentPI = 0;

        $descriptionString = "";

        $em = $this->getContainer()->get('doctrine')->getManager();
        $potentialIdentities = $em->getRepository('AppBundle:PotentialIdentity')->findAll();

        foreach ($potentialIdentities as $potentialIdentity) {

            $comparePersonArray = array();
            $comparePersonArray['firstName'] = array();
            $comparePersonArray['lastName'] = array();
            $comparePersonArray['fiscalCode'] = array();
            $comparePersonArray['validUntil'] = array();
            $comparePersonArray['dateOfBirth'] = array();
            $comparePersonArray['email'] = array();
            $comparePersonArray['cellPhone'] = array();
            $comparePersonArray['gender'] = array();
            $comparePersonArray['vulnerabilityLevelId'] = array();

            $personsOfPotentialIdentity = $potentialIdentity->getPersons();

            if (count($personsOfPotentialIdentity) >= 2) {
                $potentialIdentityIsInconsistent = false;

                //These are the "main" propertites of a potential identity
                //If persons of this potential identity have other values, the PI is inconsistent
                //Example1:
                //Person1.FirstName = "Karl"
                //Person2.FirstName = "Carl"
                //--->Inconsistent
                //
                //Example2:
                //Person1.DateOfBirth = 1950-01-01
                //Person2.DateOfBirth = [not set]
                //--->Not Inconsistent

                //Comparing the base properties of a person
                foreach ($personsOfPotentialIdentity as $person) {
                    $comparePersonArray["firstName"][] = $person->getFirstName();
                    $comparePersonArray["lastName"][] = $person->getLastName();
                    $comparePersonArray["fiscalCode"][] = $person->getFiscalCode();
                    $comparePersonArray["validUntil"][] = $person->getValidUntil() ? $person->getValidUntil()->format('Y M d'): "";
                    $comparePersonArray["dateOfBirth"][] = $person->getDateOfBirth() ? $person->getDateOfBirth()->format('Y M d'): "";
                    $comparePersonArray["email"][] = $person->getEmail();
                    $comparePersonArray["cellPhone"][] = $person->getCellphone();
                    $comparePersonArray["gender"][] = $person->getGenderMale();
                    $comparePersonArray["vulnerabilityLevelId"][] = $person->getVulnerabilityLevel()->getId();

                    $medicalRequirementIds = array();
                    foreach ($person->getMedicalRequirements() as $medicalRequirement) {
                        $medicalRequirementIds[] = $medicalRequirement->getId();
                    }
                    $comparePersonArray["medicalRequirements"][] = implode(",", $medicalRequirementIds);

                    $transportRequirementIds = array();
                    foreach ($person->getTransportRequirements() as $transportRequirement) {
                        $transportRequirementIds[] = $transportRequirement->getId();
                    }
                    $comparePersonArray["transportRequirements"][] = implode(",", $transportRequirementIds);
                }

                foreach ($comparePersonArray as $valuesArray) {

                    if ($this->isValueArrayInconsistent($valuesArray) == true) {
                        $potentialIdentityIsInconsistent = true;
                        $descriptionString = "Person-related data of the Potential Identity is inconsistent.";
                        continue;
                    }
                }

                //If the person is consistent - the personAddresses still needs to be tested for consistence
                if ($potentialIdentityIsInconsistent == false) {
                    //Start preparations to compare personAddresses

                    //Get an array with the maximal amount of personAddresses
                    $arrayWithMaxAmountOfPersonAddresses = array();
                    foreach ($personsOfPotentialIdentity as $person) {
                        if (count($arrayWithMaxAmountOfPersonAddresses) < count($person->getPersonAddresses())) {
                            $arrayWithMaxAmountOfPersonAddresses = $person->getPersonAddresses();
                        }
                    }

                    //Only compare the addresses when there is at least one address
                    if (count($arrayWithMaxAmountOfPersonAddresses) >= 1) {

                        //Array that contains strings of dumped PersonAddresses
                        $personAddressesStringArray = array();

                        foreach ($arrayWithMaxAmountOfPersonAddresses as $personAddressInLoop) {
                            $personAddressesStringArray[] = $personAddressInLoop->getAddressDumpForComparison();
                        }

                        //Finished preparations to compare personAddresses

                        //Start comparing personAddresses
                        //Compare the peronAddresses of every person with $arrayWithMaxAmountOfPersonAddresses
                        foreach ($personsOfPotentialIdentity as $person) {

                            $personAddressesOfPerson = is_array($person->getPersonAddresses()) ? $person->getPersonAddresses() : array();

                            //generate $personAddressesOfPersonStringArray
                            $personAddressesOfPersonStringArray = [];
                            foreach ($person->getPersonAddresses() as $personAddressInLoop) {
                                $personAddressesOfPersonStringArray[] = $personAddressInLoop->getAddressDumpForComparison();
                            }

                            $combinedPersonAddressesStringArray = array_merge($personAddressesStringArray, $personAddressesOfPersonStringArray);

                            //If count($combinedPersonAddressesStringArray) is the same as targetCount the personAddress is consistent
                            $targetCount = count($personAddressesStringArray) - count($personAddressesOfPerson);

                            if ($targetCount != count($combinedPersonAddressesStringArray)) {
                                $potentialIdentityIsInconsistent = true;
                                $descriptionString = "Address-related data of the Potential Identity is inconsistent.";
                                continue;
                            }
                        }

                        //Finished comparing personAddresses
                    }
                }

            } else {
                //If potentialIdentity has one (or none) persons, it can't be inconsistent
                $potentialIdentityIsInconsistent = false;
            }

            if ($potentialIdentityIsInconsistent == true) {

                //Remove all InconsistentPI entities currently in the database
                $potentialIdentities = $em->getRepository('AppBundle:InconsistentPI')->findAll();

                foreach ($potentialIdentities as $potentialIdentityInLoop) {
                    $em->remove($potentialIdentityInLoop);
                }

                $newInconsistentPI = new InconsistentPI();
                $counterInconsistentPI++;
                $newInconsistentPI->setCreated(new \DateTime());
                $newInconsistentPI->setPotentialIdentity($potentialIdentity);
                $newInconsistentPI->setDescription($descriptionString);
                $newInconsistentPI->setHidden(0);
                $em->persist($newInconsistentPI);
            }

        }
        $em->flush();

        $output->writeln($counterInconsistentPI . " inconsistent PI's were detected.");
        if ($counterInconsistentPI >= 1) {
            $output->writeln("Please check the 'Inconsistent Data'-page in the 'Administration'-menu to see them.");
        }
    }

    private function isValueArrayInconsistent($valuesArray) {

        //Removes duplicate values from the array
        $differentValuesArray = array_unique($valuesArray);

        switch (count($differentValuesArray)) {
            case 1:
                return false;
                break;
            case 2:
                //Test if empty string is in the array
                //If it is not two different values are in the array and PI is inconsistent
                $arrayKeys = array_keys($differentValuesArray);
                if ($differentValuesArray[$arrayKeys[0]] != "" && $differentValuesArray[$arrayKeys[1]] != "") {
                    //Both values are not empty ->data are inconsistent
                    return true;
                } else {
                    //Array has empty string and a value -> data are consistent
                    return false;
                }
                break;
            default:
                return true;
                break;
        }
    }
}
