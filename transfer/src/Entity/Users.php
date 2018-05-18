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
     * @ORM\Column(type="json")
     */
    private $json_metadata;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Posts", mappedBy="author")
     */
    private $posts;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Comments", mappedBy="author")
     */
    private $comments;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\PostsVotes", mappedBy="user")
     */
    private $postsVotes;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\UserRelations", mappedBy="follower")
     */
    private $userRelations;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\CommentsVotes", mappedBy="user")
     */
    private $commentsVotes;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->postsVotes = new ArrayCollection();
        $this->userRelations = new ArrayCollection();
        $this->commentsVotes = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getJsonMetadata()
    {
        return $this->json_metadata;
    }

    public function setJsonMetadata($json_metadata): self
    {
        $this->json_metadata = $json_metadata;

        return $this;
    }

    /**
     * @return Collection|Posts[]
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(Posts $post): self
    {
        if (!$this->posts->contains($post)) {
            $this->posts[] = $post;
            $post->setAuthor($this);
        }

        return $this;
    }

    public function removePost(Posts $post): self
    {
        if ($this->posts->contains($post)) {
            $this->posts->removeElement($post);
            // set the owning side to null (unless already changed)
            if ($post->getAuthor() === $this) {
                $post->setAuthor(null);
            }
        }

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
     * @return Collection|PostsVotes[]
     */
    public function getPostsVotes(): Collection
    {
        return $this->postsVotes;
    }

    public function addPostsVote(PostsVotes $postsVote): self
    {
        if (!$this->postsVotes->contains($postsVote)) {
            $this->postsVotes[] = $postsVote;
            $postsVote->setUser($this);
        }

        return $this;
    }

    public function removePostsVote(PostsVotes $postsVote): self
    {
        if ($this->postsVotes->contains($postsVote)) {
            $this->postsVotes->removeElement($postsVote);
            // set the owning side to null (unless already changed)
            if ($postsVote->getUser() === $this) {
                $postsVote->setUser(null);
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
