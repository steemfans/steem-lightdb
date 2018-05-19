<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CommentsVotesRepository")
 */
class CommentsVotes
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="bigint", options={"unsigned"=true})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Comments", inversedBy="commentsVotes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $comment;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Users", inversedBy="commentsVotes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="smallint", length=6)
     */
    private $weight;

    /**
     * @ORM\Column(type="boolean")
     */
    private $updown;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $created_at;

    public function getId()
    {
        return $this->id;
    }

    public function getComment(): ?Comments
    {
        return $this->comment;
    }

    public function setComment(?Comments $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getUser(): ?Users
    {
        return $this->user;
    }

    public function setUser(?Users $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getWeight(): ?int
    {
        return $this->weight;
    }

    public function setWeight(int $weight): self
    {
        $this->weight = $weight;

        return $this;
    }

    public function getUpdown(): ?bool
    {
        return $this->updown;
    }

    public function setUpdown(bool $updown): self
    {
        $this->updown = $updown;

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
