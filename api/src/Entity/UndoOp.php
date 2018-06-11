<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UndoOpRepository")
 * @ORM\Table(indexes={@ORM\Index(name="task_type_idx", columns={"task_type"}),@ORM\Index(name="block_num_idx", columns={"block_num"}),@ORM\Index(name="transaction_id_idx", columns={"transaction_id"})})
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

    /**
     * @ORM\Column(type="integer")
     */
    private $task_type;

    /**
     * @ORM\Column(type="integer", options={"default" = 0})
     */
    private $count;

    /**
     * @ORM\Column(type="integer")
     */
    private $block_time;

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

    public function getTaskType(): ?int
    {
        return $this->task_type;
    }

    public function setTaskType(int $task_type): self
    {
        $this->task_type = $task_type;

        return $this;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function setCount(int $count): self
    {
        $this->count = $count;

        return $this;
    }

    public function getBlockTime(): ?int
    {
        return $this->block_time;
    }

    public function setBlockTime(int $block_time): self
    {
        $this->block_time = $block_time;

        return $this;
    }
}
