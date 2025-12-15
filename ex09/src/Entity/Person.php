<?php

namespace App\Entity;

use App\Repository\PersonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PersonRepository::class)]
class Person
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length:255)]
    private ?string $username = null;

    #[ORM\Column(length:255, nullable:true)]
    private ?string $name = null;

    #[ORM\Column(length:255, nullable:true)]
    private ?string $email = null;

    #[ORM\Column]
    private bool $enable = true;

    #[ORM\Column(type:"datetime", nullable:true)]
    private ?\DateTimeInterface $birthdate = null;

    #[ORM\Column(length:20)]
    private string $maritalStatus = 'single';

    // --- Relations ---
    #[ORM\OneToMany(mappedBy:"person", targetEntity:Address::class, cascade:["persist", "remove"])]
    private Collection $addresses;

    #[ORM\OneToOne(mappedBy:"person", targetEntity:BankAccount::class, cascade:["persist", "remove"])]
    private ?BankAccount $bankAccount = null;

    public function __construct()
    {
        $this->addresses = new ArrayCollection();
    }

    // Getters / setters...

    public function getId(): ?int { return $this->id; }

    public function getUsername(): ?string { return $this->username; }
    public function setUsername(string $u): self { $this->username = $u; return $this; }

    public function getName(): ?string { return $this->name; }
    public function setName(?string $n): self { $this->name = $n; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $e): self { $this->email = $e; return $this; }

    public function isEnable(): bool { return $this->enable; }
    public function setEnable(bool $e): self { $this->enable = $e; return $this; }

    public function getBirthdate(): ?\DateTimeInterface { return $this->birthdate; }
    public function setBirthdate(?\DateTimeInterface $b): self { $this->birthdate = $b; return $this; }

    public function getMaritalStatus(): string { return $this->maritalStatus; }
    public function setMaritalStatus(string $m): self { $this->maritalStatus = $m; return $this; }

    /** @return Collection<int, Address> */
    public function getAddresses(): Collection { return $this->addresses; }
    public function addAddress(Address $address): self {
        if (!$this->addresses->contains($address)) {
            $this->addresses->add($address);
            $address->setPerson($this);
        }
        return $this;
    }

    public function removeAddress(Address $address): self {
        if ($this->addresses->removeElement($address)) {
            if ($address->getPerson() === $this) {
                $address->setPerson(null);
            }
        }
        return $this;
    }

    public function getBankAccount(): ?BankAccount { return $this->bankAccount; }

    public function setBankAccount(?BankAccount $ba): self {
        $this->bankAccount = $ba;
        if ($ba && $ba->getPerson() !== $this) {
            $ba->setPerson($this);
        }
        return $this;
    }
}