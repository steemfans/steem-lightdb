<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PostsRepository")
 */
class Posts
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="bigint", options={"unsigned"=true})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Tags", inversedBy="posts")
     * @ORM\JoinColumn(nullable=false)
     */
    private $main_tag;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Users", inversedBy="posts")
     * @ORM\JoinColumn(nullable=false)
     */
    private $author;

    /**
     * @ORM\Column(type="string", length=500)
     */
    private $permlink;

    /**
     * @ORM\Column(type="string", length=500, nullable=true)
     */
    private $title;

    /**
     * @ORM\Column(type="text")
     */
    private $body;

    /**
     * @ORM\Column(type="json")
     */
    private $json_metadata;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Tags")
     */
    private $tags;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Comments", mappedBy="post")
     */
    private $comments;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\PostsVotes", mappedBy="post")
     */
    private $postsVotes;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->postsVotes = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getMainTag(): ?Tags
    {
        return $this->main_tag;
    }

    public function setMainTag(?Tags $main_tag): self
    {
        $this->main_tag = $main_tag;

        return $this;
    }

    public function getAuthor(): ?Users
    {
        return $this->author;
    }

    public function setAuthor(?Users $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getPermlink(): ?string
    {
        return $this->permlink;
    }

    public function setPermlink(string $permlink): self
    {
        $this->permlink = $permlink;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;

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
     * @return Collection|Tags[]
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tags $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags[] = $tag;
        }

        return $this;
    }

    public function removeTag(Tags $tag): self
    {
        if ($this->tags->contains($tag)) {
            $this->tags->removeElement($tag);
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
            $comment->setPost($this);
        }

        return $this;
    }

    public function removeComment(Comments $comment): self
    {
        if ($this->comments->contains($comment)) {
            $this->comments->removeElement($comment);
            // set the owning side to null (unless already changed)
            if ($comment->getPost() === $this) {
                $comment->setPost(null);
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
            $postsVote->setPost($this);
        }

        return $this;
    }

    public function removePostsVote(PostsVotes $postsVote): self
    {
        if ($this->postsVotes->contains($postsVote)) {
            $this->postsVotes->removeElement($postsVote);
            // set the owning side to null (unless already changed)
            if ($postsVote->getPost() === $this) {
                $postsVote->setPost(null);
            }
        }

        return $this;
    }
}
