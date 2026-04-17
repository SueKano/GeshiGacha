<?php

namespace App\Entity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Uid\UuidV4;

#[ORM\MappedSuperclass]
class AbstractEntity
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Ignore]
    private ?int $id = null;

    #[ORM\Column(type: 'uuid', unique: true, nullable: false)]
    #[Ignore]
    protected UuidV4 $uuid;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Ignore]
    protected $updatedAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Ignore]
    protected $createdAt;

    public function getUuid(): UuidV4
    {
        return $this->uuid;
    }

    public function setUuid(UuidV4 $uuid): self
    {
        $this->uuid = $uuid;
        return $this;
    }

    public function __construct()
    {
        $this->uuid = UuidV4::v4();
        $this->createdAt = new \DateTime('now');
        $this->updatedAt = new \DateTime('now');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }
}
