<?php

namespace App\Services;

use App\Entity\Validator;
use Doctrine\Persistence\ObjectManager;

class EmailCorrector
{
    private string $filePath;

    private string $outputOriginName;

    private string $outputPath;

    private string $emailsEndings;

    private string $matchedEmails = "";

    private string $wrongEmails = "";

    private string $multiEmails = "";

    private ObjectManager $objectManager;

    /** @var string $escapeCharacters | Символы, которые необходимо обрезать с двух концов строки */
    private string $escapeCharacters = '\?,\.\SP\!\"\#\$\%\&\(\)\*\+\`\~\,\-\;\:\<\>\=\@';

    private array $replaceCharacters = ["​"];

    public function __construct() { }

    public function setObjectManager(ObjectManager $manager) : void
    {
        $this->objectManager = $manager;
    }

    public function getObjectManager() : ObjectManager
    {
        return $this->objectManager;
    }

    public function setFilePath(string $path) : void
    {
        $this->filePath = $path;
    }

    public function getFilePath() : string
    {
        return $this->filePath;
    }

    public function setOutputPath(string $path) : void
    {
        $this->outputPath = $path;
    }

    public function getOutputPath() : string
    {
        return $this->outputPath;
    }

    public function setOutputOriginName(string $name) : void
    {
        $this->outputOriginName = $name;
    }

    public function getOutputOriginName() : string
    {
        return $this->outputOriginName;
    }

    public function setEmailsEndings(string $endings) : void
    {
        $this->emailsEndings = $endings;
    }

    public function getEmailEndings() : string
    {
        return $this->emailsEndings;
    }

    public function correctEmails() : void
    {
        $filepath = $this->getFilePath();

        $f = fopen($filepath, 'r');

        $trim = $this->escapeCharacters;

        $this->emptyFileEmails($this->getOutputOriginName());
        $this->emptyFileEmails('wrong-'. $this->getOutputOriginName());
        $this->emptyFileEmails('multi-'. $this->getOutputOriginName());
        $this->emptyFileEmails('problem-'. $this->getOutputOriginName());

        while ($row = fgetcsv($f)) {
            $email = trim(array_shift($row), $trim);
            $email = str_replace($this->replaceCharacters, '', $email);
            $count = $this->countOfEmails($email);

            if ($count > 0) {
                if ($count === 1) {
                    $this->matchEmail($email);

                } elseif ($count > 1) {
                    $this->appendMultiEmails($email);
                }
            } else {
                $this->appendWrongEmails($email);
            }
        }

        fclose($f);

        $this->writeFileEmails($this->getOutputOriginName(), $this->matchedEmails);
        $this->writeFileEmails("wrong-". $this->getOutputOriginName(), $this->wrongEmails);
        $this->writeFileEmails("multi-". $this->getOutputOriginName(), $this->multiEmails);
    }

    private function countOfEmails(string $dataRow) : int
    {
        preg_match_all('#\@#', $dataRow, $match);

        if (isset($match[0])) {
            return count($match[0]);
        }

        return 0;
    }

    private function matchEmail($email) : void
    {
        $emailEndings = $this->getEmailEndings();
        $pattern = "#[\w+_\.\+\-\?\']+@([\w+.-]+?)\.({$emailEndings})#u";

        preg_match($pattern, $email, $match);

        if (isset($match[0])) {
//            $this->pushEmail($match[0]);
            $this->appendMatchedEmails($match[0]);

        } else {
            $pattern = "#[\w+|_?|\-?]+\.?+@([\w+|\.?|\-?]+)\.(\w+)?#u";

            preg_match($pattern, $email, $fullEnding);

            if (isset($fullEnding[2])) {
                $ending = &$fullEnding[2];

                switch ($ending) {
                    case "u": case "r": {
                        $ending = '.ru';
//                        $this->pushEmail(implode('', $fullEnding));
                        $this->appendMatchedEmails(implode('', $fullEnding));

                    break;
                    }
                    case "c": case "co": {
                        $ending = '.com';
//                        $this->pushEmail(implode('', $fullEnding));
                        $this->appendMatchedEmails(implode('', $fullEnding));

                        break;
                    }
                }
            } else {
                $this->appendWrongEmails($email);
            }
        }
    }

    private function appendMatchedEmails($email) : void
    {
        $this->matchedEmails .= $email ."\n";

        if (strlen($this->matchedEmails) > (10 * 1024 * 1024)) {
            $this->writeFileEmails($this->getOutputOriginName(), $this->matchedEmails);
        }
    }

    private function appendWrongEmails($email) : void
    {
        $this->wrongEmails .= $email ."\n";

        if (strlen($this->wrongEmails) > (10 * 1024 * 1024)) {
            $this->writeFileEmails("wrong-". $this->getOutputOriginName(), $this->wrongEmails);
        }
    }

    private function appendMultiEmails($email) : void
    {
        $this->multiEmails .= $email ."\n";

        if (strlen($this->multiEmails) > (10 * 1024 * 1024)) {
            $this->writeFileEmails("multi-". $this->getOutputOriginName(), $this->multiEmails);
        }
    }

    private function writeFileEmails($filename, string &$content) : void
    {
        $outputPath = $this->getOutputPath();

        file_put_contents($outputPath . $filename, $content, FILE_APPEND);

        $content = "";
    }

    private function emptyFileEmails(string $originName) : void
    {
        $outputPath = $this->getOutputPath();

        $this->checkDirectory($outputPath);

        $filepath = $outputPath . $originName;

        if (file_exists($filepath)) {
            unlink($filepath);
        }

        touch($filepath);
    }

    private function checkDirectory($path) : void
    {
        if (!is_dir($path)) {
            mkdir($path, recursive: true);
        }
    }

    private function pushEmail($email)
    {
        /** @var ObjectManager $manager */
        $manager = $this->getObjectManager();

        $validatorRepository = $manager->getRepository(Validator::class);

        /** @var Validator $validator */
        $validator = $validatorRepository->findOneBy(['email' => $email]);

        if (is_null($validator)) {
            $validator = new Validator();
            $validator->setCreated(new \DateTime());
            $validator->setEmail($email);

        } else {
            $validator->setUpdated(new \DateTime());
        }

        $validator->setSmtpStatus('Unknown');

        $manager->persist($validator);

        $manager->flush();
        $manager->clear();
    }
}