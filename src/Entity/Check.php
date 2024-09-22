<?php

namespace App\Entity;

use App\Repository\CheckRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CheckRepository::class)]
#[ORM\Table(name: '`check`')]
class Check
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $ord = null;

    #[ORM\ManyToOne(inversedBy: 'checks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $checker = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrd(): ?Order
    {
        return $this->ord;
    }

    public function setOrd(Order $ord): static
    {
        $this->ord = $ord;

        return $this;
    }

    public function getChecker(): ?User
    {
        return $this->checker;
    }

    public function setChecker(?User $checker): static
    {
        $this->checker = $checker;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
