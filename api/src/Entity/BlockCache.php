<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BlockCacheRepository")
 */
class BlockCache
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="bigint", options={"unsigned"=true})
     */
    private $id;

    /**
     * @ORM\Column(type="bigint", options={"unsigned"=true}, unique=true)
     */
    private $block_num;

    /**
     * @ORM\Column(type="string", length=40, unique=true)
     */
    private $previous;

    /**
     * @ORM\Column(type="string", length=40, unique=true)
     */
    private $block_id;

    /**
     * @ORM\Column(type="text")
     */
    private $block_info;

    /**
     * @ORM\Column(type="integer")
     */
    private $timestamp;

    public function getId()
    {
        return $this->id;
    }

    public function getBlockNum(): ?int
    {
        return $this->block_num;
    }

    public function setBlockNum(int $block_num): self
    {
        $this->block_num = $block_num;

        return $this;
    }

    public function getPrevious(): ?string
    {
        return $this->previous;
    }

    public function setPrevious(string $previous): self
    {
        $this->previous = $previous;

        return $this;
    }

    public function getBlockId(): ?string
    {
        return $this->block_id;
    }

    public function setBlockId(string $block_id): self
    {
        $this->block_id = $block_id;

        return $this;
    }

    public function getBlockInfo(): ?string
    {
        return $this->block_info;
    }

    public function setBlockInfo(string $block_info): self
    {
        $this->block_info = $block_info;

        return $this;
    }

    public function getTimestamp(): ?int
    {
        return $this->timestamp;
    }

    public function setTimestamp(int $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }
}
