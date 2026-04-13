<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Environment extends AbstractEntity
{
    #[ORM\Column(type: 'string', length: 50)]
    private string $name;

    #[ORM\Column(type: 'string', length: 100)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $affectedType;

    #[ORM\Column(type: 'string', length: 15)]
    private string $benefitType;

    #[ORM\Column(nullable: true)]
    private ?float $quantity = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $turns = 0;
    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $isUpAffectedType = null;

    public function getIsUpAffectedType(): ?bool
    {
        return $this->isUpAffectedType;
    }

    public function setIsUpAffectedType(?bool $isUpAffectedType): void
    {
        $this->isUpAffectedType = $isUpAffectedType;
    }

    public function getTurns(): int
    {
        return $this->turns;
    }

    public function setTurns(int $turns): void
    {
        $this->turns = $turns;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    public function setQuantity(?float $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getBenefitType(): string
    {
        return $this->benefitType;
    }

    public function setBenefitType(string $benefitType): void
    {
        $this->benefitType = $benefitType;
    }

    public function getAffectedType(): string
    {
        return $this->affectedType;
    }

    public function setAffectedType(string $affectedType): void
    {
        $this->affectedType = $affectedType;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }
}
