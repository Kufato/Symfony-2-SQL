<?php

namespace App\Entity;

use App\Repository\BankAccountRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BankAccountRepository::class)]
class BankAccount
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length:255, nullable:true)]
    private ?string $iban = null;

    #[ORM\OneToOne(inversedBy:"bankAccount")]
    #[ORM\JoinColumn(unique:true)]
    private ?Person $person = null;

    public function getId(): ?int { return $this->id; }

    public function getIban(): ?string { return $this->iban; }
    public function setIban(?string $i): self { $this->iban = $i; return $this; }

    public function getPerson(): ?Person { return $this->person; }
    public function setPerson(?Person $p): self { $this->person = $p; return $this; }
}