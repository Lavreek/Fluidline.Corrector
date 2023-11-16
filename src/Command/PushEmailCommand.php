<?php

namespace App\Command;

use App\Entity\Validator;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'PushEmail',
    description: 'Add a short description for your command',
)]
final class PushEmailCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure() : void { }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $container = $this->getApplication()->getKernel()->getContainer();

        $serializedPath = $container->getParameter('serialized');

        $difference = ['.', '..', '.gitignore'];
        $files = array_diff(scandir($serializedPath), $difference);

        for ($i = 0; $i < 20; $i++) {
            $file = array_shift($files);

            $f = fopen($serializedPath . $file, 'r');

            if (flock($f, LOCK_EX | LOCK_NB, $would_block)) {
                echo "Использую файл: $file\n";

                /** @var Validator $content */
                $content = unserialize(stream_get_contents($f));

                /** @var ManagerRegistry $doctrine */
                $doctrine = $container->get('doctrine');

                $manager = $doctrine->getManager();

                /** @var Validator $validator */
                $validator = $manager->getRepository(Validator::class)
                    ->findOneBy(['email' => $content->getEmail()]);

                if (!is_null($validator)) {
                    $validator->setSmtpStatus('Unknown');
                    $validator->setUpdated(new \DateTime());

                } else {
                    $manager->persist($content);
                }

                $manager->flush();

                fclose($f);

                unlink($serializedPath . $file);
            }
        }

        if ($would_block) {
            echo "Другой процесс уже удерживает блокировку файла\n";
        }

        return Command::SUCCESS;
    }
}
