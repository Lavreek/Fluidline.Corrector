<?php

namespace App\Services;

use App\Entity\Validator;
use Doctrine\Persistence\ObjectManager;

class DbCompare
{
    private string $serializedPath;

    private ObjectManager $objectManager;

    private string $filePath;
    
    public function setFilePath(string $path) : void
    {
        $this->filePath = $path;
    }

    public function getFilePath() : string
    {
        return $this->filePath;
    }

    public function getSerializedPath() : string
    {
        return $this->serializedPath;
    }

    public function setSerializedPath(string $path) : void
    {
        $this->serializedPath = $path;
    }

    public function setObjectManager(ObjectManager $manager) : void
    {
        $this->objectManager = $manager;
    }

    public function getObjectManager() : ObjectManager
    {
        return $this->objectManager;
    }

    public function compareEmails($email, $listname) : void
    {
        $serializedPath = $this->getSerializedPath();

        /** @var ObjectManager $manager */
        $manager = $this->getObjectManager();

        $validatorRepository = $manager->getRepository(Validator::class);

        /** @var Validator $validator */
        $validator = $validatorRepository->findOneBy(['email' => $email]);

        if (is_null($validator)) {
            file_put_contents($serializedPath . $listname . ".csv", $email . PHP_EOL, FILE_APPEND);
        }
    }

    public function correctEmails() : void
    {
        $filepath = $this->getFilePath();

        $fileinfo = pathinfo($filepath);

        $f = fopen($filepath, 'r');

        while ($row = fgetcsv($f)) {
            $this->compareEmails($row[0], $fileinfo['filename']);
        }
        fclose($f);
        $newFilePath = $this->serializedPath . $fileinfo['filename'] . ".csv";
        $this->forceDownload($newFilePath);
        
    }

    private function forceDownload($file)
    {
        if (file_exists($file)) {
          if (ob_get_level()) {
            ob_end_clean();
          }
          header('Content-Description: File Transfer');
          header('Content-Type: application/octet-stream');
          header('Content-Disposition: attachment; filename=' . basename($file));
          header('Content-Transfer-Encoding: binary');
          header('Expires: 0');
          header('Cache-Control: must-revalidate');
          header('Pragma: public');
          header('Content-Length: ' . filesize($file));
          readfile($file);
          unlink($file);
          exit;
        }
      }
}