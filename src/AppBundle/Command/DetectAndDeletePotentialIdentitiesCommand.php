<?php
/**
 * This command sends emails to the users where data changes have been made
 *
 */
namespace AppBundle\Command;

use AppBundle\Entity\InconsistentPI;
use AppBundle\Entity\Person;
use AppBundle\Entity\PotentialIdentity;
use AppBundle\Entity\PotentialIdentityCluster;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Entity\DataChangeHistory;

/**
 * Class DetectAndDeletePotentialIdentitiesCommand
 * @package AppBundle\Command
 */
class DetectAndDeletePotentialIdentitiesCommand extends ContainerAwareCommand
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
            ->setName('adaptDB:detectAndDeletePotentialIdentities')
            // the short description shown while running "php bin/console list"
            ->setDescription('This function checks creates and deletes Potential Identities.')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This function checks creates and deletes Potential Identities.');
    }

    /**
     * Executes this command.
     *
     * @param InputInterface $input The input object
     * @param OutputInterface $output The output object
     * @return integer Exit code
     * @throws \RuntimeException When in test environment
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $piDetectionArray = $this->getPiDetectionArray($output);

        $em = $this->getContainer()->get('doctrine')->getManager();

        $this->removeAllPotentialIdentities($em);

        //Gets an array of simplified persons objects to improve performance
        $simplePersonsArray = $this->getSimplePersonsArray($em);

        $personIdentificatorsArrays = array();

        foreach ($piDetectionArray as $piDetectionPartArray) {

            $tmpArray = array();

            foreach ($simplePersonsArray as $simplePerson) {

                $identificator = $this->getIdentificator($simplePerson, $piDetectionPartArray);
                if (!isset($tmpArray[$identificator])) {
                    $tmpArray[$identificator] = array();
                }

                //Two persons with the same $identificator will be grouped here
                //They are Potential Identities
                $tmpArray[$identificator][] = $simplePerson["id"];
            }

            $personIdentificatorsArrays[] = $tmpArray;
        }

        //Remove all arrays that only contain one personId
        //For those no PICluster needs to be created
        foreach ($personIdentificatorsArrays as &$personIdentificatorsArray) {

            foreach ($personIdentificatorsArray as $identificator => $personIds) {

                if (count($personIds) <= 1) {
                    unset($personIdentificatorsArray[$identificator]);
                }
            }
        }

        //DO NOT GENERATE PiClusters here, only generate PI's
        //Combine both arrays inside $personIdentificatorsArray
        $arrayWithPotentialIdentitiesToGenerate = array();

        //This is needed because the variable-name was already used beore
        //Otherwise there is a php's behaviour is unexpected and the code below does not work
        unset($personIdentificatorsArray);

        foreach ($personIdentificatorsArrays as $personIdentificatorsArray) {

            foreach ($personIdentificatorsArray as $tmpArray) {
                //Generate an unique array key for the PI - This prevents that the same combination is added twice
                $arrayKey = implode(",", $tmpArray);
                $arrayWithPotentialIdentitiesToGenerate[$arrayKey] = $tmpArray;
            }
        }

        //Add manually created PIs to $arrayWithPotentialIdentitiesToGenerate
        $manuallyAddedPIClusters = $em->getRepository('AppBundle:PotentialIdentityCluster')->findBy(array("wasCreated" => 1));

        /** @var PotentialIdentityCluster $manuallyAddedPICluster */
        foreach ($manuallyAddedPIClusters as $manuallyAddedPICluster) {
            //get PersonIds from the Cluster
            $personIdsArrayOfManuallyAddedPiCluster = array();
            foreach ($manuallyAddedPICluster->getPersons() as $person) {
                $personIdsArrayOfManuallyAddedPiCluster[] = $person->getId();
            }
            sort($personIdsArrayOfManuallyAddedPiCluster);

            $keyFromManuallyAddedPICluster = implode(",", $personIdsArrayOfManuallyAddedPiCluster);

            $arrayWithPotentialIdentitiesToGenerate[$keyFromManuallyAddedPICluster] = $personIdsArrayOfManuallyAddedPiCluster;

        }

        //Remove PIs that were manually removed from $arrayWithPotentialIdentitiesToGenerate
        $manuallyRemovedPIClusters = $em->getRepository('AppBundle:PotentialIdentityCluster')->findBy(array("wasCreated" => 0));

        //Get a list of PersonIDs from the cluster
        //If the combined string matched a key in $arrayWithPotentialIdentitiesToGenerate it should be removed
        //This combination was manually removed in the past and should not be generated again.

        /** @var PotentialIdentityCluster $manuallyRemovedPIClusters */
        foreach ($manuallyRemovedPIClusters as $manuallyRemovedPICluster) {

            $tmpArray = array();
            foreach ($manuallyRemovedPICluster->getPersons() as $person) {
                $personIdsArrayOfManuallyRemovedPiCluster[] = $person->getId();
            }
            $keyFromManuallyRemovedPICluster = implode(",", $personIdsArrayOfManuallyRemovedPiCluster);

            if (isset($arrayWithPotentialIdentitiesToGenerate[$keyFromManuallyRemovedPICluster])) {
                unset($arrayWithPotentialIdentitiesToGenerate[$keyFromManuallyRemovedPICluster]);
            }
        }

        $createdPotentialIdentitiesNamesArray = array();

        //generate Potential Identities
        foreach ($arrayWithPotentialIdentitiesToGenerate as $arrayPersonIdsForPI) {

            $newPotentialIdentity = new PotentialIdentity();

            $piName = "";
            foreach ($arrayPersonIdsForPI as $personId) {

                /** @var Person $tmpPerson */
                $tmpPerson = $em->getRepository('AppBundle:Person')->find($personId);
                $tmpPerson->setPotentialIdentity($newPotentialIdentity);
                $em->persist($tmpPerson);

                $piName = $tmpPerson->getFirstName() . ' ' . $tmpPerson->getLastName();
            }

            $createdPotentialIdentitiesNamesArray[] = $piName;
            $newPotentialIdentity->setName($piName);

            $em->persist($newPotentialIdentity);
        }

        $em->flush();

        $countGeneratedPotentialIdentities = count($arrayWithPotentialIdentitiesToGenerate);

        if ($countGeneratedPotentialIdentities >= 1) {
            $output->writeln($countGeneratedPotentialIdentities . " Potential Identities have been created:");
            $output->writeln(implode(", ", $createdPotentialIdentitiesNamesArray));
        } else {
            $output->writeln($countGeneratedPotentialIdentities . " Potential Identities have been created.");
        }
    }

    private function removeAllPotentialIdentities(EntityManager $em) {

        $em->createQuery('UPDATE AppBundle:Person p SET p.potentialIdentity = NULL')->execute();
        $em->createQuery('UPDATE AppBundle:PersonMissingInDataSource pmids SET pmids.potentialIdentity = NULL')->execute();
        $em->createQuery('DELETE FROM AppBundle:InconsistentPI')->execute();

        $potentialIdentities = $em->getRepository("AppBundle:PotentialIdentity")->findAll();

        foreach ($potentialIdentities as $potentialIdentity) {
            $em->remove($potentialIdentity);
        }

        $em->flush();
        
    }

    /**
     * Gets an string for identification of an person entity
     * If $columnNamesArray consists of one element this key is the identitifcator
     * For more than one key the values are concatenated and an md5-hash is generated and returned
     *
     * @param $personSimple
     * @param $columnNamesArray
     * @return string
     */
    private function getIdentificator($personSimple, $columnNamesArray) {

        if (count($columnNamesArray) == 1) {
            return $personSimple[$columnNamesArray[0]];
        } else {
            $idPartString = "";
            foreach ($columnNamesArray as $columnName) {
                $idPartString .= $personSimple[$columnName];
            }
            return md5($idPartString);
        }
    }

    /**
     * Gets $piDetectionArray and checks validity
     *
     * @param OutputInterface $output
     * @return mixed
     */
    private function getPiDetectionArray(OutputInterface $output) {

        $parameterName = 'potential_identity_detection';
        $validColumns = ["fiscalCode", "firstName", "lastName", "dateOfBirth", "gender"];

        $piDetectionArray = json_decode($this->getContainer()->getParameter($parameterName));

        //Check if $piDetectionArray is set
        if ($piDetectionArray == null) {
            $output->writeln('Value for parameter "potential_identity_detection" is invalid. Please verify that it is valid JSON.' . PHP_EOL);
            $output->writeln("example:");
            $output->writeln('[["fiscalCode"],["firstName","lastName","dateOfBirth"]]');
            $output->writeln('possible column-names: ' . implode(", ", $validColumns));
            die();
        }

        //Check if all column-names in $piDetectionArray are legal
        foreach ($piDetectionArray as $piDetectionPartArray) {
            $diffArray = array_diff($piDetectionPartArray, $validColumns);
            if (count($diffArray) >= 1 ) {
                $output->writeln('Invalid column(s) in value for parameter "' . $parameterName . '": ' . implode(",", $diffArray));
                $output->writeln('possible column-names: ' . implode(", ", $validColumns));
                die();
            }
        }

        return $piDetectionArray;
    }

    /**
     * Gets an plain php array with only the person-property that are important when comparing persons
     *
     * @param $em
     * @return array
     */
    private function getSimplePersonsArray($em) {
        $personArray = $em->getRepository('AppBundle:Person')->findAll();

        $simplePersonsArray = array();
        /** @var Person $person */
        foreach ($personArray as $person) {
            $simplePersonsArray[] = [
                "id" => $person->getId(),
                "fiscalCode" => $person->getFiscalCode(),
                "firstName" => $person->getFirstNameNormalized(),
                "lastName" => $person->getLastNameNormalized(),
                "dateOfBirth" => ($person->getDateOfBirth()!=null) ? $person->getDateOfBirth()->format("dmy") : "",
                "genderMale" => $person->getGenderMale()
            ];

        }
        return $simplePersonsArray;
    }
}
