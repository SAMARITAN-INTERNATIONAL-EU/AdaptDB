<?php
/**
 * Generates testData using faker
 *
 */
namespace AppBundle\Command;

use AppBundle\Entity\Address;
use AppBundle\Entity\GeoPoint;
use AppBundle\Entity\User;
use AppBundle\Service\UserRole;
use Hautelook\AliceBundle\Alice\DataFixtureLoader;
use CrEOF\Spatial\PHP\Types\Geometry\Point;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Class FillDatabaseWithCommand
 * @package AppBundle\Command
 */
class FillDatabaseWithCommand extends ContainerAwareCommand
{
    /**
     * Configures this command.
     *
     * @return void
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('adaptDB:fillDatabaseWith')
            ->setDescription('Fills the database with fixtures, o')
            ->addArgument('fixturesOrTestdata', InputArgument::OPTIONAL, 'with fixtures (type fixtures) or with fixtures and testdata (type testdata)');
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

        $em = $this->getContainer()->get('doctrine')->getManager();

        // Load objects from a yaml file
        $loader = new \Nelmio\Alice\Fixtures\Loader();
        $persister = new \Nelmio\Alice\Persister\Doctrine($em);

        if ($input->getArgument('fixturesOrTestdata') == "fixtures" || $input->getArgument('fixturesOrTestdata') == 'testdata') {
            $objects_fixtures = $loader->load(__DIR__. '/../DataFixtures/fixtures.yml', new \Nelmio\Alice\Persister\Doctrine($em));
            $persister->persist($objects_fixtures);

            if ($input->getArgument('fixturesOrTestdata') == "fixtures") {
                $output->writeln('Fixtures were added into the database');
            }

            if ($input->getArgument('fixturesOrTestdata') == "testdata") {
                $objects_testdata = $loader->load(__DIR__ . '/../DataFixtures/testdata.yml', new \Nelmio\Alice\Persister\Doctrine($em));

                $persister->persist($objects_testdata);
                $output->writeln('Fixtures and testdata were added into the database');
            }

            //Set new random password for sysadmin to enforce individual credentials for every installation
            $username = "sysadmin";
            $userManager = $this->getContainer()->get('fos_user.user_manager');
            $apiHelperService = $this->getContainer()->get('app.api_helper_service');

            //Generates a random password with an variable length to make the password harder to guess
            $newRandomPassword = $apiHelperService->generatePassword(rand(14,18));

            $userSystemAdmin = $em->getRepository('AppBundle:User')->findOneBy(array('username' => $username));

            if ($userSystemAdmin != null) {
                $userSystemAdmin->addRole(UserRole::SYSTEM_ADMIN);
            }

            $userSystemAdmin->setPlainPassword($newRandomPassword);

            //Add one data-admin user with an random password
            //This user can be used to create other users with user-defined passwords
            $output->writeln("A system-admin account was created. Log in to the system using this account to create other users.");
            $output->writeln("Username: " . $username);
            $output->writeln("Password: " . $newRandomPassword);

            //To persist the changed password
            $userManager->updateUser($userSystemAdmin);

            $em->persist($userSystemAdmin);
            
            $em->flush();

            //Normalize the street-names and firstName, lastName of persons
            /** NameNormalizationService $nameNormalizationService */
            $nameNormalizationService = $this->getContainer()->get('app.name_normalization_service');

            $persons = $em->getRepository('AppBundle:Person')->findAll();

            foreach ($persons as $person) {
                $person->setFirstNameNormalized($nameNormalizationService->normalizeName($person->getFirstName()));
                $person->setLastNameNormalized($nameNormalizationService->normalizeName($person->getLastName()));
                $em->persist($person);
            }

            $streets = $em->getRepository('AppBundle:Street')->findAll();

            foreach ($streets as $street) {
                $street->setNameNormalized($nameNormalizationService->normalizeStreetName($street->getName()));
                $em->persist($street);
            }

            $addresses = $em->getRepository('AppBundle:Address')->findAll();


            /** @var Faker faker */
            $this->faker = \Faker\Factory::create();

            /** @var Address $address */
            foreach ($addresses as $address) {
                if (rand(0,100) < 50) {
                    $newGeoPoint = new GeoPoint();
                    $lat = $this->faker->latitude;
                    $lng = $this->faker->longitude;
                    $newGeoPoint->setLat($lat);
                    $newGeoPoint->setLng($lng);
                    $newGeoPoint->setPoint(new Point($lat,$lng));
                    $address->setGeopoint($newGeoPoint);

                    $em->persist($address);
                    $em->persist($newGeoPoint);
                }
            }

            $em->flush();
        } else {
            $output->writeln('You need to specify what should be loaded: fixtures (type "adaptDB:fillDatabaseWith fixtures") or with fixtures and testdata (type "adaptDB:fillDatabaseWith testdata"');
        }
    }
}
