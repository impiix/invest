<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class FileService
 */
class FileService
{
    public function process(UploadedFile $file): array
    {
        $data = [];
        $path = $file->getRealPath();
        $handle = fopen($path, 'r');
        while(($row = fgetcsv($handle, null, ';')) !== false) {
            $data[] = $row;
        }
        $first = array_shift($data);
        $data = array_reverse($data);

        return $data;
    }
}
