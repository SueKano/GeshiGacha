<?php

namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Card extends AbstractEntity
{
    #[ORM\Column(length: 50)]
    private ?string $name = null;

    #[ORM\Column(length: 25)]
    private ?string $type = null;

    #[ORM\Column(length: 5, nullable: false)]
    private string $rarity;

    #[ORM\Column(nullable: false)]
    private int $attack;

    #[ORM\Column(nullable: false)]
    private int $defense;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $urlImage = null;

    #[ORM\Column(length: 15, nullable: false)]
    private string $age;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getRarity(): string
    {
        return $this->rarity;
    }

    public function setRarity(string $rarity): void
    {
        $this->rarity = $rarity;
    }

    public function getAttack(): int
    {
        return $this->attack;
    }

    public function setAttack(int $attack): void
    {
        $this->attack = $attack;
    }

    public function getDefense(): int
    {
        return $this->defense;
    }

    public function setDefense(int $defense): void
    {
        $this->defense = $defense;
    }

    public function getUrlImage(): ?string
    {
        return $this->urlImage;
    }

    public function setUrlImage(?string $urlImage): void
    {
        $this->urlImage = $urlImage;
    }

    public function getAge(): string
    {
        return $this->age;
    }

    public function setAge(string $age): void
    {
        $this->age = $age;
    }

    public function getHealth(): ?int
    {
        return ($this->getAttack() + $this->getDefense()) * 10;
    }

}
