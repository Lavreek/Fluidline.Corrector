<?php

namespace App\Controller;

use App\Form\CSVDeleteType;
use App\Form\CSVUploadType;
use App\Services\EmailCorrector;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomepageController extends AbstractController
{
    #[Route('/', name: 'app_root')]
    #[Route('/home', name: 'app_home')]
    #[Route('/homepage', name: 'app_homepage')]
    public function index(Request $request, ManagerRegistry $managerRegistry): Response
    {
        ini_set('max_execution_time', 1200);

        $csvUploadForm = $this->createForm(CSVUploadType::class);
        $csvUploadForm->handleRequest($request);

        $deleteForm = $this->createForm(CSVDeleteType::class);

        $outputPath = $this->getParameter('output');
        $defaultEmailEndings = 'biz|com|edu|info|org|pro|az|by|kg|kz|ru|su|tj|tm|uz';

        $emailEndings = "";

        if (file_exists($outputPath ."endings.txt")) {
            $emailEndings = file_get_contents($outputPath ."endings.txt");
        }

        if (empty($emailEndings)) {
            $emailEndings = $defaultEmailEndings;
        }

        if ($csvUploadForm->isSubmitted() and $csvUploadForm->isValid()) {
            $formData = $csvUploadForm->getData();

            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $formData['file'];

            $emailEndings = $formData['endings'];

            switch ($uploadedFile->getMimeType()) {
                case 'text/plain' : {
                    $tmpPath = $this->getParameter('tmp');

                    if (!is_dir($tmpPath)) {
                        mkdir($tmpPath, recursive: true);
                    }

                    $fileinfo = pathinfo($uploadedFile->getClientOriginalName());

                    $filename = $fileinfo['filename'] .".csv";

                    $uploadedFile->move($tmpPath, $filename);

                    $this->startProcessing(
                        $outputPath, $tmpPath . $filename, $filename, $emailEndings,
                        $managerRegistry->getManager()
                    );

                    break;
                }

                case 'text/csv' : {
                    $this->startProcessing(
                        $outputPath, $uploadedFile->getRealPath(), $uploadedFile->getClientOriginalName(),
                        $emailEndings, $managerRegistry->getManager()
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

            if ($emailEndings != $defaultEmailEndings) {
                $endingsFile = $outputPath ."endings.txt";
                touch($endingsFile);

                $f = fopen($endingsFile, 'r+');
                fwrite($f, $emailEndings);
                fclose($f);
            }
        }

        $files = array_diff(scandir($outputPath), ['.', '..']);

        return $this->render('homepage/index.html.twig', [
            'upload_files' => $files,
            'form_endings' => $emailEndings,
            'delete_form' => $deleteForm->createView(),
            'upload_form' => $csvUploadForm->createView(),
        ]);
    }

    private function startProcessing($outputPath, $filePath, $originName, $endings, $objectManager)
    {
        $corrector = new EmailCorrector();
        $corrector->setOutputPath($outputPath);
        $corrector->setFilePath($filePath);
        $corrector->setOutputOriginName($originName);
        $corrector->setEmailsEndings($endings);
        $corrector->setObjectManager($objectManager);
        $corrector->setSerializedPath($this->getParameter('serialized'));
        $corrector->correctEmails();
    }
}