<?php

namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Ignore;

#[ORM\Entity]
class BattleHistory extends AbstractEntity
{
    #[ORM\Column]
    private bool $isPlayerWon = false;

    #[ORM\ManyToOne(targetEntity: Card::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Card $userCard;

    #[ORM\ManyToOne(targetEntity: Card::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Card $enemyCard;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Ignore]
    private ?User $user = null;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function isPlayerWon(): bool
    {
        return $this->isPlayerWon;
    }

    public function setIsPlayerWon(bool $isPlayerWon): void
    {
        $this->isPlayerWon = $isPlayerWon;
    }


    public function getEnemyCard(): Card
    {
        return $this->enemyCard;
    }

    public function setEnemyCard(Card $enemyCard): void
    {
        $this->enemyCard = $enemyCard;
    }

    public function getUserCard(): Card
    {
        return $this->userCard;
    }

    public function setUserCard(Card $userCard): void
    {
        $this->userCard = $userCard;
    }
}
