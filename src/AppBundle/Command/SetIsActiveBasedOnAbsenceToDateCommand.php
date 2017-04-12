<?php
/**
 * Generates testData using faker
 *
 */
namespace AppBundle\Command;

use Hautelook\AliceBundle\Alice\DataFixtureLoader;
use Nelmio\Alice\Fixtures;

//require_once __DIR__.'/../../../vendor/fzaninotto/faker/src/autoload.php';

use Doctrine\Bundle\DoctrineBundle\Command\Proxy\DoctrineCommandHelper;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputDefinition;

/**
 * The SetIsActiveBasedOnValidUntilDateCommand removes the "Absence From" and "Absence To" values
 * and sets isActive to true
 *
 * Class SetIsActiveBasedOnAbsenceToDateCommand
 * @package AppBundle\Command
 */
class SetIsActiveBasedOnAbsenceToDateCommand extends ContainerAwareCommand
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
            ->setName('adaptDB:setIsActiveBasedAbsenceToUntilDate')
            ->setDescription('This command sets the isActive-state of a person ');
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

        $personAddressesWithAbsenceToExceeded = $em->getRepository('AppBundle:PersonAddress')->findWhereAbsenceToIsExceeded();

        foreach ($personAddressesWithAbsenceToExceeded as $personAddress) {
            $personAddress->setIsActive(true);
            $personAddress->setAbsenceFrom(null);
            $personAddress->setAbsenceTo(null);
            $em->persist($personAddress);
        }

        $output->writeln(count($personAddressesWithAbsenceToExceeded). ' PersonAddresses were set to active because the "Absence To"-date was reached.');

        $em->flush();
    }
}
