<?php

namespace App\Entity;

use App\Repository\AddressRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AddressRepository::class)]
class Address
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type:"text")]
    private string $address;

    #[ORM\ManyToOne(inversedBy:"addresses")]
    private ?Person $person = null;

    public function getId(): ?int { return $this->id; }

    public function getAddress(): string { return $this->address; }
    public function setAddress(string $a): self { $this->address = $a; return $this; }

    public function getPerson(): ?Person { return $this->person; }
    public function setPerson(?Person $p): self { $this->person = $p; return $this; }
}