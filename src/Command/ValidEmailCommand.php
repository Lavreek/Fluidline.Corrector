<?php

namespace App\Command;

use App\Entity\Validator;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'ValidEmail',
    description: 'Add a short description for your command',
)]
final class ValidEmailCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure() : void { }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $container = $this->getApplication()->getKernel()->getContainer();

        /** @var ManagerRegistry $doctrine */
        $doctrine = $container->get('doctrine');

        $manager = $doctrine->getManager();

        $validatorRepository = $manager->getRepository(Validator::class);

        /** @var Validator $validator */
        $validator = $validatorRepository->findOneBy(['smtp_status' => "Unknown"]);

        if (!is_null($validator)) {
            $email = $validator->getEmail();

            if ($this->validateEmailSMTP($email)) {
                $validator->setSmtpStatus('Active');

            } else {
                $validator->setSmtpStatus('Not active');
            }

            $validator->setUpdated(new \DateTime());
            $manager->persist($validator);
            $manager->flush();
        }

        return Command::SUCCESS;
    }

    private function validateEmailSMTP($email) : bool
    {
        $domain = substr(strrchr($email, "@"), 1);
        $records = dns_get_record($domain, DNS_MX);

        if(empty($records)) {
            return false;
        }

        $mxServers = array();
        foreach($records as $record) {
            $mxServers[] = $record['target'];
        }

        $valid = false;

        foreach($mxServers as $mxServer) {
            $socket = @fsockopen($mxServer, 25, $errno, $error_message, 10);

            if (!$socket) {
                continue;
            }

            $response = fgets($socket);

            if (strpos($response, "220") !== false) {

                fputs($socket, "HELO yourdomain.com\r\n");
                $response = fgets($socket);

                fputs($socket, "MAIL FROM: <youremail@yourdomain.com>\r\n");
                $response = fgets($socket);

                fputs($socket, "RCPT TO: <$email>\r\n");
                $response = fgets($socket);

                if (strpos($response, "250") !== false) {
                    $valid = true;
                }

                fputs($socket, "QUIT\r\n");
                fclose($socket);

                break;
            }

            fclose($socket);
        }

        return $valid;
    }
}
