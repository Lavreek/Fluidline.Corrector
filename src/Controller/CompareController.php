<?php

namespace App\Controller;

use App\Form\CSVCompareType;
use App\Services\DbCompare;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Validator;
use App\Repository\ValidatorRepository;

class CompareController extends AbstractController
{
    #[Route('/compare', name: 'app_compare')]
    public function index(Request $request, ManagerRegistry $managerRegistry): Response
    {
        /**
         * ToDo: Нужно сделать сравнение, существует ли в базе почта, если её нет, создать строку:
         * example1@examole.com EOL
         * example3@example.com EOL
         *
         * EOL - end of line -> \n
         * Убирать пробелы с концов trim()
         * Результат отправки файла в форме: Response в виде файла
         *
         *     Скорее всего, суть задачи заключается в том, чтобы получить список новых email'ов, чтобы делать
         * чтобы делать действительную аналитику списка т.е. сколько действительно новых почт существует в списке.
         *
         *     Проверку на валидацию делать не нужно, это должны делать сами аналитики. Если они не смогли этого сделать,
         * на главной странице app_root если форма для загрузки новых почт, там сразу и валидация и добавление.
         *
         *     Сохранять файлы не нужно, процесс работы таков: Отправляем список, получаем файл со списком из несуществующих
         * в системе почт.
         */

        /** @var ValidatorRepository $validator */
        $validator = $managerRegistry->getRepository(Validator::class);
        
        $csvCompareForm = $this->createForm(CSVCompareType::class);
        $csvCompareForm->handleRequest($request);
        $outputPath = $this->getParameter('output');

        if ($csvCompareForm->isSubmitted() and $csvCompareForm->isValid()) {
            $formData = $csvCompareForm->getData();

            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $formData['file'];

            switch ($uploadedFile->getMimeType()) {
                case 'text/plain' : {
                    $tmpPath = $this->getParameter('tmp');

                    if (!is_dir($tmpPath)) {
                        mkdir($tmpPath, recursive: true);
                    }

                    $fileinfo = pathinfo($uploadedFile->getClientOriginalName());

                    $filename = $fileinfo['filename'] .".csv";

                    $uploadedFile->move($tmpPath, $filename);

                    $this->startComparing(
                        $tmpPath . $filename,
                        $managerRegistry->getManager()
                    );

                    break;
                }

                case 'text/csv' : {
                    $this->startComparing(
                        $uploadedFile->getRealPath(),
                        $managerRegistry->getManager()
                    );
                    break;
                }

                case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' : {
                    $tmpPath = $this->getParameter('tmp');

                    if (!is_dir($tmpPath)) {
                        mkdir($tmpPath, recursive: true);
                    }

                    break;
                }
            }
        }
        $files = [];

        if (is_dir($outputPath)) {
            $files = array_diff(scandir($outputPath), ['.', '..']);

        } else {
            mkdir($outputPath, recursive: true);
        }


        return $this->render('compare/index.html.twig', [
            'upload_files' => $files,
            'upload_form' => $csvCompareForm->createView(),
        ]);
    }

    private function startComparing($filePath, $manager)
    {
        $compare = new DbCompare();
        $compare->setSerializedPath($this->getParameter('serialized'));
        $compare->setObjectManager($manager);
        $compare->setFilePath($filePath);
        $compare->correctEmails();
    }
}