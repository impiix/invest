<?php

declare(strict_types=1);

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;


class Robot
{
    #[Assert\NotBlank]
    protected $name;
}
