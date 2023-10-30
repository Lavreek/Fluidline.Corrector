<?php

namespace App\Controller;

use App\Form\CSVDeleteType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DeleteController extends AbstractController
{
    #[Route('/delete', name: 'app_delete')]
    public function index(Request $request): Response
    {
        $deleteForm = $this->createForm(CSVDeleteType::class);
        $deleteForm->handleRequest($request);

        if ($deleteForm->isSubmitted() and $deleteForm->isValid()) {
            $outputPath = $this->getParameter('output');

            $files = array_diff(scandir($outputPath), ['.', '..', 'endings.txt']);

            while ($files) {
                unlink($outputPath . array_shift($files));
            }

            $tmpPath = $this->getParameter('tmp');

            $files = array_diff(scandir($tmpPath), ['.', '..']);

            while ($files) {
                unlink($tmpPath . array_shift($files));
            }
        }

        return $this->redirectToRoute('app_home');
    }
}