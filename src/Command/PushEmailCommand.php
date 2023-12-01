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

        $difference = ['', '.', '..', '.gitignore'];

        $lists = array_diff(scandir($serializedPath), $difference);

        foreach ($lists as $list) {
            $listpath = $serializedPath . $list . "/";

            $files = array_diff(scandir($listpath), $difference);

            if (empty($files)) {
                rmdir($serializedPath . $list);
            }

            for ($i = 0; $i < 2500; $i++) {
                $file = array_shift($files);

                if (!$file) {
                    break;
                }

                $f = fopen($listpath . $file, 'r');

                if (flock($f, LOCK_EX | LOCK_NB, $would_block)) {
                    echo "Использую файл: $list - $file\n";

                    /** @var Validator $content */
                    $content = unserialize(stream_get_contents($f));

                    /** @var ManagerRegistry $doctrine */
                    $doctrine = $container->get('doctrine');

                    $manager = $doctrine->getManager();

                    /** @var Validator $validator */
                    $validator = $manager->getRepository(Validator::class)
                        ->findOneBy(['email' => $content->getEmail()]);

                    if (!is_null($validator)) {
                        if ($validator->getList() != $list) {
                            $validator->setList($list);
                        }

                        if (is_null($validator->isMultiMailing())) {
                            $validator->setMultiMailing(false);
                        }

                        $validator->setSmtpStatus('Unknown');
                        $validator->setUpdated(new \DateTime());

                    } else {
                        if ($content->getList() != $list) {
                            $content->setList($list);
                        }

                        if (is_null($content->isMultiMailing())) {
                            $content->setMultiMailing(false);
                        }

                        $manager->persist($content);
                    }

                    $manager->flush();

                    fclose($f);

                    unlink($listpath . $file);
                }
            }

            if (isset($would_block)) {
                if ($would_block) {
                    echo "Другой процесс уже удерживает блокировку файла\n";
                }
            }
        }

        return Command::SUCCESS;
    }
}
