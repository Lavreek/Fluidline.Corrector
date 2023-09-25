<?php

namespace App\Controller;

use App\Form\CSVUploadType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        $csvUploadForm = $this->createForm(CSVUploadType::class);
        $csvUploadForm->handleRequest($request);

        if ($csvUploadForm->isSubmitted() and $csvUploadForm->isValid()) {
            $formData = $csvUploadForm->getData();


        }

        return $this->render('homepage/index.html.twig', [
            'upload_form' => $csvUploadForm->createView(),
        ]);
    }
}
