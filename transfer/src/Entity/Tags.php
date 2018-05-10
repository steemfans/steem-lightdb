<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TagsRepository")
 */
class Tags
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $tag_name;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Posts", mappedBy="main_tag")
     */
    private $posts;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getTagName(): ?string
    {
        return $this->tag_name;
    }

    public function setTagName(string $tag_name): self
    {
        $this->tag_name = $tag_name;

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
            $post->setMainTag($this);
        }

        return $this;
    }

    public function removePost(Posts $post): self
    {
        if ($this->posts->contains($post)) {
            $this->posts->removeElement($post);
            // set the owning side to null (unless already changed)
            if ($post->getMainTag() === $this) {
                $post->setMainTag(null);
            }
        }

        return $this;
    }
}
