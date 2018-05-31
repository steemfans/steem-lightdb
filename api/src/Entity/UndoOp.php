<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UndoOpRepository")
 */
class UndoOp
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="bigint", options={"unsigned"=true})
     */
    private $id;

    /**
     * @ORM\Column(type="bigint", options={"unsigned"=true})
     */
    private $block_num;

    /**
     * @ORM\Column(type="string", length=40)
     */
    private $transaction_id;

    /**
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private $op_index;

    /**
     * @ORM\Column(type="text")
     */
    private $op;

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

    public function getTransactionId(): ?string
    {
        return $this->transaction_id;
    }

    public function setTransactionId(string $transaction_id): self
    {
        $this->transaction_id = $transaction_id;

        return $this;
    }

    public function getOpIndex(): ?int
    {
        return $this->op_index;
    }

    public function setOpIndex(int $op_index): self
    {
        $this->op_index = $op_index;

        return $this;
    }

    public function getOp(): ?string
    {
        return $this->op;
    }

    public function setOp(string $op): self
    {
        $this->op = $op;

        return $this;
    }
}