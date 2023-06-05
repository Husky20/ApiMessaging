<?php

namespace App\Service;

class FileService
{
    private $publicDir;

    public function __construct(string $publicDir)
    {
        $this->publicDir = $publicDir;
    }

    public function saveToFile(string $data, string $filename): void
    {
        $publicDirectory = $this->publicDir;
        $filePath = $publicDirectory . '/' . $filename;
        file_put_contents($filePath, $data);
    }

}