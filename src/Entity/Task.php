<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Task
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column]
    public string $name;

    #[ORM\Column]
    public string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $description = null;

    #[ORM\Column(type: 'json')]
    public array $bullets = [];

    #[ORM\Column(nullable: true)]
    public ?string $website = null;

    #[ORM\Column]
    public string $castorFile;
}
