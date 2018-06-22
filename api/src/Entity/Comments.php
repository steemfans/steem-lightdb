<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CommentsRepository")
 * @ORM\Table(indexes={@ORM\Index(name="author_text_idx", columns={"author_text"}),@ORM\Index(name="parent_author_text_idx", columns={"parent_author_text"}),@ORM\Index(name="permlink_idx", columns={"permlink"}),@ORM\Index(name="parent_permlink_idx", columns={"parent_permlink"})})
 */
class Comments
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="bigint", options={"unsigned"=true})
     */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Comments", mappedBy="parent")
     */
    private $children;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Comments", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    private $parent;

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
     * @ORM\Column(type="text", nullable=true)
     */
    private $json_metadata;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Tags")
     */
    private $tags;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Users")
     * @ORM\JoinColumn(nullable=true)
     */
    private $parent_author;

    /**
     * @ORM\Column(type="string", length=500, nullable=true)
     */
    private $parent_author_text;

    /**
     * @ORM\Column(type="string", length=500)
     */
    private $parent_permlink;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Users")
     * @ORM\JoinColumn(nullable=true)
     */
    private $author;

    /**
     * @ORM\Column(type="string", length=500, nullable=true)
     */
    private $author_text;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\CommentsVotes", mappedBy="comment")
     */
    private $commentsVotes;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $created_at;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $updated_at;

    /**
     * @ORM\Column(type="boolean")
     */
    private $is_del;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->commentsVotes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getJsonMetadata(): ?string
    {
        return $this->json_metadata;
    }

    public function setJsonMetadata(?string $json_metadata): self
    {
        $this->json_metadata = $json_metadata;

        return $this;
    }

    public function getParentAuthorText(): ?string
    {
        return $this->parent_author_text;
    }

    public function setParentAuthorText(?string $parent_author_text): self
    {
        $this->parent_author_text = $parent_author_text;

        return $this;
    }

    public function getParentPermlink(): ?string
    {
        return $this->parent_permlink;
    }

    public function setParentPermlink(string $parent_permlink): self
    {
        $this->parent_permlink = $parent_permlink;

        return $this;
    }

    public function getAuthorText(): ?string
    {
        return $this->author_text;
    }

    public function setAuthorText(?string $author_text): self
    {
        $this->author_text = $author_text;

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

    public function getIsDel(): ?bool
    {
        return $this->is_del;
    }

    public function setIsDel(bool $is_del): self
    {
        $this->is_del = $is_del;

        return $this;
    }

    /**
     * @return Collection|Comments[]
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(Comments $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children[] = $child;
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(Comments $child): self
    {
        if ($this->children->contains($child)) {
            $this->children->removeElement($child);
            // set the owning side to null (unless already changed)
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

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

    public function getParentAuthor(): ?Users
    {
        return $this->parent_author;
    }

    public function setParentAuthor(?Users $parent_author): self
    {
        $this->parent_author = $parent_author;

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
            $commentsVote->setComment($this);
        }

        return $this;
    }

    public function removeCommentsVote(CommentsVotes $commentsVote): self
    {
        if ($this->commentsVotes->contains($commentsVote)) {
            $this->commentsVotes->removeElement($commentsVote);
            // set the owning side to null (unless already changed)
            if ($commentsVote->getComment() === $this) {
                $commentsVote->setComment(null);
            }
        }

        return $this;
    }

}
