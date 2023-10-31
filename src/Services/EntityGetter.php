<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\Response;

class EntityGetter
{
    public function getResponse($filename = "Unknown", $content = "") : Response
    {
        $response = new Response();
        $response->headers->set('Content-type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', "attachment; filename=\"$filename\"");
        $response->setContent($content);
        $response->setStatusCode(200);
        $response->headers->set('Content-Transfer-Encoding', 'binary');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        return $response;
    }

}