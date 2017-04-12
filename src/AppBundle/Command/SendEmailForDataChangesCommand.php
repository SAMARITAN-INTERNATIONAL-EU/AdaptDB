<?php
/**
 * This command sends emails to the users where data changes have been made
 *
 */
namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Entity\DataChangeHistory;

/**
 * Class SendEmailForDataChangesCommand
 * @package AppBundle\Command
 */
class SendEmailForDataChangesCommand extends ContainerAwareCommand
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
            ->setName('adaptDB:sendEmailsForDataChanges')

            // the short description shown while running "php bin/console list"
            ->setDescription('Sends emails for recent data changes to the affected users.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp("This command sends emails for recent data changes to the affected users.");
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

        $notModifiedMinutesThreshold = $this->getContainer()->getParameter('send_emails_for_data_changes_not_modified_minutes_threshold');
        $dataChangesToSendEmailsFor = $em->getRepository('AppBundle:DataChangeHistory')->findDataChangesToSendEmailsFor($notModifiedMinutesThreshold);

        //Associative array with personID and the changed properties of every person
        $personsToNotifyArray = array();

        //Associative array with person IDs and person entities
        $personsArray = array();

        foreach ($dataChangesToSendEmailsFor as $dataChangeHistoryItem) {
            if ($dataChangeHistoryItem->getPerson() != null && !empty($dataChangeHistoryItem->getPerson()->getEmail())) {
                $personsArray[$dataChangeHistoryItem->getPerson()->getId()] = $dataChangeHistoryItem->getPerson();
                $personsToNotifyArray[$dataChangeHistoryItem->getPerson()->getId()][] = $dataChangeHistoryItem->getProperty();
            }
        }

        $fromEmailAddress =  $this->getContainer()->getParameter('notification_email_sender_address');

        /** SendEmailNotificationService $sendEmailNotificationService */
        $sendEmailNotificationService = $this->getContainer()->get('app.send_email_notification_service');

        $translatorService = $this->getContainer()->get('translator');

        foreach ($personsToNotifyArray as $personId => $changedPropertiy) {
            $sendEmailNotificationService->sendEMailNotification($translatorService, $personsArray[$personId], $changedPropertiy, $fromEmailAddress);
        }

        //Change the SendEmailCronjobDone property for the DataChangeHistoryItems
        foreach ($dataChangesToSendEmailsFor as $dataChangeHistoryItem) {
            /** DataChangeHistory $dataChangeHistoryItem */
            $dataChangeHistoryItem->setSendEmailCronjobDone(true);
            $em->persist($dataChangeHistoryItem);
            $em->flush();
        }

        $countEmailsSend = count($personsArray);
        $output->writeln("For " . $countEmailsSend . " E-Mail(s) were send.");
        $countPersonsWithNoEmail = count($dataChangesToSendEmailsFor) - $countEmailsSend;
        $output->writeln("For " . $countPersonsWithNoEmail." persons no E-Mails were send, because no E-Mail-address is defined.");
    }
}
