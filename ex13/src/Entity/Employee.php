<?php

namespace App\Entity;

use App\Repository\EmployeeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EmployeeRepository::class)]
#[ORM\Table(name: 'employee')]
#[ORM\UniqueConstraint(name: 'uniq_employee_email', columns: ['email'])]
class Employee
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $firstname;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $lastname;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $email;

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotNull]
    private \DateTimeInterface $birthdate;

    #[ORM\Column]
    private bool $active = true;

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotNull]
    private \DateTimeInterface $employedSince;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $employedUntil = null;

    #[ORM\Column(length: 2)]
    #[Assert\Choice(choices: ['8', '6', '4'])]
    private string $hours;

    #[ORM\Column]
    #[Assert\Positive]
    private int $salary;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(choices: [
        'manager',
        'account_manager',
        'qa_manager',
        'dev_manager',
        'ceo',
        'coo',
        'backend_dev',
        'frontend_dev',
        'qa_tester'
    ])]
    private string $position;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'employees')]
    #[ORM\JoinColumn(nullable: true)]
    private ?self $manager = null;

    #[ORM\OneToMany(mappedBy: 'manager', targetEntity: self::class)]
    private Collection $employees;

    public function __construct()
    {
        $this->employees = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getFirstname(): string { return $this->firstname; }
    public function setFirstname(string $firstname): self { $this->firstname = $firstname; return $this; }

    public function getLastname(): string { return $this->lastname; }
    public function setLastname(string $lastname): self { $this->lastname = $lastname; return $this; }

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }

    public function getBirthdate(): \DateTimeInterface { return $this->birthdate; }
    public function setBirthdate(\DateTimeInterface $birthdate): self { $this->birthdate = $birthdate; return $this; }

    public function isActive(): bool { return $this->active; }
    public function setActive(bool $active): self { $this->active = $active; return $this; }

    public function getEmployedSince(): \DateTimeInterface { return $this->employedSince; }
    public function setEmployedSince(\DateTimeInterface $date): self { $this->employedSince = $date; return $this; }

    public function getEmployedUntil(): ?\DateTimeInterface { return $this->employedUntil; }
    public function setEmployedUntil(?\DateTimeInterface $date): self { $this->employedUntil = $date; return $this; }

    public function getHours(): string { return $this->hours; }
    public function setHours(string $hours): self { $this->hours = $hours; return $this; }

    public function getSalary(): int { return $this->salary; }
    public function setSalary(int $salary): self { $this->salary = $salary; return $this; }

    public function getPosition(): string { return $this->position; }
    public function setPosition(string $position): self { $this->position = $position; return $this; }

    public function getManager(): ?self { return $this->manager; }
    public function setManager(?self $manager): self { $this->manager = $manager; return $this; }

    public function getEmployees(): Collection { return $this->employees; }
}