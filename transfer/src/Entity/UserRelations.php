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
     * @ORM\Column(type="string", length=20)
     */
    private $what;

    public function getId()
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

    public function setWhat(string $what): self
    {
        $this->what = $what;

        return $this;
    }
}
