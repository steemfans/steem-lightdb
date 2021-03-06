<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UsersRepository")
 */
class Users
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="bigint", options={"unsigned"=true})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $username;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $json_metadata;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Comments", mappedBy="author")
     */
    private $comments;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\UserRelations", mappedBy="follower")
     */
    private $userRelations;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\CommentsVotes", mappedBy="user")
     */
    private $commentsVotes;

    /**
     * @ORM\Column(type="boolean")
     */
    private $is_pow;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $created_at;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $updated_at;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->userRelations = new ArrayCollection();
        $this->commentsVotes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getJsonMetadata(): ?string
    {
        return $this->json_metadata;
    }

    public function setJsonMetadata(?string $json_metadata): self
    {
        $this->json_metadata = $json_metadata;

        return $this;
    }

    public function getIsPow(): ?bool
    {
        return $this->is_pow;
    }

    public function setIsPow(bool $is_pow): self
    {
        $this->is_pow = $is_pow;

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

    public function getUpdatedAt(): ?int
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?int $updated_at): self
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    /**
     * @return Collection|Comments[]
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comments $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments[] = $comment;
            $comment->setAuthor($this);
        }

        return $this;
    }

    public function removeComment(Comments $comment): self
    {
        if ($this->comments->contains($comment)) {
            $this->comments->removeElement($comment);
            // set the owning side to null (unless already changed)
            if ($comment->getAuthor() === $this) {
                $comment->setAuthor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|UserRelations[]
     */
    public function getUserRelations(): Collection
    {
        return $this->userRelations;
    }

    public function addUserRelation(UserRelations $userRelation): self
    {
        if (!$this->userRelations->contains($userRelation)) {
            $this->userRelations[] = $userRelation;
            $userRelation->setFollower($this);
        }

        return $this;
    }

    public function removeUserRelation(UserRelations $userRelation): self
    {
        if ($this->userRelations->contains($userRelation)) {
            $this->userRelations->removeElement($userRelation);
            // set the owning side to null (unless already changed)
            if ($userRelation->getFollower() === $this) {
                $userRelation->setFollower(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|CommentsVotes[]
     */
    public function getCommentsVotes(): Collection
    {
        return $this->commentsVotes;
    }

    public function addCommentsVote(CommentsVotes $commentsVote): self
    {
        if (!$this->commentsVotes->contains($commentsVote)) {
            $this->commentsVotes[] = $commentsVote;
            $commentsVote->setUser($this);
        }

        return $this;
    }

    public function removeCommentsVote(CommentsVotes $commentsVote): self
    {
        if ($this->commentsVotes->contains($commentsVote)) {
            $this->commentsVotes->removeElement($commentsVote);
            // set the owning side to null (unless already changed)
            if ($commentsVote->getUser() === $this) {
                $commentsVote->setUser(null);
            }
        }

        return $this;
    }

}
