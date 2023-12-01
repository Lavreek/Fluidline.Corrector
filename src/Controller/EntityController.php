<?php

namespace App\Controller;

use App\Entity\Validator;
use App\Services\EntityGetter;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EntityController extends AbstractController
{
    #[Route('/active', name: 'response_email_active')]
    public function active(ManagerRegistry $managerRegistry): Response
    {
        $entities = $managerRegistry->getRepository(Validator::class)->findBy(['smtp_status' => 'Active']);

        $content = "";

        foreach ($entities as $entity) {
            $content .= $entity->getEmail() ."\n";
        }

        $getter = new EntityGetter();
        return $getter->getResponse("active.csv", $content);
    }

    #[Route('/notactive', name: 'response_not_active')]
    public function notActive(ManagerRegistry $managerRegistry): Response
    {
        $entities = $managerRegistry->getRepository(Validator::class)->findBy(['smtp_status' => 'Not active']);

        $content = "";

        foreach ($entities as $entity) {
            $content .= $entity->getEmail() ."\n";
        }

        $getter = new EntityGetter();
        return $getter->getResponse("notactive.csv", $content);
    }
}
