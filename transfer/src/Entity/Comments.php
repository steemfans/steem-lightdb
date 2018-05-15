<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CommentsRepository")
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
     * @ORM\Column(type="json", nullable=true)
     */
    private $json_metadata;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Posts")
     * @ORM\JoinColumn(nullable=false)
     */
    private $post;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Users")
     * @ORM\JoinColumn(nullable=false)
     */
    private $parent_author;

    /**
     * @ORM\Column(type="string", length=500)
     */
    private $parent_permlink;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Users")
     * @ORM\JoinColumn(nullable=false)
     */
    private $author;

    public function getId()
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

    public function getJsonMetadata()
    {
        return $this->json_metadata;
    }

    public function setJsonMetadata($json_metadata): self
    {
        $this->json_metadata = $json_metadata;

        return $this;
    }

    public function getPost(): ?Posts
    {
        return $this->post;
    }

    public function setPost(?Posts $post): self
    {
        $this->post = $post;

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

    public function getParentPermlink(): ?string
    {
        return $this->parent_permlink;
    }

    public function setParentPermlink(string $parent_permlink): self
    {
        $this->parent_permlink = $parent_permlink;

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
}
