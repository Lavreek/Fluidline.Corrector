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
        /** @var ValidatorRepository $validator */
        $validator = $managerRegistry->getRepository(Validator::class);
        
        ini_set('max_execution_time', 1200);

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