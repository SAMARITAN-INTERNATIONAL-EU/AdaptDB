<?php
/**
 * This command removes unused entities from the database
 *
 */
namespace AppBundle\Command;

use AppBundle\Entity\InconsistentPI;
use Doctrine\ORM\EntityManager;
use Faker\Provider\cs_CZ\DateTime;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Entity\DataChangeHistory;
use DirectoryIterator;

/**
 * Class CleanUpDatabaseCommand
 * @package AppBundle\Command
 */
class CleanUpDatabaseCommand extends ContainerAwareCommand
{
    
    /** @var  EntityManager $em */
    private $em;

    /**
     * Configures this command.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('adaptDB:cleanUpDatabase')

            // the short description shown while running "php bin/console list"
            ->setDescription('This function removes all addresses, countries, streets, zipcodes, countries , geoPoints and geoAreas without refereneces to it.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp("This function removes all addresses, countries, streets, zipcodes, countries , geoPoints and geoAreas without refereneces to it.");
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

        $this->em = $this->getContainer()->get('doctrine')->getManager();

        $output->writeln("Cleaning up the database...");
        $this->cleanUpPersonAddresses($output);
        $this->cleanUpAddresses($output);
        $this->cleanUpstreets($output);
        $this->cleanUpZipcodes($output);
        $this->cleanUpAuthTokens($output);
        $this->cleanUpGeoPoints($output);
        $this->cleanUpGeoAreas($output);
        $this->cleanUpCSVTempFolder($output);

    }

    /**
     * Deletes AuthTokens that have exceeded 7 days ago
     * AuthTokens could be deleted directly when they have exceeded their lifetime, but like this
     * the client can receive the information that the token is not valid anymore instead of "authenticion failed"
     */
    private function cleanUpAuthTokens(OutputInterface $output) {

        $dateInterval = new \DateInterval('P7D');
        $dateInterval->invert = 1;

        $authTokensToDelete = $this->em->getRepository('AppBundle:AuthToken')->createQueryBuilder('at')
            ->select("at")
            ->where('at.exceeds <= :deleteAuthTokenThreshold')
            ->setParameter("deleteAuthTokenThreshold", (new \DateTime())->add($dateInterval))
            ->getQuery()
            ->getResult();

        foreach ($authTokensToDelete as $authToken) {
            $this->em->remove($authToken);
        }

        $output->writeln(count($authTokensToDelete) . ' authTokens deleted');

        $this->em->flush();
    }

    private function cleanUpPersonAddresses(OutputInterface $output) {

        //Finds all addresses that are not used in the PersonAddress-table
        $addressesToDelete = $this->em->getRepository('AppBundle:PersonAddress')->createQueryBuilder('pa')
            ->select("pa")
            ->where('pa.person is null')
            ->getQuery()
            ->getResult();


        foreach ($addressesToDelete as $address) {
            $this->em->remove($address);
        }
        $output->writeln(count($addressesToDelete) . ' personAdresses deleted');

        $this->em->flush();

    }

    private function cleanUpAddresses(OutputInterface $output) {

        //Finds all addresses that are not used in the PersonAddress-table
        $addressesToDelete = $this->em->getRepository('AppBundle:Address')->createQueryBuilder('ad')
            ->select("ad")
            ->leftJoin('AppBundle:PersonAddress', 'pa', 'WITH', 'ad.id = pa.address')
            ->where('pa.address is null')
            ->getQuery()
            ->getResult();


        foreach ($addressesToDelete as $address) {
            $this->em->remove($address);
        }
        $output->writeln(count($addressesToDelete) . ' addresses deleted');

        $this->em->flush();

    }

    private function cleanUpStreets(OutputInterface $output)
    {
        //Finds all streets that are not used in the Address-table or the emergency-street-list
        $streetsToDelete = $this->em->getRepository('AppBundle:Street')->createQueryBuilder('st')
            ->select("st")
            ->leftJoin('AppBundle:Address', 'ad', 'WITH', 'st.id = ad.street')
            ->where('ad.street is null')
            ->getQuery()
            ->getResult();


        foreach ($streetsToDelete as $street) {
            $this->em->remove($street);
        }


        $output->writeln(count($streetsToDelete) . ' streets deleted');

        $this->em->flush();
    }

    private function cleanUpZipcodes(OutputInterface $output) {

        //Finds all zipcodes that are not used in the Street-table
        $zipcodesToDelete = $this->em->getRepository('AppBundle:Zipcode')->createQueryBuilder('zc')
            ->select("zc")
            ->leftJoin('AppBundle:Street', 'st', 'WITH', 'zc.id = st.zipcode')
            ->where('st.zipcode is null')
            ->getQuery()
            ->getResult();


        foreach ($zipcodesToDelete as $zipcode) {
            $this->em->remove($zipcode);
        }
        $output->writeln(count($zipcodesToDelete) . ' zipcodes deleted');

        $this->em->flush();
    }

    private function cleanUpGeoPoints(OutputInterface $output) {

        //Finds all geoPoints that have no geoAreaId and are not referenced by any address
        $countriesToDelete = $this->em->getRepository('AppBundle:GeoPoint')->createQueryBuilder('gp')
            ->select("gp")
            ->leftJoin('AppBundle:Address', 'ad', 'WITH', 'gp.id = ad.geopoint')
            ->where('ad.geopoint is null')
            ->andWhere('gp.geoArea is null')
            ->getQuery()
            ->getResult();

        foreach ($countriesToDelete as $country) {
            $this->em->remove($country);
        }
        $output->writeln(count($countriesToDelete) . ' geo-points deleted');

        $this->em->flush();
    }

    private function cleanUpGeoAreas(OutputInterface $output) {

        //Finds all GeoAreas without an emergency set
        $geoAreasToDelete = $this->em->getRepository('AppBundle:GeoArea')->createQueryBuilder('ga')
            ->select("ga")
            ->where('ga.emergency is null')
            ->getQuery()
            ->getResult();

        foreach ($geoAreasToDelete as $geoArea) {
            $this->em->remove($geoArea);
        }
        $output->writeln(count($geoAreasToDelete) . ' geo-areas deleted');

        $this->em->flush();
    }

    /**
     * Removes files older than one hour from csv_file_temp_folder
     *
     * @param OutputInterface $output
     *
     */
    private function cleanUpCSVTempFolder(OutputInterface $output) {

        $tmpFolder = $this->getContainer()->getParameter("csv_file_temp_folder");
        $tmpCsvFilesPath = realpath(dirname(__FILE__)."/../../". $tmpFolder);
        $cdir = scandir($tmpCsvFilesPath);
        $filesDeletedCounter = 0;

        foreach ($cdir as $key => $value) {
            if (!in_array($value, array(".", ".."))) {
                $oneHourBeforeTimestamp = time() - (60*60);
                $absoluteFilename = $tmpCsvFilesPath."/".$value;
                $modifiedTimestamp = filemtime($absoluteFilename);
                if ($modifiedTimestamp < $oneHourBeforeTimestamp) {
                    unlink($absoluteFilename);
                    $filesDeletedCounter++;
                }
            }
        }

        $output->writeln($filesDeletedCounter . " files deleted from csv_file_temp_folder folder");
    }
}
