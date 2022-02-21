<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Class OutputService
 */
class OutputService
{
    public function output(string $output)
    {
        echo $output . '<br>';
    }

    public function outputArray(array $output)
    {
        print_r($output);
    }
}
