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
     * @ORM\Column(type="string", length=500)
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
     * @ORM\Column(type="string", length=500)
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

}
