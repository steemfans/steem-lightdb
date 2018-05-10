<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LogsRepository")
 */
class Logs
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="bigint")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $which_table;

    /**
     * @ORM\Column(type="bigint")
     */
    private $which_id;

    /**
     * @ORM\Column(type="bigint")
     */
    private $block_num;

    /**
     * @ORM\Column(type="string", length=40)
     */
    private $transaction_id;

    /**
     * @ORM\Column(type="integer")
     */
    private $operation_index;

    public function getId()
    {
        return $this->id;
    }

    public function getWhichTable(): ?string
    {
        return $this->which_table;
    }

    public function setWhichTable(string $which_table): self
    {
        $this->which_table = $which_table;

        return $this;
    }

    public function getWhichId(): ?int
    {
        return $this->which_id;
    }

    public function setWhichId(int $which_id): self
    {
        $this->which_id = $which_id;

        return $this;
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

    public function getOperationIndex(): ?int
    {
        return $this->operation_index;
    }

    public function setOperationIndex(int $operation_index): self
    {
        $this->operation_index = $operation_index;

        return $this;
    }
}
