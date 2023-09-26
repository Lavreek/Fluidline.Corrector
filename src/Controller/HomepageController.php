<?php

namespace App\Controller;

use App\Form\CSVDeleteType;
use App\Form\CSVUploadType;
use App\Services\EmailCorrector;
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
    public function index(Request $request): Response
    {
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', 120);

        $csvUploadForm = $this->createForm(CSVUploadType::class);
        $csvUploadForm->handleRequest($request);

        $deleteForm = $this->createForm(CSVDeleteType::class);

        $outputPath = $this->getParameter('csv_output');
        $defaultEmailEndings = 'biz|com|edu|info|org|pro|az|by|kg|kz|ru|su|tj|tm|uz';

        $emailEndings = "";

        if (empty($emailEndings)) {
            $emailEndings = $defaultEmailEndings;
        }

        if (file_exists($outputPath ."endings.txt")) {
            $emailEndings = file_get_contents($outputPath ."endings.txt");
        }

        if ($csvUploadForm->isSubmitted() and $csvUploadForm->isValid()) {
            $formData = $csvUploadForm->getData();

            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $formData['file'];

            $emailEndings = $formData['endings'];

            $corrector = new EmailCorrector();
            $corrector->setOutputPath($outputPath);
            $corrector->setFilePath($uploadedFile->getRealPath());
            $corrector->setOutputOriginName($uploadedFile->getClientOriginalName());
            $corrector->setEmailsEndings($emailEndings);
            $corrector->correctEmails();

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
}