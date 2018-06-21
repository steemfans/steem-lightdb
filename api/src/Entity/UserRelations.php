<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRelationsRepository")
 */
class UserRelations
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Users", inversedBy="userRelations")
     * @ORM\JoinColumn(nullable=false)
     */
    private $follower;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Users")
     * @ORM\JoinColumn(nullable=false)
     */
    private $following;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $what;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $created_at;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFollower(): ?Users
    {
        return $this->follower;
    }

    public function setFollower(?Users $follower): self
    {
        $this->follower = $follower;

        return $this;
    }

    public function getFollowing(): ?Users
    {
        return $this->following;
    }

    public function setFollowing(?Users $following): self
    {
        $this->following = $following;

        return $this;
    }

    public function getWhat(): ?string
    {
        return $this->what;
    }

    public function setWhat(?string $what): self
    {
        $this->what = $what;

        return $this;
    }

    public function getCreatedAt(): ?int
    {
        return $this->created_at;
    }

    public function setCreatedAt(?int $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }
}
